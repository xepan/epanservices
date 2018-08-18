<?php

/**
* description: ATK Model
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\epanservices;

class Model_Epan extends \xepan\base\Model_Epan{

	public $status = ['Trial','Paid','Grace','Expired'];
	
	public $actions = [
		'Trial'=>['view','edit','manage_applications','pay','validity','expire','usage_limit','associate_with_category','copy_website_and_db_from','change_publish_status','previous_themes_folders'],
		'Paid'=>['view','edit','manage_applications','validity','expire','usage_limit','associate_with_category','copy_website_and_db_from','change_publish_status','previous_themes_folders'],
		'Grace'=>['view','edit','delete','manage_applications','pay','validity','expire','usage_limit','associate_with_category','copy_website_and_db_from','change_publish_status','previous_themes_folders'],
		'Expired'=>['view','edit','delete','pay','validity','associate_with_category','copy_website_and_db_from','change_publish_status','previous_themes_folders']
	];

	function init(){
		// throw new \Exception($this->app->epan['epan_dbversion'], 1);

		parent::init();
		$this->getElement('epan_category_id')->display(['form'=>'xepan\commerce\DropDown']);

		// $this->addHook('beforeInsert',[$this,'createFolder']);
		// $this->addHook('beforeInsert',[$this,'userAndDatabaseCreate']);
		$this->addHook('beforeDelete',[$this,'notifyByHook']);
		$this->addHook('beforeDelete',[$this,'swipeEverything']);
	}

	function createFromOrder($app,$order){
		$customer = $this->add('xepan\commerce\Model_Customer');
		
		if(!$customer->loadLoggedIn("Customer"))
			throw new \Exception("customer/ user not found");
		
		$epan_service_category = ['Epan','Templates','Addons','Application'];
		
		// foreach order item
		$order_items = $order->orderItems();
		foreach ($order_items as $order_item) {
			
			$item = $order_item->item();
			$associate_category	= $this->add('xepan\commerce\Model_CategoryItemAssociation')->addCondition('item_id',$item->id);
			$associate_category->addExpression('category_name')->set($associate_category->refSQL('category_id')->addCondition('status','Active')->fieldQuery('name'));

			foreach ($associate_category as $category) {						
				// if item is not in epan_service_category then continue
				if(!in_array($category['category_name'], $epan_service_category))
					continue;

				// if item category is epan
				// create epan in trial mode give unique epan name
				if($category['category_name'] === "Epan"){
					$extra_info['qsp_detail_id'] = $order_item->id;
					$extra_info['item_id'] = $item->id;
					$extra_info['specification'] = $item->getSpecification($case='exact');
					$extra_info['valid_till'] = date("Y-m-d H:i:s", strtotime('+ 2 Weeks', strtotime($this->app->now)));

					$epan_item_info = json_encode($extra_info);
					$this->createTrialEpan($epan_item_info,$order_item);
				}

				// 	if item category is template
				// get current user active and paid epan 
				// 	add template to user account
				if($category['category_name'] === "Templates"){
					
				}

			}
		}
	}

	function createTrialEpan($epan_item_info, $order_item){
		
		$customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn("Customer");

		$new_trial_epan = $this->add('xepan\epanservices\Model_Epan');

		/* EXTRACTING OUT EPAN NAME FROM EXTRA FIELD OF ORDER ITEM, (JUST IN CASE OF TRIAL)*/
		if(count(json_decode($order_item['extra_info']))){
			$cf_array = json_decode($order_item['extra_info'],true);
			
			$cf_genric_model = $this->add('xepan\commerce\Model_Item_CustomField_Generic')->addCondition('name','epan name')->tryLoadAny();
			$epan_name = $cf_array[0][$cf_genric_model->id]['custom_field_value_name'];
			$new_trial_epan['name'] = $epan_name;

		}else{
			$new_trial_epan['name'] = uniqid();
		}
		

		$new_trial_epan['created_by_id'] = $customer->id;
		$new_trial_epan['status'] = "Trial";
		$new_trial_epan['expiry_date'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime($this->app->now)) . " +14 DAY"));
		$new_trial_epan['epan_category_id'] = $this->add('xepan\base\Model_Epan_Category')->tryLoadAny()->id;
		$new_trial_epan['extra_info'] = $epan_item_info;		
		$new_trial_epan['epan_dbversion'] = $this->app->epan['epan_dbversion'];		
		$new_trial_epan->save();

		return $new_trial_epan;
	}

	function getCfAndValue($cf_json){
		$cf_array = json_decode($cf_json,true)?:[];

		$return_array = [];
		foreach ($cf_array as $data_array) {
			foreach ($data_array as $key => $value) {
				if(!is_numeric($key)) continue;

				$return_array[strtolower(trim($value['custom_field_name']))] = strtolower(trim($value['custom_field_value_name']));
			}
		}
		
		return $return_array;
	}

	function invoicePaid($app,$invoice){
		
		if($invoice['status'] != "Paid") return;

		
		foreach ($invoice->items() as $invoice_item) {
			// check if oi item belongs to Epan category
			$item = $this->add('xepan\commerce\Model_Item')->load($invoice_item['item_id']);
			$cf_value_array = $this->getCfAndValue($invoice_item['extra_info']);
			
			if($item->isInCategory('Hosting') AND isset($cf_value_array['domain name']) AND isset($cf_value_array['epan name']) AND isset($cf_value_array['year'])){
				// add domain entry
				$epan_model = $this->add('xepan\epanservices\Model_Epan')->loadBy('name',$cf_value_array['epan name']);
				$epan_model->addDomain($cf_value_array['domain name'],$cf_value_array['year'],$invoice['contact_id']);

				//update aliases
				$epan_name = $cf_value_array['epan name'];
				$alias_name = $cf_value_array['domain name'];

				$epan_model = $this->add('xepan\epanservices\Model_Epan')
								->tryLoadBy('name',$epan_name);
				$temp = explode(",", $epan_model['aliases']);
				$temp[] = '"'.$alias_name.'"';
				$epan_model['aliases'] = trim(implode(",", $temp),",");
				$epan_model->save();

			}elseif($item->isInCategory('Epan Alias Purchase') AND $cf_value_array['epan name'] AND $cf_value_array['epan alias']){
				// epan alias purchase
				$epan_name = $cf_value_array['epan name'];
				$alias_name = $cf_value_array['epan alias'];

				$epan_model = $this->add('xepan\epanservices\Model_Epan')
								->tryLoadBy('name',$epan_name);
				
				$temp = explode(",", $epan_model['aliases']);
				$temp[] = '"'.$alias_name.'"';
				$epan_model['aliases'] = trim(implode(",", $temp),",");
				$epan_model->save();
			}elseif($item->isInCategory('Epan Domain Park') AND $cf_value_array['epan name'] AND $cf_value_array['epan domain park name']) {
				// epan domain name parked purchase
				$epan_name = $cf_value_array['epan name'];
				$park_domain_name = $cf_value_array['epan domain park name'];

				// check if domain is already parked
				if($this->checkAliasExist($park_domain_name)){
					throw new \Exception("this domain ".$park_domain_name." is already parked");
				}

				$epan_model = $this->add('xepan\epanservices\Model_Epan')
								->tryLoadBy('name',$epan_name);
				$temp = explode(",", $epan_model['aliases']);
				$temp[] = '"'.$park_domain_name.'"';
				$epan_model['aliases'] = trim(implode(",", $temp),",");
				$epan_model->save();

			}elseif($item->isInCategory('Epan')){
				
				// update expiry of epan
				if($cf_value_array['epan name']){
					$cf_value = $cf_value_array['epan name'];

					$epan_model = $this->add('xepan\epanservices\Model_Epan')->addCondition('name',$cf_value);
					$epan_model->tryLoadAny();
					if($epan_model->loaded()){
						$epan_model['expiry_date'] = date("Y-m-d H:i:s", strtotime('+ '.$item['renewable_value']." ".$item['renewable_unit'], strtotime($this->app->now)));
						$epan_model['status'] = "Paid";
						$epan_model->save();
					}
				}

			}

		}
		// get related order
		// 	if item is epan
		// 		change mode to paid and extend valid_till
		// 	if item is template
		// 		mark paid
	}

	function userAndDatabaseCreate($user_model=null){
		preg_match(
                    '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                    '(/([0-9a-zA-Z_/\.-]*))|',
                    $this->app->getConfig('dsn'),
                    $matches
                );
		
		$username = uniqid();
		$database = strtolower($this->app->normalizeName($this['name']));
		$host = $matches[5];
		$password = md5(uniqid());
		
		if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Creating Config File');
		$dsn = "mysql://$username:$password@$host/$database";
		$config_file = "<?php \n\n\t\$config['dsn'] = '".$dsn."';\n\n";

		file_put_contents(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name']).'/config.php', $config_file);
		
		if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Creating database');
		
		$this->app->db->dsql()->expr("CREATE database `$database`;")->execute();
		$this->app->db->dsql()->expr("GRANT ALL PRIVILEGES ON `$database`.* To '$username'@'%' IDENTIFIED BY '$password';")->execute();

		$new_db = $this->add('DB');
		$new_db->connect($dsn);

		if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Importing database, please wait ... this may take time');

		if($this->app->is_admin)
			$new_db->dsql()->expr(file_get_contents(getcwd().'/../install.sql'))->execute();
		else
			$new_db->dsql()->expr(file_get_contents(getcwd().'/install.sql'))->execute();

		if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out(' - Importing database done');
		
		if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Preparing for first use ...');

		$saved_db = clone $this->app->db;
		$this->app->db = $new_db;

		try{
			$user = $this->app->auth->model;

			$this->api->db->beginTransaction();
			$this->app->db->dsql()->expr('SET FOREIGN_KEY_CHECKS = 0;')->execute();
			$this->app->resetDB = true;

			$addons = $this->app->getConfig('xepan_available_addons',['xepan\\base','xepan\\communication', 'xepan\\hr','xepan\\projects','xepan\\marketing','xepan\\accounts','xepan\\commerce','xepan\\production','xepan\\crm','xepan\\cms','xepan\\blog'/*,'xepan\\epanservices'*/]);

			foreach ($addons as $addon) {
				if($addon==='xepan\\base' or $addon==='xepan\base') {
					$this->app->xepan_app_initiators[$addon]->resetDB(null,$install_apps=false);
				}
				else
					$this->app->xepan_app_initiators[$addon]->resetDB();
			}

			if($user_model instanceof \xepan\base\Model_User && $user_model->loaded())
				$user = $user_model;

			if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Installing Applications');
			$installed_apps_namespaces = $this->installApplication();
			
			$this->add('xepan\base\Model_Epan')->tryLoadAny()
						->set('name',$this['name'])
						->set('epan_dbversion',$this['epan_dbversion'])
						->save();
			
			if($cnsl = $this->app->getConfig('View_Console',false)) $cnsl->out('Creating default user');
			
			$user_new = $this->add('xepan\base\Model_User')->tryLoadAny()->set('username',$user['username']);
			if($user_model instanceof \xepan\base\Model_User && $user_model->loaded()){
				$user_new['password'] = $user_model['password'];
				$user_new['created_by_id']=$this->app->customer->id;
			}
			$user_new->save();
			$cnsl->out('User saved');

			$this->app->db->dsql()->expr('SET FOREIGN_KEY_CHECKS = 1;')->execute();

			$this->api->db->commit();
			$this->app->db = $saved_db;
			$this->app->auth->login($user);


		}catch(\Exception_StopInit $e){
			$this->api->db->commit();

		}catch(\Exception $e){
			$this->api->db->rollback();
			throw $e;
		}

		$this->app->db = $saved_db;
		$this->app->auth->login($user);
	}

	function createFolderTest(){

	}

	function createSuperUser($m,$new_id){
		$user = $this->add('xepan\base\Model_User_SuperUser');
        $this->app->auth->addEncryptionHook($user);
        $user=$user->set('username','admin@epan.in')
             ->set('scope','SuperUser')
             ->set('password','admin')
             ->set('epan_id',$new_id)
             ->saveAndUnload('xepan\base\Model_User_Active');
        $this->app->hook('epan-created',[$new_id]);
	}

	function notifyByHook(){
		$this->app->hook('epan-deleted',[$this]);
	}

	function swipeEverything($epan=null){
		if(!$epan) $epan = $this['name'];
		if($epan instanceof \xepan\epanservices\Model_Epan){
			$epan=$epan['name'];
		}

		if(!file_exists(realpath('websites/'.$epan.'/config.php'))) return;
		
		include_once('websites/'.$epan.'/config.php');
		
		preg_match(
                    '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                    '(/([0-9a-zA-Z_/\.-]*))|',
                    $config['dsn'],
                    $matches
                );

		$fs = \Nette\Utils\FileSystem::delete('./websites/'.$epan);
		// $this->app->db->dsql()->expr("GRANT ALL PRIVILEGES ON `*`.* To '$matches[2]'@'%';")->execute();
		// $this->app->db->dsql()->expr("DROP USER `$matches[2]`@'%'")->execute();
		$this->app->db->dsql()->expr("DROP DATABASE IF EXISTS `$matches[7]`;")->execute();		
	}

	function page_manage_applications($p){

			$applications=[
				'communication'=>['namespace'=>'xepan\communication','user_installable'=>1],
				'Hr'=>['namespace'=>'xepan\hr','user_installable'=>1],
				'projects'=>['namespace'=>'xepan\projects','user_installable'=>1],
				'marketing'=>['namespace'=>'xepan\marketing','user_installable'=>1],
				'accounts'=>['namespace'=>'xepan\accounts','user_installable'=>1],
				'cms'=>['namespace'=>'xepan\cms','user_installable'=>1],
				'blog'=>['namespace'=>'xepan\blog','user_installable'=>1],
				'commerce'=>['namespace'=>'xepan\commerce','user_installable'=>1],
				'production'=>['namespace'=>'xepan\production','user_installable'=>1],
				'crm'=>['namespace'=>'xepan\crm','user_installable'=>1],
				'epanservices'=>['namespace'=>'xepan\epanservices','user_installable'=>1],
				'ispmanager'=>['namespace'=>'xavoc\ispmanager','user_installable'=>1],
				'listing'=>['namespace'=>'xepan\listing','user_installable'=>1],
				'ivf'=>['namespace'=>'xavoc\ivf','user_installable'=>1],
			];

			$installed_apps_array=[];

			$remote_epan = $this->add('xepan\epanservices\Controller_RemoteEpan');
			$remote_epan->setEpan($this->id);

			$remote_epan->do(function($app)use(&$installed_apps_array){
				$installed_apps = $this->add('xepan\base\Model_Epan_InstalledApplication',['skip_epan_condition'=>true]);
				$installed_apps_array = $installed_apps->getRows();
			});


			$p->add('View')->set('Available Applications')->addClass('alert alert-info');
			$form = $p->add('Form');

			foreach ($applications as $name => $details) {
				$f = $form->addField('CheckBox',$name);
				if(in_array($details['namespace'], array_column($installed_apps_array, 'application_namespace'))){
					$f->set(true);
				}
				$f_h = $form->addField('CheckBox',$name.'_hidden');
				foreach ($installed_apps_array as $ins_app) {
					if($ins_app['application']==$name && $ins_app['is_hidden']) $f_h->set(true);
				}
			}

			$form->addSubmit();

			if($form->isSubmitted()){
				
				$selection = $form->getAllFields();
				$to_remove = [];
				$remote_epan->do(function($app) use($remote_epan, &$selection, &$applications, &$to_remove){
					$remote_epan_id = $this->add('xepan\base\Model_Epan')->tryLoadBy('name',$this['name'])->get('id');
					foreach ($applications as $name => $details) {
						$apps_added = $this->add('xepan\base\Model_Application');
						$apps_added->tryLoadBy('name',$name);
						if($selection[$name] && !$apps_added->loaded()){
							$apps_added['namespace']= $details['namespace'];
							$apps_added['user_installable']=1;
							$apps_added->save();
						}

						$installed_apps = $this->add('xepan\base\Model_Epan_InstalledApplication');
						$installed_apps->tryLoadBy('application_id',$apps_added->id);

						if($selection[$name]){
							$installed_apps['epan_id']= $remote_epan_id;
							$installed_apps['is_active']= 1;
							$installed_apps['is_hidden']= $selection[$name.'_hidden'];
							$installed_apps->save();
						}else{
							if(!$selection[$name] && $installed_apps->loaded()) $installed_apps->delete();
							if(!$selection[$name] && $apps_added->loaded()) $apps_added->delete();
						}
					}
				});
				// throw new \Exception(print_r($to_remove,true), 1);
				$form->js()->univ()->successMessage('hurreyy')->execute();
			}

			// $this->add('View')->set('ReCreate with CompleteLIster, ACL transaction query with crud problem, may be form and grid can work');


			// $crud = $p->add('xepan\hr\CRUD');
			// $crud->setModel($installed_apps);

	}

	function pay(){
		$this['status'] = 'Paid';
		$this->save();

		$this->app->employee
				->addActivity("Epan '".$this['name']."' Paid By'".$this->app->employee['name']."'", $this->id, null,null,null,null)
				->notifyWhoCan('manage_applications,expire','Paid');
	}

	function page_validity($p){
		$extra_info = json_decode($this['extra_info'],true);

		$form = $p->add('Form');
		$form->addField('DateTimePicker','valid_till')->set($this['expiry_date']);
		$form->addField('CheckBox','set_as_grace')->set(true);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->validity($form['valid_till'],$form['set_as_grace']);
			$this->app->employee
				->addActivity("Epan '".$this['name']."' Validity Changed By'".$this->app->employee['name']."'", $this->id, null,null,null,null)
				->notifyWhoCan('pay,expire','Trial');
			return $p->js()->univ()->closeDialog();
		}
	}

	function validity($valid_till,$setGrace = true){
		// $extra_info = json_decode($this['extra_info'],true);
		// $extra_info ['valid_till'] = $valid_till;
		$this['expiry_date'] = $valid_till;
		if($setGrace)
			$this['status'] = 'Grace';

		$this->save();
		return true;
	}


	function page_usage_limit($p){
		$extra_info = json_decode($this['extra_info'],true);

		$validity_limit = [
						'Employee Limit'=>0,
						'Backend User Limit'=>0,
						'Email Accounts'=>0,
						'Sendig Email Threshold Per Minute Per Setting'=>0,
						'Mass Email Setting Allowed'=>0,
						'Email IMAP Account Allowed'=>0,
						'Storage Limit'=>0,
						'Data Grabber'=>"No",
						'IndiaMart CRM Integration'=>"No"
				];

		$employee_limit = isset($extra_info ['specification']['Employee Limit'])?$extra_info ['specification']['Employee Limit']:0;
		$backend_user_limit = isset($extra_info ['specification']['Backend User Limit'])?$extra_info ['specification']['Backend User Limit']:0;
		$email_settings_limit = isset($extra_info ['specification']['Email Accounts'])?$extra_info ['specification']['Email Accounts']:0;
		$email_threshold_limit = isset($extra_info ['specification']['Sendig Email Threshold Per Minute Per Setting'])?$extra_info ['specification']['Sendig Email Threshold Per Minute Per Setting']:0;
		$storage_limit = isset($extra_info ['specification']['Storage Limit'])?$extra_info ['specification']['Storage Limit']:0;
		$data_grabber = isset($extra_info ['specification']['Data Grabber'])?$extra_info ['specification']['Data Grabber']:"No";
		$mass_email_allowed = isset($extra_info ['specification']['Mass Email Setting Allowed'])?$extra_info ['specification']['Mass Email Setting Allowed']:0;
		$email_imap_limit = isset($extra_info ['specification']['Email IMAP Account Allowed'])?$extra_info ['specification']['Email IMAP Account Allowed']:0;
		$india_mart_allowed = isset($extra_info ['specification']['IndiaMart CRM Integration'])?$extra_info ['specification']['IndiaMart CRM Integration']:"No";

		$form = $p->add('Form');
		$form->addField('employee_limit')->set($employee_limit)->setFieldHint('0 means unlimited');
		$form->addField('backend_user_limit')->set($backend_user_limit)->setFieldHint('0 means unlimited');
		$form->addField('email_settings_limit')->set($email_settings_limit)->setFieldHint('0 means unlimited');
		$form->addField('sending_email_threshold_per_minute_per_setting')->set($email_threshold_limit)->setFieldHint('same as value define 5 then 5 limit or 0 then 0 limit');
		$form->addField('mass_email_allowed')->set($mass_email_allowed)->setFieldHint('0 means unlimited');
		$form->addField('email_imap_account_allowed')->set($email_imap_limit)->setFieldHint('0 means unlimited');
		$form->addField('storage_limit')->set($storage_limit);
		$form->addField('data_grabber')->set($data_grabber);
		$form->addField('india_mart_crm_integration')->set($india_mart_allowed);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){

			$validity_limit = [
						'Employee Limit'=>$form['employee_limit'],
						'Backend User Limit'=>$form['backend_user_limit'],
						'Email Accounts'=>$form['email_settings_limit'],
						'Sendig Email Threshold Per Minute Per Setting'=>$form['sending_email_threshold_per_minute_per_setting'],
						'Mass Email Setting Allowed'=>$form['mass_email_allowed'],
						'Email IMAP Account Allowed'=>$form['email_imap_account_allowed'],
						'Storage Limit'=>$form['storage_limit'],
						'Data Grabber'=>$form['data_grabber'],
						'IndiaMart CRM Integration'=>$form['india_mart_crm_integration'],
				];
			$this->usage_limit($validity_limit);
			$this->app->employee
				->addActivity("Epan '".$this['name']."' usage limit Changed By'".$this->app->employee['name']."'", $this->id, null,null,null,null)
				->notifyWhoCan('pay,expire,manage_applications','Trial');
			return $p->js()->univ()->closeDialog();
		}
	}

	function usage_limit($detail){
		$extra_info = json_decode($this['extra_info'],true);
		
		foreach ($detail as $key => $value) {
			$extra_info['specification'][$key] = $value;
		}
		$this['extra_info'] = $extra_info;
		$this->save();
		return true;
	}

	function page_expire($p){
		$form = $p->add('Form');
		$form->addField('text','narration');
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->expire($form['narration']);
			$this->app->employee
				->addActivity("Epan '".$this['name']."' Expired By'".$this->app->employee['name']."'", $this->id, null,null,null,null)
				->notifyWhoCan('pay','Expired');
			return $p->js()->univ()->closeDialog();
		}
	}	

	function expire($narration){
		$extra_info = json_decode($this['extra_info'],true);
		$extra_info ['reason_for_expire'] = $narration;
		$this['status']='Expired';
		$this['extra_info']= $extra_info;
		$this->saveAs('xepan\epanservices\Model_Epan');
		return true;
	}


	function page_copy_website_and_db_from($page){
		$form = $page->add('Form');
		$form->addField('xepan\epanservices\Epan','copy_from');
		$form->addField('CheckBox','copy_website');
		$form->addField('CheckBox','copy_db')->setFieldHint('Will also copy upload folder');
		$form->addSubmit('Start Copy');

		if($form->isSubmitted()){
			$this->copy_website_and_db_from($form['copy_from'],$form['copy_website'],$form['copy_db']);
			return $form->js()->univ()->successMessage('Copied');
		}
	}

	function copy_website_and_db_from($from_epan,$website,$db){

		$from_epan = $this->add('xepan\base\Model_Epan')->load($from_epan)->get('name');

		if($website){
			\Nette\Utils\FileSystem::delete('./websites/'.$this['name'].'/www');
			\Nette\Utils\FileSystem::copy('./websites/'.$from_epan.'/www','./websites/'.$this['name'].'/www',true);
		}

		if($db){
			
			// dump from_epan database
			
			$bk = $this->add('xepan\base\Controller_Backup');

			$config = file_get_contents($this->app->pathfinder->base_location->base_path.'/websites/'.$from_epan.'/config.php');
			$config = preg_match('/.*config.*dsn.*=(.*);/i', $config, $dsn_config);
			$dsn = $dsn_config[1];

            preg_match(
                '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                '(/([0-9a-zA-Z_/\.-]*))|',
                $dsn,
                $matches
            );

      		$bk->setDBUser($matches[2]);
      		$bk->setDBPassword($matches[4]);
      		$bk->setDBHost($matches[5]);
      		$bk->setDBName($matches[7]);

            $bk->file_name = $this->api->pathfinder->base_location->base_path.'/./websites/'.$from_epan.'/xepan-copy-dump.sql';
            $bk->export();
			// DO READ COMMENTS AT LAST -- import in `this` epan database -- (remember, we are in epan's main db connected mode, this still means another epan)

            $config = file_get_contents($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name'].'/config.php');
			$config = preg_match('/.*config.*dsn.*=(.*);/i', $config, $dsn_config);
			$dsn = $dsn_config[1];
			
			$new_db = $this->add('DB');
			$new_db->connect($dsn);

			$new_db->dsql()->expr(file_get_contents($bk->file_name))->execute();
			$new_db->dsql()->expr('UPDATE epan SET name="'.$this['name'].'"')->execute();

			// copy upload folder from from_epan
			\Nette\Utils\FileSystem::delete('./websites/'.$this['name'].'/upload');
			\Nette\Utils\FileSystem::copy('./websites/'.$from_epan.'/upload','./websites/'.$this['name'].'/upload',true);
			// remove dump file to save space
			\Nette\Utils\FileSystem::delete($bk->file_name);

		}
	}

	function installApplication(){	
		$installed_apps_namespaces	=[];
		$epan = $this->add('xepan\base\Model_Epan')->tryLoadAny();		

        $extra_info = $this['extra_info']; // loaded from old app->db  (changed in called function btw)
        $extra_info = json_decode($extra_info,true);

        $addons_to_keep = [];

        $spec = [];
        if(isset($extra_info['specification']))
        	$spec = $extra_info['specification'];

        foreach ($spec as $key => $value) {
            if(strtolower($value) === 'yes' || strtolower($value) === 'no*' || strtolower($value) === 'hidden'){
            	$app = $this->add('xepan\base\Model_Application')->tryLoadBy('namespace','xepan\\'.strtolower($key));
            	if($app->loaded()){
            		if($app['user_installable']){
	            		$epan->installApp($app,(strtolower($value)!='yes'));
	            		$installed_apps_namespaces[] = $app['namespace'];
            		}
            	}
            }
        }

        return $installed_apps_namespaces;
	}


	function checkAliasExist($epan_name){
		$epan_model = $this->add('xepan\epanservices\Model_Epan');
		$epan_model->addCondition([['name',$epan_name],['aliases','like','%"'.$epan_name.'"%']]);
		$epan_model->tryLoadAny();
		
		return $epan_model->loaded();
	}

	function purchaseEpanAlias($epan_alias_name,$customer=null,$check_existing=true,$redirect_to_payment=true){
		if(!$this->loaded()) throw new \Exception("epan must loaded");

		if($check_existing && $this->checkAliasExist($epan_alias_name))
			throw new \Exception($epan_alias_name.".epan.in already exists, select another one.");
				
		// load alias item
		$item = $this->add('xepan\commerce\Model_Item');
		$item->tryLoadBy('name','Epan Alias Purchase');
		if(!$item->loaded()) throw new \Exception("alias product not found, contact to epan.in");
		
		// create sale order
		$new_order = $this->placeOrder($customer,$item,['epan alias'=>$epan_alias_name]);
		
		$payment_url = $this->app->url('customer-checkout',
										[
											'step'=>"Payment",
											'order_id'=>$new_order->id,
											'next_step'=>'Payment'
										]
									);
		$this->app->js()->univ()->redirect($payment_url)->execute();

	}

	function parkDomain($domain_name,$customer=null,$check_existing=true,$redirect_to_payment=true){
		if(!$this->loaded()) throw new \Exception("epan must loaded");

		if($check_existing && $this->checkAliasExist($domain_name))
			throw new \Exception($domain_name."is already parked, try with another one");
				
		// load park domain item
		$item = $this->add('xepan\commerce\Model_Item');
		$item->tryLoadBy('name','Epan Domain Park');
		if(!$item->loaded()) throw new \Exception("Domain Park product not found, contact to epan.in");
		
		// create sale order
		$new_order = $this->placeOrder($customer,$item,['epan domain park name'=>$domain_name]);
		$payment_url = $this->app->url('customer-checkout',
										[
											'step'=>"Payment",
											'order_id'=>$new_order->id,
											'next_step'=>'Payment'
										]
									);
		$this->app->js()->univ()->redirect($payment_url)->execute();
	}

	/*
	** Item_detail_array = [
						0=>[
								'id'=>,
								'qty'=>,
								'custom_field'=>[]
							],
						....
				]
	
		$cf_value_array = ['cf_name'=>'value']
	*/

	function placeOrder($customer=null,$item_detail,$cf_value_array=[]){
		$this->customer = $customer;
		if(!$customer){
			$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
			$customer->loadLoggedIn();
			if(!$customer->loaded()) throw new \Exception("customer not found");
		}

		$selected_item = [];
		if($item_detail instanceof \xepan\commerce\Model_Item){

			if(!$item_detail->loaded()) throw new \Exception("item model not loaded");

			$cf_array = [];
			$cf_list = $item_detail->activeAssociateCustomField()->getRows();

			$temp = [];
			foreach ($cf_list as $key => $cf) {

				if(!isset($temp[$cf['department_id']])){
					$temp[$cf['department_id']] = [];
					$temp[$cf['department_id']]['department_name'] = $cf['department'];
				}
				$temp[$cf['department_id']][$cf['customfield_generic_id']] = [];
				$temp[$cf['department_id']][$cf['customfield_generic_id']]['custom_field_name'] = $cf['name'];

				$cf_value = "";
				$cf_value_id = "";
				switch (trim(strtolower($cf['customfield_generic']))) {
					case 'epan name':
						$cf_value_id = $cf_value = $this['name'];
						break;
					case 'epan alias':
						if(!isset($cf_value_array['epan alias'])) throw new \Exception("epan alias value name not found");

						$cf_value_id = $cf_value = $cf_value_array['epan alias'];
						break;
					case 'epan domain park name':
						if(!isset($cf_value_array['epan domain park name'])) throw new \Exception("domain name is not defined, how to park ?");
						$cf_value_id = $cf_value = $cf_value_array['epan domain park name'];
						break;
					case 'domain name':
						if(!isset($cf_value_array['domain name'])) throw new \Exception("domain name is not defined, how to purchase ?");
						$cf_value_id = $cf_value = $cf_value_array['domain name'];
						break;
					case 'tld':
						if(!isset($cf_value_array['tld'])) throw new \Exception("tld is not defined");
						$cf_value_id = $cf_value = $cf_value_array['tld'];
						break;
					case 'year':
						if(!isset($cf_value_array['year'])) throw new \Exception("year");
						$cf_value_id = $cf_value = $cf_value_array['year'];
						break;
				}

				$temp[$cf['department_id']][$cf['customfield_generic_id']]['custom_field_value_id'] = $cf_value_id;
				$temp[$cf['department_id']][$cf['customfield_generic_id']]['custom_field_value_name'] = $cf_value;
			}
			$cf_array = $temp;
			
			$selected_item[0]['id'] = $item_detail->id;
			$selected_item[0]['qty'] = 1;
			$selected_item[0]['custom_field'] = $cf_array;

		}elseif(is_array($item_detail) AND count($item_detail)){
			$selected_item = $item_detail;
		}else{
			throw new \Exception("must pass select item detail");
		}

		$cart_model = $this->add('xepan\commerce\Model_Cart');
		$cart_model->emptyCart();

		foreach($selected_item as $key => $item) {
			$cart_model->addItem($item['id'],$item['qty'],null,$item['custom_field']);
		}

		$address = $customer->getAddress();

		$order = $this->add('xepan\commerce\Model_SalesOrder');
		$order->placeOrderFromCart($address,false);
		$this->app->hook('order_placed',[$order]);
		return $order;

	}

	function checkDomain($domain_name){
		$d = $this->add('xepan\epanservices\Model_DomainDetails');
		$d->addCondition('name',$domain_name);
		$d->tryLoadAny();
		return $d->loaded();

	}

	function purchaseDomain($domain_name,$domain_tld,$year=1,$customer=null,$check_existing=true,$redirect_to_payment=true){
		if(!$this->loaded()) throw new \Exception("epan must loaded");

		if(!$domain_tld)
			throw new \Exception("Top level domain ie. .com, .in etc. must define");

		if($check_existing && $this->checkDomain($domain_name))
			throw new \Exception($domain_name." already exists, select another one.");
				
		// load Domain item
		$item = $this->add('xepan\commerce\Model_Item');
		$item->tryLoadBy('name','Domain');
		if(!$item->loaded()) throw new \Exception("domain product not found, contact to epan.in");
		
		// create sale order
		$cf = [
				'domain name'=>$domain_name,
				'tld'=>$domain_tld,
				'epan name'=>$this['name'],
				'year'=>$year
			];
		$new_order = $this->placeOrder($customer,$item,$cf);
		
		$payment_url = $this->app->url('customer-checkout',
										[
											'step'=>"Payment",
											'order_id'=>$new_order->id,
											'next_step'=>'Payment'
										]
									);
		$this->app->js()->univ()->redirect($payment_url)->execute();

	}

	function addDomain($domain_name,$year=1,$customer_id){
		if(!$this->loaded()) throw new \Exception("epan model must loaded ");

		if($this->checkDomain($domain_name))
			throw new \Exception("domain not available");
		
		$created_at = $this->app->now;
		$expire_date = date('Y-m-d H:i:s',strtotime("+".$year." year", strtotime($created_at)));

		$d = $this->add('xepan\epanservices\Model_DomainDetails');
		$d['name'] = $domain_name;
		$d['park_for_epan_id'] = $this->id;
		$d['created_by_id'] = $customer_id;
		$d['created_at'] = $created_at;
		$d['last_renew_at'] = $created_at;
		$d['expire_date'] = $expire_date;
		$d['vendor'] = $this->app->getConfig('DomianVendor','Generic');
		$d->save();
		return $d;
	}

	function page_associate_with_category($page){

		$m = $this->add('xepan\base\Model_EpanCategoryAssociation');
		$m->addCondition('epan_id',$this->id);
		
		$crud = $page->add('xepan\hr\CRUD');
		$crud->setModel($m,['epan_category_id'],['epan_category']);
		$crud->grid->removeAttachment();
		$crud->grid->removeColumn('action');
	}

	function change_publish_status(){
		$this['is_published'] = !$this['is_published'];
		$this->save();
	}

	function page_previous_themes_folders($page){

		$m = $page->add('Model');
		$m->addField('name');

		$m->addHook('beforeDelete',function($m){
	        if(file_exists($m['name'])){	        	
	            \Nette\Utils\FileSystem::delete($m['name']);
	        }
    	});

		// $p = scandir('websites/'.$this->app->current_website_name);
		$p = glob('websites/'.$this['name'].'/*',GLOB_ONLYDIR);
		$p =array_filter($p,function($v){
			return strpos($v, 'www-') !== false;
		});
        arsort($p);
        $m->setSource('Array',$p);

		$crud = $page->add('xepan\hr\CRUD',['allow_add'=>false,'allow_edit'=>false]);
		$crud->setModel($m);
		$crud->grid->removeColumn('id');
		$crud->grid->addColumn('Button','Revert');

		if($_GET['Revert']){
			$this->js()->univ()->errorMessage("This facility is stopped from admin for security reasons")->execute();
			$m->load($_GET['Revert']);
			\Nette\Utils\FileSystem::delete('./websites/'.$this['name'].'/www-before_revert');
			\Nette\Utils\FileSystem::rename('./websites/'.$this['name'].'/www','./websites/'.$this->app->current_website_name.'/www-before_revert');
			\Nette\Utils\FileSystem::createDir('./websites/'.$this['name'].'/www');
			\Nette\Utils\FileSystem::copy($m['name'],'./websites/'.$this['name'].'/www',true);
			$this->js()->univ()->successMessage($m['name'].' Is copied to www and old www is saved in www-before_revert')->execute();
		}

	}

}
