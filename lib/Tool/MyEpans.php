<?php

namespace xepan\epanservices;


class Tool_MyEpans extends \xepan\cms\View_Tool {

	public $options = [
				'login_page'=>'login'
			];	

	function init(){
		parent::init();

		if(!$this->app->auth->isLoggedIn()){
			$this->app->redirect($this->options['login_page']);
			return;
		}

		$customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn();

        // check customer is loaded
        if(!$customer->loaded()){
            $this->add('View_Info')->set('Customer account not found');
            return;            
        }
		
		$new_btn  = $this->add('Button')->set('Create New Epan Web Site')->addClass('btn btn-primary btn-lg');
		$grid = $this->add('Grid');

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$new_epan = $this->add('xepan\epanservices\Model_Epan');

			$form = $p->add('Form');
			$form->setModel($new_epan,['epan_category_id','name']);

			$installable_apps = $this->add('xepan\base\Model_Application')->addCondition('user_installable',true);
			foreach ($installable_apps as $epan_app) {
				$form->addField('CheckBox',$this->app->normalizeName($epan_app['name']),$epan_app['name']);
			}

			if($form->isSubmitted()){

				$apps_selected=[];
				foreach ($installable_apps as $epan_app) {
					if($form[$this->app->normalizeName($epan_app['name'])]){
						$apps_selected[$epan_app->id] = $epan_app['namespace'];
					}
				}
				if($reply = $installable_apps->validateRequirements($apps_selected)){
					if($reply instanceof \jQuery)
						$form->js(null,$reply)->execute();
					else
						$form->js()->univ()->errorMessage($reply)->execute();
				}
				$form->save();
				$grid->js(null,$grid->js()->univ()->cloaseDialog())->reload()->execute();
			}
		});

		$this->app->addStyleSheet('jquery-ui');
		$new_btn->js('click',$this->js()->univ()->frameURL('Create New Epan Web Site',$vp->getURL()));

		$myEpans = $this->add('xepan\epanservices\Model_Epan');
		$myEpans->addCondition('created_by_id',$this->app->auth->model->id);
		
		$grid->setModel($myEpans);
	}
}