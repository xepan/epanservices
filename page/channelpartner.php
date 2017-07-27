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

class page_channelpartner extends \xepan\base\Page {
	public $title='Channel Partner';

	function init(){
		parent::init();
		
		$model = $this->add('xepan\epanservices\Model_ChannelPartner');
		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($model,['first_name','user_id']);
		
	}
}
