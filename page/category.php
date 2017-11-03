<?php

namespace xepan\epanservices;

class page_category extends \xepan\base\Page {
	public $title='Epan Category Management';

	function init(){
		parent::init();
		
		$cat = $this->add('xepan\base\Model_EpanCategory');
		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($cat);

		$crud->grid->removeAttachment();
		$crud->grid->addSno();
		$crud->grid->addPaginator($ipp=25);
		$crud->grid->removeColumn('status');
	}
}
