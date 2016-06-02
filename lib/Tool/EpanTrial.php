<?php

namespace xepan\epanservices;

class Tool_EpanTrial extends \xepan\cms\View_Tool {

	function init(){
		parent::init();

		$form = $this->add('Form');
	}

	function defaultTemplate(){
		return['view\tool\epantrial'];
	}
}