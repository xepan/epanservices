<?php

namespace xepan\epanservices;

class Tool_Download extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		if($_GET['download']){
			$this->js(true)->univ()->successMessage('Hello');
		}

		$this->js('click',$this->js()->univ()->successMessage('Your download should start now'))->reload(['download'=>true])->_selector('#download-btn');

	}

	function defaultTemplate(){
		if($_GET['download'])
			return ['view\tool\customer-download','downloadbtn'];
		
		return ['view\tool\customer-download'];
	}

}