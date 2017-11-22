<?php

namespace xepan\epanservices;

class Tool_Download extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		if($_GET['download']){
			$val = (int) file_get_contents('vendor/xepan/epanservices/download-count.txt');
			$val++;
			file_put_contents('vendor/xepan/epanservices/download-count.txt', $val);
			$this->app->redirect($this->app->getConfig('xepan-community-stable-download-path'));
			$this->js(true)->univ()->successMessage('Hello');
		}

		$this->js('click',$this->js()->univ()->successMessage('Your download should start soon'))->reload(['download'=>true])->_selector('#download-btn');

	}

	function defaultTemplate(){
		if($_GET['download'])
			return ['view\tool\customer-download','downloadbtn'];
		
		return ['view\tool\customer-download'];
	}

}