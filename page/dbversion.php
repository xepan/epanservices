<?php

namespace xepan\epanservices;

/**
* 
*/
class page_dbversion extends \xepan\base\Page{

	public $title='xEpan Database Version';
	public $dir='dbversion';
    public $namespace = __NAMESPACE__;

	function init(){
		parent::init();
		// throw new \Exception($this->namespace, 1);
        $m=$this->add('xepan\base\Model_DbVersion',array('dir'=>$this->dir,'namespace'=>'xepan\base'));
  		// foreach ($m as $mi) {
  		// 	var_dump($mi['']);
  		// 	echo "values = " .$mi['code']."<br/>";
  		// }

        $crud = $this->add('xepan\base\CRUD');
        $crud->setModel($m,['code'],['id','name','code']);
        $f=$crud->form;
	    $path=$this->api->pathfinder->base_location->base_path.'/../vendor/'.str_replace("\\","/",$this->namespace)."/".$this->dir;
        
        if($crud->isEditing()){

	        if($f->isSubmitted()){
	        	// if model file name exis the update the file content

	        	if($m['name'] and file_exists($path."/".$m['name'])){
	        		$filename = $m['name'];
	        	}else{
	        		$filename= str_pad($m->max_count+1, 8, "0",STR_PAD_LEFT).".sql";
					// $filename = "dbversion-".($m->max_count+1).".sql";
	        	}
	        	
				$newFileName = $path.'/'.$filename;
				$newFileContent = $m['code'];
				if(file_put_contents($newFileName,$newFileContent)!=false){
					return $f->js(true,$f->js()->reload())->univ()->successMessage("File created (".basename($newFileName).")");
				}else{
					return $f->js(true,$f->js()->reload())->univ()->errorMessage("Cannot create file (".basename($newFileName).")");
				}
	        	$m->save();
	        }

	        if($crud->isEditing('edit')){
	        	$content = file_get_contents($path."/".$crud->model['name']);
	        	$f->getElement('code')->set($content);
	        }
        }

        
        // $crud->grid->addHook('formatRow', function($g)use($dir){
        //     $g->current_row['name']=$g->current_row['name']."sql";
        // });
	}
}