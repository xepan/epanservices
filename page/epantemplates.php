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
		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false],null,null);
		$template_m = $this->add('xepan\epanservices\Model_Epan');
		$template_m->addCondition('is_template',true);
		$template_m->add('xepan\base\Controller_TopBarStatusFilter',['extra_conditions'=>[['is_template',true]]]);
		
		$crud->setModel($template_m,
				['name','created_by_id','created_at','expiry_date','status','is_template','aliases'],
				['name','created_by','created_at','expiry_date','status','is_template','is_published']
			);

		$btn = $crud->grid->add('Button',null,'grid_buttons')->set('Add New Epan Template')->addClass('btn btn-primary');
		$btn->js('click')->univ()->frameURL('Add New Template',$this->app->url('./addTemplate'));
		$crud->noAttachment();
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

			$newEpan_inServices = $this->add('xepan\epanservices\Model_Epan')
	        						->addCondition('name',$_POST[$epan_name->name])->tryLoadAny()
	        						;
			// add extra_info with specifications ... 
	    	$newEpan_inServices['is_published'] = true;
	    	$newEpan_inServices['is_template'] = true;
			$newEpan_inServices['expiry_date'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime($this->app->now)) . " +14 DAY"));
			$newEpan_inServices->createFolder($newEpan_inServices);

			$newEpan_inServices->userAndDatabaseCreate(); // individual new epan database
			$newEpan_inServices->save();
			
			$zip = new \xepan\base\zip;

			$fs = \Nette\Utils\FileSystem::rename('./websites/'.$_POST[$epan_name->name].'/www','./websites/'.$_POST[$epan_name->name].'/_www',true);
			$zip->extractZip($_FILES[$file->name]['tmp_name'], './websites/'.$_POST[$epan_name->name].'/www');
			
			$this->app->redirect($this->app->url('xepan_epanservices_epantemplates'));
		}

	}
}
