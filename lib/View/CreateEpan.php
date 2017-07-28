<?php


namespace xepan\epanservices;

class View_CreateEpan extends \View{

	function init(){
		parent::init();
		

		// /* Already Tried */ check here for agency total trial accounts
	   	//      	$trial_epan = $this->add('xepan\epanservices\Model_Epan');
	   	//      	$trial_epan->addCondition('created_by_id',$new_customer_model->id);
	   	//      	$trial_epan->addCondition('status','Trial');
	   	//      	$trial_epan->tryLoadAny();

	   	//      	if($trial_epan->loaded()){
	   	//      		$form->js(true,$v->js(true)->hide())
		//           ->atk4_form('fieldError','epan_name','You Have already tried!')
		//           ->execute();
	   	//      		return;
	   	//      	}

		$customer = $this->add('xepan\commerce\Model_Customer');

		$form = $this->add('Form');
		$form->setLayout('form/createepan');
		$form->setModel($customer,['first_name','last_name','organization','customer_type','country_id','state_id','city','address','pin_code','tin_no','pan_no','gstin']);
		$form->addField('username');
		$form->addField('password','password');
		$form->addField('password','re_password');
		$form->addField('line','email_id');
		$form->addField('Number','contact_no');
		$form->addField('Line','epan_name');

		$cat_model = $this->add('xepan\commerce\Model_Category')->addCondition('name','Epan Agency');
		$cat_model->tryLoadAny();
		$cat_model->save();

		$cat_asso = $this->add("xepan\commerce\Model_CategoryItemAssociation");
		$cat_asso->addCondition('category_id',$cat_model->id);
		$item_array = [];
		foreach ($cat_asso as $cat) {
			$item_array[$cat['item_id']] = $cat['item'];
		}

		$product_field = $form->addField('xepan\base\DropDown','item');
		$product_field->setValueList($item_array);

		$validate_fields = ['first_name','last_name','organization','country_id','state_id','city','address','pin_code','username','password','re_password','email_id','contact_no','epan_name'];
		foreach ($validate_fields as $key => $field_name) {
			$form->getElement($field_name)->validate('required');
		}

		$form->addSubmit('Create Free 14 Day Trial')->addClass('btn btn-primary btn-block');

		// $v = $this->add('View')->setHtml('<span>Creating your website and admin panel. Be with us, it will take few minutes.</span> <img src="vendor\xepan\epanservices\templates\images\loader.gif">');
		// $v->js(true)->hide();

		if($form->isSubmitted()){

			$epan_name = $form['epan_name'];
			
			try{
				$this->api->db->beginTransaction();

				// check user is exist or not
				$user = $this->add('xepan\base\Model_User_Active');
				$user->addCondition('scope','WebsiteUser');
				$user->addCondition('username',$form['username']);
				$user->tryLoadAny();
				if($user->loaded())
					$form->displayError('username','username already exist');

				$this->add('BasicAuth')
					->usePasswordEncryption('md5')
					->addEncryptionHook($user);
				
				$user['username'] = $form['username'];
				$user['password'] = $form['password'];
				$user->save();
				
				$form->save();
				$new_customer_model = $form->getModel();

				$new_customer_model ['billing_address'] = $form['address'];
				$new_customer_model ['billing_country_id'] = $form['country_id'];
				$new_customer_model ['billing_state_id'] = $form['state_id'];
				$new_customer_model ['billing_city'] = $form['city'];
				$new_customer_model ['billing_pincode'] = $form['pin_code'];

				$new_customer_model ['shipping_address'] = $form['address'];
				$new_customer_model ['shipping_country_id'] = $form['country_id'];
				$new_customer_model ['shipping_state_id'] = $form['state_id'];
				$new_customer_model ['shipping_city'] = $form['city'];
				$new_customer_model ['shipping_pincode'] = $form['pin_code'];
								
				$new_customer_model['user_id'] = $user->id;
				$new_customer_model->save();

				// check email id
				if($form['email_id']){
					$email = $this->add('xepan\base\Model_Contact_Email',['bypass_hook'=>true]);
					$email['contact_id'] = $new_customer_model->id;
					$email['head'] = "Official";
					$email['value'] = $form['email_id'];
					$new_customer_model->checkEmail($email,$form['email_id'],$new_customer_model,$form);
					$email->save();
				}

				// check phone no
				if($form['contact_no']){
					$phone = $this->add('xepan\base\Model_Contact_Phone',['bypass_hook'=>true]);
					$phone['contact_id'] = $new_customer_model->id;
					$phone['head'] = "Official";
					$phone['value'] = $form['contact_no'];
					$new_customer_model->checkPhoneNo($phone,$form['contact_no'],$new_customer_model,$form);
					$phone->save();
				}

				// create epan
        		$epan_model = $this->add('xepan\epanservices\Model_Epan');
        		$epan_model->addCondition('name',strtolower($form['epan_name']));
        		$epan_model->tryLoadAny();

	        	if($epan_model->loaded()){
	        		$form->js(true)
		            ->atk4_form('fieldError','epan_name','name already taken')
		            ->execute();
	        		return;
	        	}

	        	$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
	        	
	        	set_time_limit(60);
	        	$this->createSaleOrder($form['epan_name'],$form['item'],$new_customer_model);

	        	$new_epan_model = $this->add('xepan\epanservices\Model_Epan')->addCondition('name',$form['epan_name'])->tryLoadAny();
	        	$new_epan_model['is_published'] = true;
				$new_epan_model->createFolder($new_epan_model);

				$new_epan_model->userAndDatabaseCreate($user); // individual new epan database
				
				$item = $this->add('xepan\commerce\Model_Item');
				$item->load($form['item']);
				$specification = $item->getSpecification('exact');

				$usage_limit = [
						'employee_limit'=>$specification['Employee Limit'],
						'email_settings_limit'=>$specification['Email Accounts'],
						'email_threshold_limit'=>$specification['Email Threshold'],
						'storage_limit'=>$specification['Storage Limit']
					];

				$new_epan_model->usage_limit($usage_limit);
				$new_epan_model->save();

				$this->api->db->commit();
			}catch(\Exception_StopInit $e){
				// $this->api->db->commit();
			}catch(\Exception $e){
				if($this->api->db->inTransaction()) $this->api->db->rollback();
				throw $e;
				// if(isset($new_epan_model))
				// 	$new_epan_model->swipeEverything($epan_name);
    			
				// $form->js(true)
	   			//->atk4_form('fieldError','epan_name','Could not create epan, please try again.')
	   			//->execute();
    			// return;
			}

			$form->js(null,$form->js()->univ()->reload())->univ()->successMessage('epan created')->execute();
		}

	}

