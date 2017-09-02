<?php

namespace xepan\epanservices;

class Tool_CustomerDashboard extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		// $live_edit_id = $this->app->stickyGET('live_edit');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();

		$epan = $this->add('xepan\epanservices\Model_Epan');
		$epan->addCondition('created_by_id',$customer->id);

		$grid = $this->add('Grid');
		$grid->setModel($epan,['name','status']);
		$grid->addColumn('Button','live_edit','Live Edit');
		$grid->addColumn('Button','detail','Detail');

		if($live_edit_id = $_GET['live_edit']){
			$epan = $this->add('xepan\base\Model_Epan')->tryLoadBy('id',$live_edit_id);
			$token = md5(uniqid());
			$this->add('xepan\epanservices\Controller_RemoteEpan')
				->setEpan($epan)
				->do(function($app)use($token){
					$app->add('xepan\base\Model_User')
						->tryLoadAny()
						->set('access_token',$token)
						->set('access_token_expiry',date('Y-m-d H:i:s',strtotime($app->now.' +10 seconds')))
						->save();
				});
			$this->url = $url = "{$_SERVER['HTTP_HOST']}";        
	        $this->protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	        $this->domain = $domain = str_replace('www.','',$this->app->extract_domain($url))?:'www';
	        $this->sub_domain = $sub_domain = str_replace('www.','',$this->app->extract_subdomains($url))?:'www';
	        
			$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.$this->app->pathfinder->base_location->base_url,['access_token'=>$token]),'LiveEdit')->execute();
		}

		if($detail_id = $_GET['detail']){
			$this->js(true)->univ()->location($this->app->url('epan-detail',['selected'=>$_GET['detail']]))->execute();
		}

	}
}