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

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn();

        // check customer is loaded
        if(!$customer->loaded()){
            $this->add('View_Info')->set('Customer account not found');
            return;            
        }

		$this->showMyEpans();
		
	}

	function showMyEpans(){

		$this->app->addStyleSheet('jquery-ui');

		$this->new_btn  = $this->add('Button')->set('Create New Epan Web Site')->addClass('btn btn-primary btn-lg');
		$this->grid = $this->add('Grid');		
		$this->new_btn->js('click',$this->js()->reload(['action'=>'createNew']));

		$myEpans = $this->add('xepan\epanservices\Model_Epan');
		$myEpans->addCondition('created_by_id',$this->customer->id);
		
		$this->grid->setModel($myEpans);
	}
}