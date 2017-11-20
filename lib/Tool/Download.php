<?php

namespace xepan\epanservices;

class Tool_Download extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

	}

	function defaultTemplate(){
		return ['view\tool\customer-download'];
	}

}