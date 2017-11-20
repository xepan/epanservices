<?php

namespace xepan\epanservices;

class Tool_CustomerMenu extends \xepan\cms\View_Tool {
	public $options = [

	];

	function init(){
		parent::init();

		if($this->owner instanceof \AbstractController) return;

		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/epanservices/templates/css/agency.css" />');
		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer')
							->addCondition('user_id',$this->app->auth->model->id);
		$this->customer->loadLoggedIn();

		// update your profile first		
		if( $this->app->page != "customer-setting" && (!$customer['country_id'] OR !$customer['state_id'] OR !$customer['city'] OR !$customer['pin_code'] OR !$customer['address'] OR !$customer['first_name']) ){
			$this->app->redirect($this->app->url('customer-setting',['profile'=>'incomplete']));
		}

		if($customer['country_id'])
			$this->app->country = $this->add('xepan\base\Model_Country')->load($customer['country_id']);
		if($customer['state_id'])
			$this->app->state = $this->add('xepan\base\Model_State')->load($customer['state_id']);

		$editor = $this->add('xepan\cms\Model_User_CMSEditor');
		$editor->addCondition('user_id',$this->app->auth->model->id);
		$editor->tryLoadAny();

		if(!$this->customer->loaded() && !$editor->loaded())
			$this->app->redirect($this->app->url('login',['layout'=>'new_registration']));

		$menu = [
				['key'=>'customer-dashboard','name'=>'Dashboard'],
				['key'=>'new-account', 'name'=>'New Epan'],
				['key'=>'customer-orderhistory', 'name'=>'Order History'],
				['key'=>'customer-setting', 'name'=>'Setting'],
				['key'=>'customer-download', 'name'=>'Download']
			];
		$this->submenu_list = [
					'customer-setting'=>[
								'index.php?page=customer-setting&action=profile'=>'Profile',
								'index.php?page=customer-setting&action=changepassword'=>'Change Password',
							]
					];

		$this->complete_lister = $cl = $this->add('CompleteLister',null,null,['view/customermenu']);
		$cl->setSource($menu);

		$page = $this->app->page;
		$cl->addHook('formatRow',function($g)use($page){
			$submenu_html = "";
			$submenu_class = "";

			if(isset($this->submenu_list[$g->model['key']])){
				$submenu_html = '<ul class="dropdown-menu">';
				foreach ($this->submenu_list[$g->model['key']] as $s_key => $s_value) {
					$submenu_html .= '<li><a class="dropdown-item" href="'.$s_key.'">'.$s_value.'</a></li>';
				}
				$submenu_html .= '</ul>';
				$submenu_class = "dropdown";

				$g->current_row_html['list'] = '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'.$g->model['name'].' <span class="caret"></span></a>';
			}else{
				$g->current_row_html['list'] = '<a href="'.$g->model['key'].'">'.$g->model['name'].'</a>';
			}

			if($g->model['key'] == $page)
				$g->current_row_html['active_menu'] = "active ".$submenu_class;
			else
				$g->current_row_html['active_menu'] = "deactive ".$submenu_class;
			
			$g->current_row_html['submenu'] = $submenu_html;

		});

		$cl->template->trySet('customer_name',$this->customer['name']);
		$cl->template->trySet('customer_dp',($this->customer['image']?:"vendor/xepan/epanservices/templates/images/profile.png"));	
		
		$this->js(true)->_selector('.dropdown-toggle')->dropdown();		
	}
}