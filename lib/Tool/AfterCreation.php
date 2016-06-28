<?php

namespace xepan\epanservices;

class Tool_AfterCreation extends \xepan\cms\View_Tool {
	public $options = [
	];

	function init(){
		parent::init();		
		
		$admin_url = "http://www.".$_GET['epan_name']."epan.in/admin";
		$website_url = "http://www.".$_GET['epan_name']."epan.in";

		$this->template->trySet('admin',$admin_url);
		$this->template->trySet('website',$website_url);
	}

	function defaultTemplate(){
		return['view\tool\greetings'];
	}
}