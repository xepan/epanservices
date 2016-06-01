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
		$this->grid = $this->add('xepan\base\Grid');
		$myEpans = $this->add('xepan\epanservices\Model_Epan');
		$myEpans->addCondition('created_by_id',$this->customer->id);
		$this->grid->setModel($myEpans,['epan_category','xepan_template','created_by','name','status']);
		$publish_button = $this->grid->addColumn('Button','Publish');

		if($_GET['Publish']){
			$new=$this->add('xepan\epanservices\Model_Epan');
			$new->load($_GET['Publish']);
			$new['published']=true;
			$new->save();
			$this->grid->js()->reload()->execute();
			
		}
	}
}