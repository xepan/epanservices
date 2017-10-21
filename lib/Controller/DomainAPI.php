<?php

namespace xepan\epanservices;

class Controller_DomainAPI extends \AbstractController {

	public $test_mode = false;
	public $next_test_return = false;
	public $persistent_next_test_return = false;
	public $pass_all=false;

	function init(){
		parent::init();
	}

	function checkAvailability($domain_array=[]){
		if($this->test_mode) return $this->getTestReturn();

	}

	function registerDomain($contact_info){
		if($this->test_mode) return $this->getTestReturn();

	}

	function addChildNameServer($domain,$child_nameserver_name,$ip_address_array){
		if($this->test_mode) return $this->getTestReturn();

	}

	function deleteChildNameServer($child_nameserver_name){
		if($this->test_mode) return $this->getTestReturn();

	}

	function modifyChildNameServer($domain,$child_nameserver_name,$ip_address_array){
		if($this->test_mode) return $this->getTestReturn();
		
	}

	function testMode($tm){
		$this->test_mode=$tm;
	}

	function setTestReturn($ret,$persstent=false){
		$this->next_test_return = $ret;
		$this->persistent_next_test_return=$persstent;
	}

	function getTestReturn(){
		$val=$this->next_test_return;
		if(!$this->persistent_next_test_return) $this->next_test_return=null;
		return $val;
	}
}