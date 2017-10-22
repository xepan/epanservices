<?php

namespace xepan\epanservices;

class Model_DomainDetails extends \xepan\base\Model_Table{
	public $table = "domain_details";

	function init(){
		parent::init();
		
		$this->hasOne('xepan\epanservices\Epan','park_for_epan_id');
		$this->hasOne('xepan\commerce\Customer','created_by_id');

		$this->addField('name')->caption('Domain Name');
		$this->addField('registration_detail')->type('text');
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('last_renew_at')->type('datetime');
		$this->addField('expire_date');

		$this->addField('vendor')
			->system(true)
			->defaultValue($this->app->getConfig('DomianVendor','Generic'));
		
	}
}