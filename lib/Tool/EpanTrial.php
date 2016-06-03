<?php

namespace xepan\epanservices;

class Tool_EpanTrial extends \xepan\cms\View_Tool {
	public $options = [
		'login_page'=>'login'
	];

	function init(){
		parent::init();
		$this->app->addStyleSheet('jquery-ui');

		/*FORM TO ASK USER FOR EPAN NAME AND TO CHECK LOGGEDIN*/
		$form = $this->add('Form',null,'form');
		$form->setLayout('view\tool\form\epantrial');
		$form->addField('epan_name');
		$form->addSubmit('click here and enjoy 15 day free trial');

		if($form->isSubmitted()){

			if(!$this->app->auth->isLoggedIn()){
				$this->app->redirect($this->options['login_page']);
				return;
			}

			$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
	        $customer->loadLoggedIn();

    	    if(!$customer->loaded()){
        	    $this->add('View_Info')->set('Customer account not found');
            	return;            
        	}

        	/* DO EPAN WITH THE SAME NAME EXIST */
        	$epan_name = $form['epan_name'];
        	$myEpans = $this->add('xepan\epanservices\Model_Epan');
        	$myEpans->addCondition('name',strtolower($epan_name));
        	$myEpans->tryLoadAny();

        	if($myEpans->loaded()){
        		$form->error('epan_name','name already taken');
        		return;
        	}

        	/* IF CUSTOMER IS LOGGED IN AND EPAN NAME IS UNIQUE THEN CREATE EPAN */
        	$epan_name = $form['epan_name'];
        	$this->createEpan($epan_name);
        	
        	$newEpan = $this->add('xepan\epanservices\Model_Epan')->addCondition('name',$epan_name)->tryLoadAny();
        	$newEpan['is_published']=true;
			$newEpan->createFolder($newEpan);
			$newEpan->userAndDatabaseCreate();
			$newEpan->save();  	
        	
        	return;
		}
	}

	function createEpan($epan_name){
		/* ADDING TRIAL ITEM INTO CART */
		$cf_genric_model = $this->add('xepan\commerce\Model_Item_CustomField_Generic')->addCondition('name','epan name')->tryLoadAny();
		if(!$cf_genric_model->loaded())
			throw new \Exception("cf epan_name not found");
			
		
		/* MAKING ARRAY FOR PASSING CUSTOM FIELD IN METHOD */
		$cf_array = [];
		$cf_array[0] = [];
		$cf_array[0]['department_name'] = "";
		$cf_array[0][$cf_genric_model->id] = [];
		$cf_array[0][$cf_genric_model->id]['custom_field_name'] = $cf_genric_model['name'];
		$cf_array[0][$cf_genric_model->id]['custom_field_value_id'] = $epan_name;
		$cf_array[0][$cf_genric_model->id]['custom_field_value_name'] = $epan_name;
        
		$trial_item_id = 2401;
		$trial_item_count = 1;

		$model_cart = $this->add('xepan\commerce\Model_Cart');
		$model_cart->emptyCart();
		$model_cart->addItem($trial_item_id,$trial_item_count,null,$cf_array);

		/*CREATING ORDER FROM CART*/					
		$billing_detail = [
							'billing_address' => ' ',
							'billing_city'=>' ',
							'billing_state_id'=>' ',
							'billing_state'=>' ',
							'billing_country_id'=>' ',
							'billing_country'=>' ',
							'billing_pincode'=>' ',

							'shipping_address' =>' ',
							'shipping_city'=>' ',
							'shipping_state_id'=>' ',
							'shipping_state'=>' ',
							'shipping_country_id'=>' ',
							'shipping_country'=>' ',
							'shipping_pincode'=>' ',
							];
							
		$order = $this->add('xepan\commerce\Model_SalesOrder');
		$order = $order->placeOrderFromCart($billing_detail);
		$this->app->hook('order_placed',[$order]);
	}

	function defaultTemplate(){
		return['view\tool\epantrial'];
	}
}