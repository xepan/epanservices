<?php


namespace xepan\epanservices;

class View_MyEpans extends \View{

	function init(){
		parent::init();
		
		$this->addClass('epan-service-grid');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();

		$epan = $this->add('xepan\epanservices\Model_Epan');
		$epan->addCondition('created_by_id',$customer->id);
		$epan->setOrder('created_at','desc');

		$this->add('View')->set('My Epans (Websites / Online Stores / ERP Installations)')->addClass(' panel panel-heading xepan-grid-heading');
		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($epan,['name','status','created_at','expiry_date']);
		$grid->addColumn('Button','visit',['descr'=>'Visit Website','button_class'=>'btn btn-primary']);
		$grid->addColumn('Button','live_edit',['descr'=>'Edit Website','button_class'=>'btn btn-primary']);
		$grid->addColumn('Button','admin_login',['descr'=>'Admin Login','button_class'=>'btn btn-primary']);
		$grid->addColumn('Button','detail',['descr'=>'Detail','button_class'=>'btn btn-success']);
		$grid->addPaginator($ipp=10);
		$grid->addSno();

		if(($live_edit_id = $_GET['live_edit']) OR ($live_edit_id = $_GET['admin_login'])){
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
	        
	        if($_GET['live_edit'])
				$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.$this->app->pathfinder->base_location->base_url,['access_token'=>$token]),'LiveEdit')->execute();
						
	        if($_GET['admin_login'])
				$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.$this->app->pathfinder->base_location->base_url."admin"),'adminpanel')->execute();

		}

		if($_GET['visit']){
			$epan->load($_GET['visit']);
			$this->url = $url = "{$_SERVER['HTTP_HOST']}";        
	        $this->protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	        $this->domain = $domain = str_replace('www.','',$this->app->extract_domain($url))?:'www';
	        $this->sub_domain = $sub_domain = str_replace('www.','',$this->app->extract_subdomains($url))?:'www';
	        
			$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.$this->app->pathfinder->base_location->base_url),'YourWebsite')->execute();
		}

		if($detail_id = $_GET['detail']){
			$this->js(true)->univ()->location($this->app->url('epan-detail',['selected'=>$_GET['detail']]))->execute();
		}
		
	}
}