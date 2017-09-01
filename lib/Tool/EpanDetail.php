<?php

namespace xepan\epanservices;

class Tool_EpanDetail extends \xepan\cms\View_Tool {

	public $options = [];

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		$epan_id = $_GET['selected'];

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();
		if(!$customer->loaded()) throw new \Exception("customer not found");
		
		$epan = $this->add('xepan\epanservices\Model_Epan');
		$epan->tryLoad($epan_id);
		if(!$epan->loaded()) throw new \Exception("epan not exist");
			
		if($epan['created_by_id'] != $customer->id) throw new \Exception("you are not the authorize the customer for this customer");

		$this->add('View')->setElement('h2')->set($epan['name'].".epan.in")->addClass('text-center');

		
	}
}