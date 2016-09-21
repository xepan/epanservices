<?php

namespace xepan\epanservices;

class Tool_EpanTrial extends \xepan\cms\View_Tool {
	public $options = [
		'login_page'=>'login'
	];

	function init(){
		parent::init();	
		$this->app->addStyleSheet('jquery-ui');
		$company_m = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'company_name'=>"Line",
									'company_owner'=>"Line",
									'mobile_no'=>"Line",
									'company_email'=>"Line",
									'company_address'=>"Line",
									'company_pin_code'=>"Line",
									'company_description'=>"text",
									'company_description'=>"text",
									'company_logo_absolute_url'=>"Line",
									'company_twitter_url'=>"Line",
									'company_facebook_url'=>"Line",
									'company_google_url'=>"Line",
									'company_linkedin_url'=>"Line",
									],
						'config_key'=>'COMPANY_AND_OWNER_INFORMATION',
						'application'=>'communication'
					]);
		$company_m->tryLoadAny();	
		
		$social=$this->add('View',null,null,['view/schema-micro-data','social_block']);
		$social->template->trySet('company_name',$company_m['company_name']);
		$social->template->trySet('website_url',$this->app->pm->base_url);
		$social->template->trySet('website_name',$this->app->current_website_name);
		$social->template->trySet('logo_url',$company_m['company_logo_absolute_url']);
		$social->template->trySet('twitter_url',$company_m['company_twitter_url']);
		$social->template->trySet('facebook_url',$company_m['company_facebook_url']);
		$social->template->trySet('google_url',$company_m['company_google_url']);
		$social->template->trySet('linkedin_url',$company_m['company_linkedin_url']);
		
		$view = $this->add('View',null,null,['view/schema-micro-data','person_info']);
		$view->setModel($company_m);
		
		$this->app->memorize('next_url',$this->app->page);
		if(!$this->app->auth->isLoggedIn()){
			$f = $this->add('Form');
			$f->addSubmit('Free 14 days trial')->addClass('btn btn-primary btn-block xepan-form-free-trial-btn')->addStyle(['font-size'=>'42px','font-family'=>'Lucida Console']);

			if($f->isSubmitted()){
				$this->app->redirect($this->options['login_page']);
				return;
			}
			return;
		}

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn();

	    if(!$customer->loaded()){
	    	$this->add('View')->addClass('panel panel-danger')->set('You are not a registered customer');
        	return;            
    	}

    	$v = $this->add('View')->setHtml('<span>Creating your website and admin panel. Be with us, it will take less than 1 minute.</span> <img src="vendor\xepan\epanservices\templates\images\loader.gif">');
    	$v->js(true)->hide();

		/*FORM TO ASK USER FOR EPAN NAME AND TO CHECK LOGGEDIN*/
		if($this->app->auth->isLoggedIn()){
			$form = $this->add('Form',null,'form');
			$form->setLayout('view\tool\form\epantrial');
			$form->addField('epan_name')->setAttr(['placeholder'=>'your website name'])->validate('required?Please enter a website name');
			$submit_button = $form->addSubmit('Free 14 day Trial')->addClass('btn btn-primary');	
			$submit_button->js('click',$v->js()->show());
		}

		if($form->isSubmitted()){
        	/* Already Tried */
        	$trial_epan = $this->add('xepan\epanservices\Model_Epan');
        	$trial_epan->addCondition('created_by_id',$customer->id);
        	$trial_epan->addCondition('status','Trial');
        	$trial_epan->tryLoadAny();

        	if($trial_epan->loaded()){
        		$form->js(true,$v->js(true)->hide())
	            ->atk4_form('fieldError','epan_name','You Have already tried!')
	            ->execute();
        		return;
        	}
        	
        	/* DO EPAN WITH THE SAME NAME EXIST */
        	$epan_name = $form['epan_name'];
        	$myEpans = $this->add('xepan\epanservices\Model_Epan');
        	$myEpans->addCondition('name',strtolower($epan_name));
        	$myEpans->tryLoadAny();

        	if($myEpans->loaded()){
        		$form->js(true,$v->js(true)->hide())
	            ->atk4_form('fieldError','epan_name','name already taken')
	            ->execute();
        		return;
        	}


        	/* IF CUSTOMER IS LOGGED IN AND EPAN NAME IS UNIQUE THEN CREATE EPAN */
        	$epan_name = $form['epan_name'];
        	$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
        	try{
				$this->api->db->beginTransaction();
	        	$this->createEpan($epan_name); // in epan services database, just a new row with specifications of apps
	        	$newEpan_inServices = $this->add('xepan\epanservices\Model_Epan')->addCondition('name',$epan_name)->tryLoadAny();
	        	$newEpan_inServices['is_published']=true;
	        	
				$newEpan_inServices->createFolder($newEpan_inServices);

				$newEpan_inServices->userAndDatabaseCreate(); // individual new epan database
				$newEpan_inServices->save();  	

				$this->api->db->commit();
			}catch(\Exception $e){
				$this->api->db->rollback();
				throw $e;				
				$newEpan_inServices->swipeEverything($epan_name);
    			
				$form->js(true,$v->js(true)->hide())
	            ->atk4_form('fieldError','epan_name','Could not create epan, please try again.')
	            ->execute();
        		return;
			}

        	$user = $customer->user();
        	$email_id = $user['username']; 
			$this->sendGreetingsMail($email_id,$email_settings);

        	return $this->app->redirect($this->app->url('greetings',['epan_name'=>$epan_name,'message'=>'We have sent you a welcome mail. Check your email address linked to the account.']));
		}
	}

	function sendGreetingsMail($email_id,$email_settings){
		// $email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
		$mail = $this->add('xepan\communication\Model_Communication_Email');
														
		$email_subject = file_get_contents(getcwd().'/vendor/xepan/epanservices/templates/mail/greeting_mail_subject.html');
		$email_body = file_get_contents(getcwd().'/vendor/xepan/epanservices/templates/mail/greeting_mail_body.html');

		$subject_temp=$this->add('GiTemplate');
		$subject_temp->loadTemplateFromString($email_subject);
		
		$subject_v=$this->add('View',null,null,$subject_temp);

		$temp=$this->add('GiTemplate');
		$temp->loadTemplateFromString($email_body);
		
		$body_v=$this->add('View',null,null,$temp);
		$body_v->template->trySet('username',$email_id);					

		$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
		$mail->addTo($email_id);
		$mail->setSubject($subject_v->getHtml());
		$mail->setBody($body_v->getHtml());
		$mail->send($email_settings);
	}

	function createEpan($epan_name){
		/* ADDING TRIAL ITEM INTO CART */
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
        
		$trial_item = $this->add('xepan\commerce\Model_Item')->tryLoadBy('name','EpanTrial');
		if(!$trial_item->loaded()) throw $this->exception('Please create an item with "EpanTrial" name first');
		
		$trial_item_id = $trial_item->id;
		$trial_item_count = 1;

		$model_cart = $this->add('xepan\commerce\Model_Cart');
		$model_cart->emptyCart();
		$model_cart->addItem($trial_item_id,$trial_item_count,null,$cf_array);

		/*CREATING ORDER FROM CART*/					
		$billing_detail = [
							'billing_address' => ' ',
							'billing_city'=>' ',
							'billing_state_id'=>' ',
							'billing_country_id'=>' ',
							'billing_pincode'=>' ',

							'shipping_address' =>' ',
							'shipping_city'=>' ',
							'shipping_state_id'=>' ',
							'shipping_country_id'=>' ',
							'shipping_pincode'=>' ',
							];
							
		$order = $this->add('xepan\commerce\Model_SalesOrder');
		$order = $order->placeOrderFromCart($billing_detail,false);
		$this->app->hook('order_placed',[$order]);
	}

	function defaultTemplate(){
		return['view\tool\epantrial'];
	}
}