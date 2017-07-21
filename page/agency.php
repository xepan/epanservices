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
		
		$crud = $this->add('xepan\hr\CRUD');

		$crud->setModel('xepan\commerce\Store_Warehouse');
	}
}
