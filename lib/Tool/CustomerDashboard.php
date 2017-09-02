<?php

namespace xepan\epanservices;

class Tool_CustomerDashboard extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		// $live_edit_id = $this->app->stickyGET('live_edit');

		$this->add('xepan\epanservices\View_MyEpans');

		$this->add('xepan\epanservices\View_MyUnPaidInvoice');
	}
}