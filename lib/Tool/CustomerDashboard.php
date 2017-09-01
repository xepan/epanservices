<?php

namespace xepan\epanservices;

class Tool_CustomerDashboard extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		// $live_edit_id = $this->app->stickyGET('live_edit');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();

		$epan = $this->add('xepan\epanservices\Model_Epan');
		$epan->addCondition('created_by_id',$customer->id);

		$grid = $this->add('Grid');
		$grid->setModel($epan,['name','status']);
		$grid->addColumn('Button','live_edit','Live Edit');
		$grid->addColumn('Button','detail','Detail');

		if($live_edit_id = $_GET['live_edit']){
			$this->js(true)->univ()->errorMessage('TODO ... -:)')->execute();
		}

		if($detail_id = $_GET['detail']){
			$this->js(true)->univ()->location($this->app->url('epan-detail',['selected'=>$_GET['detail']]))->execute();
		}

	}
}