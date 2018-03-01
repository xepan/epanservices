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

class page_epans extends \xepan\base\Page {
	public $title='Epans';

	function init(){
		parent::init();

		$this->updateEpansDB = $this->add('VirtualPage');
		$this->updateEpansDB->set([$this,'updateEpansDB']);

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
				$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.str_replace('/admin/','',(string)$this->app->pathfinder->base_location->base_url),['access_token'=>$token]),'LiveEdit')->execute();
						
	        if($_GET['admin_login'])
				$this->js()->univ()->newWindow($this->app->url($this->protocol.$epan['name'].".".$this->domain.$this->app->pathfinder->base_location->base_url."admin"),'adminpanel')->execute();

		}

		$epan_model= $this->add('xepan\epanservices\Model_Epan');
		$epan_model->add('xepan\epanservices\Controller_SideBarStatusFilter');
		
		$crud = $this->add('xepan\hr\CRUD',null,null,null);
		$crud->setModel($epan_model,
				['name','created_by_id','created_at','expiry_date','status','is_template','aliases'],
				['name','created_by','created_at','expiry_date','status','is_template','is_published']
			)
			->addCondition([['is_template',false],['is_template',null]])
			->setOrder('created_at','desc')
			;
		
		$crud->grid->addQuickSearch(['name']);

		$crud->grid->add('Button',null,'grid_buttons')->addClass('btn btn-primary')->set('Update DB')->js('click',$this->js()->univ()->frameURL($this->updateEpansDB->getURL()));

		$crud->grid->addColumn('Button','live_edit',['descr'=>'Frontend Edit','button_class'=>'btn btn-primary']);
		$crud->grid->removeColumn('status');
		$crud->grid->addFormatter('name','template')->setTemplate('<a href="http://{$name}.epan.in" target="_blank">{$name}</a>','name');
		$crud->noAttachment();
	}

	function updateEpansDB($page){
		$page->add('View_Console')->set(function($x){
			$epans = $this->add('xepan\base\Model_Epan');

			$url=parse_url($this->app->url()->absolute());

	        $scheme   = isset($url['scheme']) ? $url['scheme'] . '://' : '';
	        $host     = isset($url['host']) ? $url['host'] : '';
	        $port     = isset($url['port']) ? ':' . $url['port'] : '';
	        $user     = isset($url['user']) ? $url['user'] : '';
	        $pass     = isset($url['pass']) ? ':' . $url['pass']  : '';
	        $pass     = ($user || $pass) ? "$pass@" : '';
	        $path     = isset($url['path']) ? $url['path'] : '';

	        if (substr($path,-1) != '/') {
	            $path.='/';
	        }

			foreach ($epans as $e) {
				try{
					file_get_contents($scheme.$user.$pass.$e['name'].'.'.$host.$port.$path);				
				}catch(\Exception $err){
					$x->err("Error: " . $scheme.$user.$pass.$e['name'].'.'.$host.$port.$path);
					continue;
				}
				$x->out("Done: " . $scheme.$user.$pass.$e['name'].'.'.$host.$port.$path);
			}
		});
		$page->add('Text')->set('After console');
	}
}
