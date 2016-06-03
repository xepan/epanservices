<?php

namespace xepan\epanservices;

class Tool_EpanTrial extends \xepan\cms\View_Tool {
	public $options = [
		'login_page'=>'login'
	];

	function init(){
		parent::init();
		$this->app->addStyleSheet('jquery-ui');

		$form = $this->add('Form',null,'form');
		$form->setLayout('view\tool\form\epantrial');
		$form->addField('epan_name');
		$form->addSubmit('Create');

		if($form->isSubmitted()){
			if(!$this->app->auth->isLoggedIn()){
				$this->app->redirect($this->options['login_page']);
				return;
			}

			$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
	        $customer->loadLoggedIn();

    	    if(!$customer->loaded()){
        	    $this->add('View_Info')->set('Customer account not found');
            	return;            
        	}

        	$epan_name = $form['epan_name'];
        	$myEpans = $this->add('xepan\epanservices\Model_Epan');
        	$myEpans->addCondition('name',strtolower($epan_name));
        	$myEpans->tryLoadAny();

        	if($myEpans->loaded()){
        		$this->displayError('from_phone','from_phone is required');
        	}

		}
	}

	function defaultTemplate(){
		return['view\tool\epantrial'];
	}
}