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

class page_domaincheck extends \xepan\base\Page {

	public $allow_frontend = true;
	function init(){
		parent::init();
		
		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();
		if(!$customer->loaded()) throw new \Exception("customer not found");

		$domain_name = $this->app->stickyGet('domain_name');
		$epan_id = $this->app->stickyGet('current_epan_id');
		$domain_tld = $this->app->stickyGet('tld');

		$epan_model = $this->add('xepan\epanservices\Model_Epan');
		$epan_model->tryLoad($epan_id);
		if(!$epan_model->loaded()){
			$this->add('View')->set('session out, epan not found');
			return;			
		}

		if(!$domain_name){
			$this->add('View')->set('domain name not found');
			return;
		}

		if(!$domain_tld){
			$this->add('View')->set('TLD not found');
			return;
		}

		$qualified_domain = strtolower($domain_name.$domain_tld);

		$domain = $this->add('xepan\epanservices\Controller_DomainAPI_'.$this->app->getConfig('DomianVendor','Generic'));
		
		// temporary for test mode
		$domain->testMode(true);
		$domain->setTestReturn(true);
		$result = $domain->checkAvailability([$qualified_domain]);

		if(!$result){
			$this->add('View')->addClass('alert alert-danger')->set($qualified_domain." is not available");
			return;
		}

		$this->add('View')->addClass('alert alert-success')->set($qualified_domain." is available");
		$form = $this->add('Form');
		$form->addField('xepan\base\DropDown','year')->setValueList([1=>1,2=>2,3=>3,4=>4,5=>5]);
		$form->addSubmit('Purchase Now')
			->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$epan_model->purchaseDomain($qualified_domain,$domain_tld,$form['year'],$customer);
		}

	}
}
