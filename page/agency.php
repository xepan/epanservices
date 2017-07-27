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

class page_agency extends \xepan\base\Page {
	public $title='Agency List';

	function init(){
		parent::init();
		
		$model = $this->add('xepan\epanservices\Model_Agency');
		$model->addCondition('is_channelpartner',false);

		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($model,['first_name','user_id','channelpartner_id']);
		
		// if($crud->isEditing()){
		// 	$form = $crud->form;
		// 	$form->getElement('created_by_id')->model();
		// }

	}
}