	function createSaleOrder($epan_name,$item_id,$customer_model=null){

		$cf_genric_model = $this->add('xepan\commerce\Model_Item_CustomField_Generic')->addCondition('name','epan name')->tryLoadAny();
		if(!$cf_genric_model->loaded())
			throw new \Exception("please add 'epan name' custom field in epan item");
			
		/* MAKING ARRAY FOR PASSING CUSTOM FIELD IN METHOD */
		$cf_array = [];
		$cf_array[0] = [];
		$cf_array[0]['department_name'] = "";
		$cf_array[0][$cf_genric_model->id] = [];
		$cf_array[0][$cf_genric_model->id]['custom_field_name'] = $cf_genric_model['name'];
		$cf_array[0][$cf_genric_model->id]['custom_field_value_id'] = $epan_name;
		$cf_array[0][$cf_genric_model->id]['custom_field_value_name'] = $epan_name;
        
		$trial_item = $this->add('xepan\commerce\Model_Item')
						->load($item_id);
		if(!$trial_item->loaded()) throw $this->exception('item not found');
		
		$item_count = 1;

		//Load Default TNC
		$tnc = $this->add('xepan\commerce\Model_TNC')->addCondition('is_default_for_sale_order',true)->setLimit(1)->tryLoadAny();
		$tnc_id = $tnc->loaded()?$tnc['id']:0;
		$tnc_text = $tnc['content']?$tnc['content']:"not defined";

		$master_detail = [
				'contact_id' => $customer_model->id,
				'currency_id' => $customer_model['currency_id']?$customer_model['currency_id']:$this->app->epan->default_currency->get('id'),
				'nominal_id' => 0,
				'billing_country_id'=> $customer_model['billing_country_id'],
				'billing_state_id'=> $customer_model['billing_state_id'],
				'billing_name'=> $customer_model['first_name']." ".$customer_model['last_name'],
				'billing_address'=> $customer_model['address'],
				'billing_city'=> $customer_model['city'],
				'billing_pincode'=> $customer_model['pin_code'],
				'shipping_country_id'=> $customer_model['shipping_country_id'],
				'shipping_state_id'=> $customer_model['shipping_state_id'],
				'shipping_name'=> $customer_model['first_name']." ".$customer_model['last_name'],
				'shipping_address'=> $customer_model['shipping_address'],
				'shipping_city'=> $customer_model['shipping_city'],
				'shipping_pincode'=> $customer_model['shipping_pincode'],
				'is_shipping_inclusive_tax'=> 0,
				'is_express_shipping'=> 0,
				'narration'=> null,
				'round_amount'=> 0,
				'discount_amount'=> 0,
				'exchange_rate' => $this->app->epan->default_currency['value'],
				'tnc_id'=>$tnc_id,
				'tnc_text'=> $tnc_text,
				'status' => "OnlineUnpaid"
			];

		$taxation = $trial_item->applicableTaxation();
		if($taxation instanceof \xepan\commerce\Model_Taxation){
			$taxation_id = $taxation->id;
			$tax_percentage = $taxation['percentage'];
		}else{
			$taxation_id = 0;
			$tax_percentage = 0;
		}

		$item = [
				'item_id'=>$item_id,
				'price'=>$trial_item['sale_price'],
				'quantity' => $item_count,
				'taxation_id' => $taxation_id,
				'tax_percentage' => $tax_percentage,
				'narration'=>null,
				'extra_info'=>$cf_array,
				'shipping_charge'=>0,
				'shipping_duration'=>0,
				'express_shipping_charge'=>0,
				'express_shipping_duration'=>null,
				'qty_unit_id'=>$trial_item['qty_unit_id'],
				'discount'=>0
			];
		$detail_data[] = $item;

		$qsp = $this->add('xepan\commerce\Model_QSP_Master')->createQSP($master_detail,$detail_data,'SalesOrder');
		if(isset($qsp['master_detail']['id'])){
			$order_id = $qsp['master_detail']['id'];
			$order = $this->add('xepan\commerce\Model_SalesOrder')->load($order_id);
			$this->app->hook('order_placed',[$order]);
		}


	}

}