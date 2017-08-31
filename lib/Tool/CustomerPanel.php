<?php

namespace xepan\epanservices;

class Tool_CustomerPanel extends \xepan\cms\View_Tool {
	public $options = [];
	public $customer;
	function init(){
		parent::init();
		
		$url = "{$_SERVER['HTTP_HOST']}";
        $this->domain = str_replace('www.','',$this->app->extract_domain($url))?:'www';
        $this->sub_domain = str_replace('www.','',$this->app->extract_subdomains($url))?:'www';
		
		if($this->owner instanceof \AbstractController) return;

		$this->addClass('main-box');

		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/epanservices/templates/css/agency.css" />');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer')
							->addCondition('user_id',$this->app->auth->model->id);

		// $agency->addExpression('total_epan')->set(function($m,$q){
		// 	$a = $m->add('xepan\epanservices\Model_Epan');
		// 	$a->addCondition('created_by_id',$m->getElement('id'));
		// 	return $q->expr('IFNULL([0],0)',[$a->count()]);
		// });

		$customer->loadLoggedIn();
		// if(!$customer->loaded()){
		// 	$this->add('View')->set('alert alert-danger')->set('you are not the customer');
		// 	return;
		// }
		
		$this->menubar();

		$view = $this->app->stickyGET('view')?:"dashboard";

		switch ($view) {
			// case "newepan":
			// 	$this->container->add('xepan\epanservices\View_CreateEpan');
			// 	break;
			case "dashboard":
				$this->container = $this->add('View')->addClass('container agency-panel-conatiner main-box');
				$this->dashboard();
				break;
		}
		// $this->add('View')->set('Expired in next 15 days');
		// $epan = $this->add('xepan\base\Model_Epan');
		// $g = $this->add('CRUD');
		
		// $g->setModel($epan);
		// $g->grid->addQuickSearch(['name']);
	
	}

	function dashboard(){
		$action = $this->app->stickyGET('action')?:'item';
		switch ($action) {
			case 'create-epan':
				// $this->container->add('xepan\epanservices\Tool_EpanTrial',['options'=>['login_page'=>'login','sale_item_id'=>$_GET['x-select-id'],'button_name'=>'Next']]);
				break;
			case 'item':
				$this->container->add('xepan\epanservices\View_Item');
				break;
		}

	}

	function menubar(){
		
	}

}