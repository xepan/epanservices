<?php

namespace xepan\epanservices;

class Tool_AfterCreation extends \xepan\cms\View_Tool {
	public $options = [
	];

	function init(){
		parent::init();		
	}

	function defaultTemplate(){
		return['view\tool\greetings'];
	}
}