<?php

namespace xepan\epanservices;

class Tool_AfterCreation extends \xepan\cms\View_Tool {
	public $options = [
	];

	function init(){
		parent::init();		
		
		if($this->owner instanceof \AbstractController) return;
		
		$admin_url = "http://www.".$_GET['epan_name'].".epan.in/admin/?page=xepan_hr_user";
		$website_url = "http://www.".$_GET['epan_name'].".epan.in";
		$message = $this->app->stickyGET('message');

		$this->template->trySet('admin',$admin_url);
		$this->template->trySet('website',$website_url);
		$this->template->trySet('message',$message);
	}

	function defaultTemplate(){
		return['view\tool\greetings'];
	}
}