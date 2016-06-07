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
		'Trial'=>['view','edit','delete','manage_applications','pay'],
		'Paid'=>['view','edit','delete','manage_applications'],
		'Grace'=>['view','edit','delete','manage_applications','pay'],
		'Expired'=>['view','edit','delete','manage_applications','pay']
	];

	function init(){
		parent::init();
		$this->getElement('epan_category_id')->display(['form'=>'xepan\commerce\DropDown']);

		// $this->addHook('beforeInsert',[$this,'createFolder']);
		// $this->addHook('beforeInsert',[$this,'userAndDatabaseCreate']);
		$this->addHook('beforeDelete',[$this,'notifyByHook']);
		$this->addHook('beforeDelete',[$this,'swipeEvenrything']);
	}

	function createFromOrder($app,$order){
		$customer = $this->add('xepan\commerce\Model_Customer');
		
		if(!$customer->loadLoggedIn())
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
					$extra_info['specification'] = $item->getSpecification();
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
		$customer->loadLoggedIn();

		$new_trial_epan = $this->add('xepan\epanservices\Model_Epan');

		/* EXTRACTING OUT EPAN NAME FROM EXTRA FIELD OF ORDER ITEM, (JUST IN CASE OF TRIAL)*/
		if($order_item['extra_info']){
			$cf_array = json_decode($order_item['extra_info'],true);
			
			$cf_genric_model = $this->add('xepan\commerce\Model_Item_CustomField_Generic')->addCondition('name','epan name')->tryLoadAny();
			$epan_name = $cf_array[0][$cf_genric_model->id]['custom_field_value_name'];
			$new_trial_epan['name'] = $epan_name;

		}else{
			$new_trial_epan['name'] = uniqid();
		}
		

		$new_trial_epan['created_by_id'] = $customer->id;
		$new_trial_epan['status'] = "Trial";
		$new_trial_epan['valid_till'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime($this->app->now)) . " +14 DAY"));
		$new_trial_epan['epan_category_id'] = $this->add('xepan\base\Model_Epan_Category')->tryLoadAny()->id;
		$new_trial_epan['extra_info'] = $epan_item_info;		
		$new_trial_epan->save();

		return $new_trial_epan;
	}

	function invoicePaid($app,$invoice){
		// get related order 
		// 	if item is epan
		// 		change mode to paid and extend valid_till
		// 	if item is template
		// 		mark paid
	}

	function userAndDatabaseCreate(){
		preg_match(
                    '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                    '(/([0-9a-zA-Z_/\.-]*))|',
                    $this->app->getConfig('dsn'),
                    $matches
                );
		
		$username = strtolower($this->app->normalizeName($this['name']));
		$database = strtolower($this->app->normalizeName($this['name']));
		$host = $matches[5];
		$password = md5(uniqid());
		
		$dsn = "mysql://$username:$password@$host/$database";
		$config_file = "<?php \n\n\t\$config['dsn'] = '".$dsn."';\n\n";

		file_put_contents(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name']).'/config.php', $config_file);
		$this->app->db->dsql()->expr("CREATE database $database;")->execute();
		$this->app->db->dsql()->expr("GRANT ALL PRIVILEGES ON $database.* To '$username'@'%' IDENTIFIED BY '$password';")->execute();

		$new_db = $this->add('DB');
		$new_db->connect($dsn);
		if($this->app->is_admin)
			$new_db->dsql()->expr(file_get_contents(getcwd().'/../install.sql'))->execute();
		else
			$new_db->dsql()->expr(file_get_contents(getcwd().'/install.sql'))->execute();

		$saved_db = $this->app->db;
		$this->app->db = $new_db;

		try{
			$user = clone $this->app->auth->model;
			$this->api->db->beginTransaction();
			$this->app->db->dsql()->expr('SET FOREIGN_KEY_CHECKS = 0;')->execute();
			$this->app->resetDB = true;

			foreach ($this->app->xepan_addons as $addon) {
				if($addon==='xepan\base') 
					$this->app->xepan_app_initiators[$addon]->resetDB(null,$install_apps=false);
				else
					$this->app->xepan_app_initiators[$addon]->resetDB();
			}

			$this->installApplication();
			
			$this->add('xepan\base\Model_Epan')->tryLoadAny()->set('name',$this['name'])->save();			
			$this->app->db->dsql()->expr('SET FOREIGN_KEY_CHECKS = 1;')->execute();        
			$this->api->db->commit();
			$this->app->db = $saved_db;
			$this->app->auth->login($user);
		}catch(\Exception_StopInit $e){

		}catch(\Exception $e){			
			$this->api->db->rollback();
			$this->app->db = $saved_db;
			$this->app->auth->login($user);
			throw $e;
		}

	}

	function createFolderTest(){

	}

	function createFolder($m){
		if(file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$this['name']))){
			throw $this->exception('Epan cannot be created, folder already exists','ValidityCheck')
						->setField('name')
						->addMoreInfo('epan',$this['name']);
		}
		$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name']);
		$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$this['name'].'/assets');
		$fs = \Nette\Utils\FileSystem::copy('./vendor/xepan/cms/templates/defaultlayout','./websites/'.$this['name'],true);
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

	function swipeEvenrything(){
		include_once('websites/'.$this['name'].'/config.php');
		
		preg_match(
                    '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                    '(/([0-9a-zA-Z_/\.-]*))|',
                    $config['dsn'],
                    $matches
                );

		$fs = \Nette\Utils\FileSystem::delete('./websites/'.$this['name']);
		$this->app->db->dsql()->expr("DROP USER '$matches[2]'@$matches[5];")->execute();
		$this->app->db->dsql()->expr("DROP database $matches[7];")->execute();
		
	}

	function page_manage_applications($p){

			$this->add('View')->set('ReCreate with CompleteLIster, ACL transaction query with crud problem, may be form and grid can work');

			$installed_apps = $this->add('xepan\base\Model_Epan_InstalledApplication',['skip_epan_condition'=>true]);
			$installed_apps->addCondition('epan_id',$this->id);

			$crud = $p->add('xepan\hr\CRUD');
			$crud->setModel($installed_apps);

	}

	function installApplication(){		
		$epan = $this->add('xepan\base\Model_Epan')->tryLoadAny();		

        $extra_info = $this['extra_info']; // loaded from old app->db  (changed in called function btw)
        $extra_info = json_decode($extra_info,true);

        $addons_to_keep = [];
        foreach ($extra_info['specification'] as $key => $value) {
            if(strtolower($value) === 'yes'){
            	$epan->installApp($this->add('xepan\base\Model_Application')->loadBy('namespace','xepan\\'.strtolower($key)));
            }
        }

	}
}
