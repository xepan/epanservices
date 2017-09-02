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
		'Trial'=>['view','edit','delete','manage_applications','pay','validity','expire','usage_limit'],
		'Paid'=>['view','edit','delete','manage_applications','expire','usage_limit'],
		'Grace'=>['view','edit','delete','manage_applications','pay','expire','usage_limit'],
		'Expired'=>['view','edit','delete','pay']
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

	function invoicePaid($app,$invoice){
		
		foreach ($invoice->items() as $invoice_item) {
			// check if oi item belongs to Epan category
			$item = $this->add('xepan\commerce\Model_Item')->load($invoice_item['item_id']);
			// update expiry of epan
			if($item->isInCategory('Epan')){
				$cf_array = json_decode($invoice_item['extra_info'],true)?:[];

				foreach ($cf_array[0] as $key => $value) {
					if(!is_numeric($key)) continue;
					// update epan
					if($value['custom_field_name'] == "epan name" && $value['custom_field_value_name']){

						$epan_model = $this->add('xepan\epanservices\Model_Epan')->addCondition('name',$value['custom_field_value_name']);
						$epan_model->tryLoadAny();
						if($epan_model->loaded()){
							$epan_model['expiry_date'] = date("Y-m-d H:i:s", strtotime('+ '.$item['renewable_value']." ".$item['renewable_unit'], strtotime($this->app->now)));
							$epan_model->save();
						}
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
		
		$dsn = "mysql://$username:$password@$host/$database";
		$config_file = "<?php \n\n\t\$config['dsn'] = '".$dsn."';\n\n";

		file_put_contents(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name']).'/config.php', $config_file);
		$this->app->db->dsql()->expr("CREATE database `$database`;")->execute();
		$this->app->db->dsql()->expr("GRANT ALL PRIVILEGES ON `$database`.* To '$username'@'%' IDENTIFIED BY '$password';")->execute();

		$new_db = $this->add('DB');
		$new_db->connect($dsn);
		if($this->app->is_admin)
			$new_db->dsql()->expr(file_get_contents(getcwd().'/../install.sql'))->execute();
		else
			$new_db->dsql()->expr(file_get_contents(getcwd().'/install.sql'))->execute();

		$saved_db = clone $this->app->db;
		$this->app->db = $new_db;

		try{
			$user = $this->app->auth->model;

			$this->api->db->beginTransaction();
			$this->app->db->dsql()->expr('SET FOREIGN_KEY_CHECKS = 0;')->execute();
			$this->app->resetDB = true;

			foreach ($this->app->xepan_addons as $addon) {
				if($addon==='xepan\\base' or $addon==='xepan\base') {
					$this->app->xepan_app_initiators[$addon]->resetDB(null,$install_apps=false);
				}
				else
					$this->app->xepan_app_initiators[$addon]->resetDB();
			}

			if($user_model instanceof \xepan\base\Model_User && $user_model->loaded())
				$user = $user_model;

			$this->installApplication();
			
			$this->add('xepan\base\Model_Epan')->tryLoadAny()
						->set('name',$this['name'])
						->set('epan_dbversion',$this['epan_dbversion'])
						->save();
			
			$user_new = $this->add('xepan\base\Model_User')->tryLoadAny()->set('username',$user['username']);
			if($user_model instanceof \xepan\base\Model_User && $user_model->loaded())
				$user_new['password'] = $user_model['password'];
			$user_new->save();

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

	function createFolder($m){
		if(file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name']))){
			throw $this->exception('Epan cannot be created, folder already exists','ValidityCheck')
						->setField('name')
						->addMoreInfo('epan',$this['name']);
		}

		if(file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/default/www'))){
			$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name']);
			$fs = \Nette\Utils\FileSystem::copy('./websites/default/www','./websites/'.$this['name'].'/www',true);
		}else{
			$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name']);
			$fs = \Nette\Utils\FileSystem::copy('./vendor/xepan/cms/templates/defaultlayout','./websites/'.$this['name'],true);
		}
		$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name'].'/assets');
		$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name'].'/upload');
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

			$this->add('View')->set('ReCreate with CompleteLIster, ACL transaction query with crud problem, may be form and grid can work');

			$installed_apps = $this->add('xepan\base\Model_Epan_InstalledApplication',['skip_epan_condition'=>true]);
			$installed_apps->addCondition('epan_id',$this->id);

			$crud = $p->add('xepan\hr\CRUD');
			$crud->setModel($installed_apps);

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
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->validity($form['valid_till']);
			$this->app->employee
				->addActivity("Epan '".$this['name']."' Validity Changed By'".$this->app->employee['name']."'", $this->id, null,null,null,null)
				->notifyWhoCan('pay,expire','Trial');
			return $p->js()->univ()->closeDialog();
		}
	}

	function validity($valid_till){
		// $extra_info = json_decode($this['extra_info'],true);
		// $extra_info ['valid_till'] = $valid_till;
		$this['expiry_date'] = $valid_till;
		$this->save();
		return true;
	}


	function page_usage_limit($p){
		$extra_info = json_decode($this['extra_info'],true);
		$employee_limit = isset($extra_info ['specification']['employee'])?$extra_info ['specification']['employee']:0;
		$email_settings_limit = isset($extra_info ['specification']['email'])?$extra_info ['specification']['email']:0;
		$email_threshold_limit = isset($extra_info ['specification']['threshold'])?$extra_info ['specification']['threshold']:0;
		$storage_limit = isset($extra_info ['specification']['storage'])?$extra_info ['specification']['storage']:0;
		
		$form = $p->add('Form');
		$form->addField('employee_limit')->set($employee_limit);
		$form->addField('email_settings_limit')->set($email_settings_limit);
		$form->addField('email_threshold_limit')->set($email_threshold_limit);
		$form->addField('storage_limit')->set($storage_limit);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->usage_limit($form);
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
		$this->save();
		return true;
	}

	function installApplication(){		
		$epan = $this->add('xepan\base\Model_Epan')->tryLoadAny();		

        $extra_info = $this['extra_info']; // loaded from old app->db  (changed in called function btw)
        $extra_info = json_decode($extra_info,true);

        $addons_to_keep = [];

        $spec = [];
        if(isset($extra_info['specification']))
        	$spec = $extra_info['specification'];

        foreach ($spec as $key => $value) {
            if(strtolower($value) === 'yes'){
            	$app = $this->add('xepan\base\Model_Application')->tryLoadBy('namespace','xepan\\'.strtolower($key));
            	if($app->loaded())
	            	$epan->installApp($app);
            }
        }

	}
}
