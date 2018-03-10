<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\epanservices;

class page_licensegenerator extends \xepan\base\Page {
	public $title='Epan License Generator';

	function init(){
		parent::init();
		
		$form= $this->add('Form');
		$form->addField('application');
		$form->addField('key');
		$form->addField('DatePicker','valid_till');
		$form->addSubmit('Generate');

		$v = $this->add('View');

		if($_GET['application']){
			$v->set(md5($_GET['key'].$_GET['application'].$_GET['valid_till']));
		}

		if($form->isSubmitted()){
			$v->js()->reload($form->getAllFields())->execute();
		}

	}
}
