<?php

namespace xepan\epanservices;

class Tool_CustomerMenu extends \xepan\cms\View_Tool {
	public $options = [

	];

	function init(){
		parent::init();

		if($this->owner instanceof \AbstractController) return;

		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/epanservices/templates/css/agency.css" />');
		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer')
							->addCondition('user_id',$this->app->auth->model->id);
		$this->customer->loadLoggedIn();

		// update your profile first		
		if( $this->app->page != "customer-setting" && (!$customer['country_id'] OR !$customer['state_id'] OR !$customer['city'] OR !$customer['pin_code'] OR !$customer['address'] OR !$customer['first_name']) ){
			$this->app->redirect($this->app->url('customer-setting',['profile'=>'incomplete']));
		}

		if($customer['country_id'])
			$this->app->country = $this->add('xepan\base\Model_Country')->load($customer['country_id']);
		if($customer['state_id'])
			$this->app->state = $this->add('xepan\base\Model_State')->load($customer['state_id']);

		$editor = $this->add('xepan\cms\Model_User_CMSEditor');
		$editor->addCondition('user_id',$this->app->auth->model->id);
		$editor->tryLoadAny();

		if(!$this->customer->loaded() && !$editor->loaded())
			$this->app->redirect($this->app->url('login',['layout'=>'new_registration']));

		$menu = [
				['key'=>$this->app->url('customer-dashboard'),'name'=>'Dashboard'],
				['key'=>$this->app->url('new-account'), 'name'=>'New Epan'],
				['key'=>$this->app->url('customer-orderhistory'), 'name'=>'Order History'],
				['key'=>$this->app->url('customer-setting'), 'name'=>'Setting']
			];

		$this->complete_lister = $cl = $this->add('CompleteLister',null,null,['view/customermenu']);
			$cl->setSource($menu);

		$page = $this->app->page;
		$cl->addHook('formatRow',function($g)use($page){
			if(strpos($g->model['key'], $page)){
				$g->current_row_html['active_menu'] = "active";
			}else{
				$g->current_row_html['active_menu'] = "deactive";
			}
		});

		$cl->template->trySet('customer_name',$this->customer['name']);
		$cl->template->trySet('customer_dp',($this->customer['image']?:"vendor/xepan/epanservices/templates/images/profile.png"));	
	}
}