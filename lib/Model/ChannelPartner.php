<?php
namespace xepan\epanservices;

class Model_ChannelPartner extends \xepan\epanservices\Model_Agency{
	public $table_alias = "epanChannelPartner";
	
	function init(){
		parent::init();
		
		$this->addCondition('is_channelpartner',true);

		// $created_by_id = $this->getElement('created_by_id');
		// $created_by_id->display(['form'=>'autocomplete/Basic']);
		// $created_by_id->defaultValue($this->app->employee->id);

		// $assign_to_id = $this->getElement('assign_to_id');
		// $assign_to_id->display(['form'=>'autocomplete/Basic']);
		
		// $user_id = $this->getElement('user_id');
		// $user_id->display(['form'=>'autocomplete/Basic']);

	}
}
