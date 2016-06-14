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

class page_epans extends \xepan\base\Page {
	public $title='Epans';

	function init(){
		parent::init();
		
		$crud = $this->add('xepan\hr\CRUD',null,null,['view\epans']);
		$crud->setModel('xepan\epanservices\Epan');
	}
}
