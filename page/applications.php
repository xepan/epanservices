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

class page_applications extends \xepan\base\Page {
	
	public $title='Applications';

	function init(){
		parent::init();

		$app_model= $this->add('xepan\base\Model_Application');

		$app_model->addHook('beforeInsert',[$this,'createAppStructure']);
		$crud = $this->add('xepan\base\CRUD');
		$crud->setModel($app_model);

		$crud->grid->addFormatter('name','template')->setTemplate('<a href="'.$this->app->url('xepan_epanservices_manageapplication').'&application={$id}" >{$name}</a>','name');
	}

	function createAppStructure($m){
		if(strpos($m['namespace'], 'xavoc\\')!==false){
			$folder = '../shared/apps/';
			$app_path = '../shared/apps/'.str_replace("\\", '/', $m['namespace']);
			\Nette\Utils\FileSystem::copy('vendor/xepan/epanservices/templates/app_template',$app_path);
			$data=[
				'{name}'				=>$m['name'],
				'{namespace}'			=>$m['namespace'],
				'{_namespace}'			=>str_replace('\\', '_', $m['namespace']),
				'{namespace_fwdslash}'	=>str_replace('\\', '/', $m['namespace']),

			];
			$files=['lib/Initiator.php'];
			foreach ($files as $file) {
				$file_path = $app_path.'/'.$file;
				$file_data = file_get_contents($file_path);
				foreach ($data as $key => $value) {
					$file_data = str_replace($key, $value, $file_data);
				}
				file_put_contents($file_path, $file_data);
			}
		}
	}
}
