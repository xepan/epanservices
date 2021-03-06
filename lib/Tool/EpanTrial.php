<?php

namespace xepan\epanservices;

class Tool_EpanTrial extends \xepan\cms\View_Tool {
	public $options = [
		'login_page'=>'login',
		'sale_item_id'=>0,
		'button_name'=>'Free 14 day Trial',
		'next_page'=>'select-theme'
	];

	function init(){
		parent::init();

		if($this->owner instanceof \AbstractController) return;

		if($item_id = $this->app->stickyGET('x-new-product')){
			$this->options['sale_item_id'] = $item_id;
			$this->options['button_name'] = "Next";
			$this->add('xepan\epanservices\View_ProgressBar',['active_step'=>1],'header');
		}

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn("Customer");

		$this->create_trial_vp = $this->add('VirtualPage');
		$this->create_trial_vp->set([$this,'create_trial_vp']);

		$this->app->addStyleSheet('jquery-ui');
		$company_m = $this->add('xepan\base\Model_Config_CompanyInfo');
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
			$f->addSubmit('Free 14 day Trial')->addClass('btn btn-primary btn-block xepan-form-free-trial-btn')->addStyle(['font-size'=>'42px','font-family'=>'Lucida Console']);

			if($f->isSubmitted()){
				$this->app->redirect($this->options['login_page']);
				return;
			}
			return;
		}

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
			$submit_button = $form->addSubmit($this->options['button_name'])->addClass('btn btn-primary');
			$submit_button->js('click',$v->js()->show());
		}

		if($form->isSubmitted()){

			// validate name 
			if(preg_match("/[^[:alnum:]\-]/",strtolower($form['epan_name']))){
				$form->js(true,$v->js(true)->hide())
	            	->atk4_form('fieldError','epan_name','Only AphaNumeric values permitted')
	            	->execute();
			}
			
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
        	$epan_name = strtolower($form['epan_name']);

        	$myEpans = $this->add('xepan\epanservices\Model_Epan');
        	$myEpans->addCondition('name',$epan_name);
        	$myEpans->tryLoadAny();

        	if($myEpans->loaded()){
        		$form->js(true,$v->js(true)->hide())
	            ->atk4_form('fieldError','epan_name','name already taken')
	            ->execute();
        		return;
        	}



        	/* IF CUSTOMER IS LOGGED IN AND EPAN NAME IS UNIQUE THEN CREATE EPAN */
        	$form->js()->univ()->frameURL('Please wait, creating your system',$this->app->url($this->create_trial_vp->getURL(),['epan_name'=>$epan_name]))->execute();
		}
	}

	function create_trial_vp($page){
		$this->app->stickyGET('epan_name');
		$page->add('View_Console')->set(function($c){
        	$this->app->setConfig('View_Console',$c);
			$epan_name = $_GET['epan_name'];
			$c->out('Initiating Creating System for \''.$_GET['epan_name'].'\'');
			// $c->jsEval($this->js()->univ()->successMessage($this->options['next_page']));
			// return;
			$email_settings = $this->add('xepan\communication\Model_Communication_DefaultEmailSetting')->tryLoadAny();
        	try{
        		set_time_limit(0);
				$this->api->db->beginTransaction();
				$c->out('Registering sub-domain  \''.$_GET['epan_name'].'\'');
	        	$this->createEpan($epan_name); // in epan services database, just a new row with specifications of apps
	        	$c->out(' - sub-domain registered');
	        	$newEpan_inServices = $this->add('xepan\epanservices\Model_Epan')
	        						->addCondition('name',$epan_name)->tryLoadAny()
	        						;
	        	$newEpan_inServices['is_published'] = true;
				$newEpan_inServices['expiry_date'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime($this->app->now)) . " +14 DAY"));
	        	$c->out('Creating directory structure');
				$newEpan_inServices->createFolder($newEpan_inServices);
	        	$c->out(' - Directory structure created');

				$current_user = $this->add('xepan\base\Model_User_Active')->load($this->app->auth->model->id);
				$newEpan_inServices->userAndDatabaseCreate($current_user); // individual new epan database
				$newEpan_inServices->save();
	        	$c->out(' - Database and initial user created');

				$this->api->db->commit();
			}catch(\Exception_StopInit $e){
				$this->api->db->commit();
			}catch(\Exception $e){
				if($this->api->db->inTransaction()) $this->api->db->rollback();
				throw $e;
				if(isset($newEpan_inServices))
					$newEpan_inServices->swipeEverything($epan_name);
    			
				$form->js(true,$v->js(true)->hide())
	            ->atk4_form('fieldError','epan_name','Could not create epan, please try again.')
	            ->execute();
        		return;
			}

			$customer = $this->customer;
        	$user = $customer->user();
        	$email_id = $user['username'];

        	$c->out('Setting up your new Epan');
        	$this->associateCustomerWithCategory($customer);
        	$c->out(' - Setting up done');
        	try{
				$this->sendGreetingsMail($email_id,$email_settings);
	        	$c->out('Send you a greeting email on '.$email_id);
        	}catch(\Exception $e){
        		$c->err($e->getMessage());
        		$c->err('Could not send you email');
        	}

        	$c->out('Redirecting to new created epan');
			if($this->options['next_page']){
				$detail = [
							'epan_name'=>$epan_name,
							'epan_id'=>(isset($newEpan_inServices)?$newEpan_inServices->id:0)
						];
				$this->app->memorize('newepan',$detail);
        		$c->jsEval($this->js()->univ()->location($this->app->url($this->options['next_page'])));
			}else{
        		$c->jsEval($this->js()->univ()->location($this->app->url('greetings',['epan_name'=>$epan_name,'message'=>'We have sent you a welcome mail. Check your email address linked to the account.'])));
			}

		});
	}

	// Associate customer with "Online Epan Customer" category as soon as Epan is created
	function associateCustomerWithCategory($customer){
		$marketing_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$marketing_category->tryLoadBy('name','Online Epan Customer');

		if(!$marketing_category->loaded()){
			$marketing_category['name'] = 'Online Epan Customer';
			$marketing_category['system'] = true;
			$marketing_category->save();
		}

		$cat_assocs = $this->add('xepan\marketing\Model_Lead_Category_Association');
		$cat_assocs->addCondition('lead_id',$customer->id);
		$cat_assocs->addCondition('marketing_category_id',$marketing_category->id);
		$cat_assocs->tryLoadAny();

		if($cat_assocs->loaded())
			return;
		
		$cat_assocs->save();
	}

	function sendGreetingsMail($email_id,$email_settings){
		// $email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
		$mail = $this->add('xepan\communication\Model_Communication_Email');
														
		$email_subject = file_get_contents(getcwd().'/shared/apps/xepan/epanservices/templates/mail/greeting_mail_subject.html');
		$email_body = file_get_contents(getcwd().'/shared/apps/xepan/epanservices/templates/mail/greeting_mail_body.html');

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
		

		$trial_item = $this->add('xepan\commerce\Model_Item');
		if($sale_item_id = $this->options['sale_item_id'])
			$trial_item->load($sale_item_id);
		else{
			$trial_item->tryLoadBy('name','EpanTrial');
		}

		if(!$trial_item->loaded()) throw $this->exception('Please create an item with "EpanTrial" name first');
		
		$trial_item_id = $trial_item->id;
		$trial_item_count = 1;

		$model_cart = $this->add('xepan\commerce\Model_Cart');
		$model_cart->emptyCart();
		$model_cart->addItem($trial_item_id,$trial_item_count,null,$cf_array);

		/*CREATING ORDER FROM CART*/
		$customer = $this->add('xepan\commerce\Model_Customer');

		if(!$customer->loadLoggedIn("Customer"))
			throw new \Exception("you logout or session out try again");

		$billing_detail = $customer->getAddress();
		// $billing_detail = [
		// 					'billing_address' => ' ',
		// 					'billing_city'=>' ',
		// 					'billing_state_id'=>' ',
		// 					'billing_country_id'=>' ',
		// 					'billing_pincode'=>' ',

		// 					'shipping_address' =>' ',
		// 					'shipping_city'=>' ',
		// 					'shipping_state_id'=>' ',
		// 					'shipping_country_id'=>' ',
		// 					'shipping_pincode'=>' ',
		// 					];
		$order = $this->add('xepan\commerce\Model_SalesOrder');
		$order = $order->placeOrderFromCart($billing_detail,false);
		$this->app->hook('order_placed',[$order]);
	}

	function defaultTemplate(){
		return['view\tool\epantrial'];
	}
}