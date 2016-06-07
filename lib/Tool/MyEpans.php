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
       		 ->addColumn('Publish','PUBLISH YOUR EPAN',['descr'=>'Publish'])
       		 ->set(function($page){
				$id = $_GET[$page->short_name.'_id'];
				$new=$page->add('xepan\epanservices\Model_Epan');
				$new->load($id);

				
			
			
			if($new['is_published']){
				$view = $page->add('View',null,null,['view\tool\alreadypulished-unpublished']);
				return $view->template->trySet('msg','This Epan is already Published');	
			}

			$epan_name = $new['name'];
			$x = $this->api->db->dsql()->expr("SELECT IF(EXISTS (SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$epan_name'), '1','0')")->getOne(); 
			$form = $page->add('Form');
			$form->setLayout('view\tool\form\un-pub');
			
			if(!file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$epan_name)) && !$x){								
				$form->addField('name')->setAttr(['placeholder'=>'Epan Name']);
			}

			
			$form->addSubmit('Publish')->addClass('btn btn-block btn-primary');

			if($form->isSubmitted()){
				if($form->hasElement('name')){
					$new['name'] = $form['name'];
					$new['is_published']=true;
					
					$new->createFolder($new);
					$new->userAndDatabaseCreate();
					$new->save();	    		
					return $form->js(true,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Epan Published')->execute();	    			
				}
				else{
					$new['is_published']=true;
					$new->save();	    		
					return $form->js(true,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Epan Published')->execute();	    			
				}
			}
    	});


       $this->grid->add('VirtualPage')
       		 ->addColumn('UnPublish','UNPUBLISH YOUR EPAN',['descr'=>'UnPublish'])
       		 ->set(function($page){
				$id = $_GET[$page->short_name.'_id'];
				$new=$page->add('xepan\epanservices\Model_Epan');
				$new->load($id);

				
			if(!$new['is_published']){
				$view = $page->add('View',null,null,['view\tool\alreadypulished-unpublished']);
				return $view->template->trySet('msg','This Epan is already UnPublished');	
			}
			
			$form = $page->add('Form');
			$form->setLayout('view\tool\form\un-pub');

			$form->addSubmit('Unpublish')->addClass('btn btn-block btn-primary');
			if($form->isSubmitted()){
				$new['is_published']=null;	
				$new->save();

				return $form->js(true,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Epan Unpublished')->execute();	    			
			}
			
    	});		 
	}
}