<?php
namespace xepan\epanservices;

class Model_Agency extends \xepan\commerce\Model_Customer{

	public $status = ['Active','InActive'];
	public $actions = [
					'Active'=>['view','edit','delete','deactivate','communication'],
					'InActive'=>['view','edit','delete','activate','communication']
					];

	function init(){
		parent::init();

		$agency_j = $this->join('agency.contact_id');

		$agency_j->hasOne('xepan\epanservices\ChannelPartner','channelpartner_id');
		$agency_j->addField('is_channelpartner')->type('boolean')->defaultValue(0);
	}
}
