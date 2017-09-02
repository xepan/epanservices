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

class page_epantemplates extends \xepan\base\Page {
	public $title='Epans';

	function page_index(){		
		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false],null,['view\epans']);
		$crud->setModel('xepan\epanservices\Epan')->addCondition('is_template',true);

		$btn = $crud->grid->add('Button',null,'grid_buttons')->set('Add New Epan Template')->addClass('btn btn-primary');
		$btn->js('click')->univ()->frameURL('Add New Template',$this->app->url('./addTemplate'));

	}

	function page_addTemplate(){
		$form = $this->add('Form',['js_widget'=>null]);
		$form->add('xepan\base\Controller_FLC')
			->layout([
				'name'=>'Epan Name~c1~6',
				'domain_extension~'=>'c2~6',
				'zip_file'=>'Upload File~c1~12'
				]);

		$epan_name = $form->addField('name');
		$form->layout->add('H1',null,'domain_extension')->set('.epan.in');
		$file = $form->addField('xepan\base\Upload',['name'=>'zip_file','mode'=>'plain']);

		$form->addSubmit('Create Epan Template')->addClass('btn btn-primary');

		if($_POST){
			var_dump($_POST);
			var_dump($_FILES);
			var_dump($file->name);
			var_dump($_POST[$epan_name->name]);
			// $this->add('View')->set('sdfsdfsdfsd');
			// throw new \Exception("Error Processing Request", 1);
			
			$this->app->redirect($this->app->url('xepan_epanservices_epantemplates'));
		}

	}
}
