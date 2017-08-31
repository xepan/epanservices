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
		if(!$this->customer->loaded())
			$this->app->redirect($this->app->url('login',['layout'=>'new_registration']));
		$menu = [
				['key'=>$this->app->url('customer-dashboard'),'name'=>'Dashboard'],
				// ['key'=>$this->app->url('customer-dashboard',['view'=>'newepan']), 'name'=>'New Epan'],
			];

		$this->complete_lister = $cl = $this->add('CompleteLister',null,null,['view/customermenu']);
			$cl->setSource($menu);

		$view = $_GET['view'];
		if(!$view){
			$view = "dashboard";
		}
		$page = "view=".$view;

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