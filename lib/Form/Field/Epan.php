<?php

namespace xepan\epanservices;

class Form_Field_Epan extends  \xepan\base\Form_Field_Basic {

	public $id_field=null;
	public $title_field=null;

	function init(){
		parent::init();
	}

	function setIdField($id_field){
		$this->id_field = $id_field;
	}

	function setTitleField($title_field){
		$this->title_field = $title_field;
	}

	function includeAll(){
		$this->include_status=null;
	}

	function includeStatus($status){
		$this->include_status = $status;
	}


	function recursiveRender(){
		$this->setModel('xepan\epanservices\Model_Epan',$this->id_field, $this->title_field);
		parent::recursiveRender();
	}
}