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

class page_test extends \xepan\base\Page {
	public $title='Test';

	function init(){
		parent::init();
		
		// $in = $this->add('xepan\commerce\Model_SalesInvoice')->load(6263);

		// $epan = $this->add('xepan\epanservices\Model_Epan');
		// $epan->invoicePaid($this->app,$in);
	}
}
