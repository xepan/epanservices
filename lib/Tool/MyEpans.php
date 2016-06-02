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
		$this->grid = $this->add('xepan\base\Grid');
		$myEpans = $this->add('xepan\epanservices\Model_Epan');
		$myEpans->addCondition('created_by_id',$this->customer->id);
		$this->grid->setModel($myEpans,['epan_category','xepan_template','created_by','name','status']);
		
		$this->grid->add('VirtualPage')
       		 ->addColumn('Publish')
       		 ->set(function($page){
				$id = $_GET[$page->short_name.'_id'];
				$new=$page->add('xepan\epanservices\Model_Epan');
				$new->load($id);

				
			if($new['is_published']){
				return $page->add('View_Info')->set('Already Published');
			}

			$form = $page->add('Form');
			$form->addField('name');
			$form->addSubmit('Submit');

			if($form->isSubmitted()){
				$new['name'] = $form['name'];
				$new['is_published']=true;	
				
				$new->createFolder($new);
				// $new->userAndDatabaseCreate();
				// $new->save();	    		
			}
    	});


       $this->grid->add('VirtualPage')
       		 ->addColumn('UnPublish')
       		 ->set(function($page){
				$id = $_GET[$page->short_name.'_id'];
				$new=$page->add('xepan\epanservices\Model_Epan');
				$new->load($id);

				
			if(!$new['is_published']){
				return $page->add('View_Info')->set('Already UnPublished');
			}
			
			$form = $page->add('Form');
			$form->addSubmit('Submit');
			if($form->isSubmitted()){
				$new['is_published']=null;	
				$new->save();	    			
			}
			
    	});		 
	}
}