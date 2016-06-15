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

        $my_account = $this->add('xepan\commerce\Tool_MyAccount',null,'account',null);

		$this->showMyEpans();
		$this->showMyTemplates();
		
	}

	function showMyEpans(){
		
		$this->app->addStyleSheet('jquery-ui');
		$this->grid = $this->add('xepan\base\Grid',null,'epan');
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
				$form->addField('name')->setAttr(['placeholder'=>'Your website name']);
				$form->layout->add('View',null,'domain')->set('.epan.in');
			}

			
			$form->addSubmit('Publish')->addClass('btn btn-block btn-primary');

			if($form->isSubmitted()){
				if($form->hasElement('name')){
					if($form['name'] ==''){
		        		return $form->error('name','You cannot leave website name empty');
        			}
        			if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $form['name']))
					{	
		        		return $form->error('name','Website name cannot contain special characters');
					}
					if (preg_match('/\s/', $form['name']))
					{	
		        		return $form->error('name','Website name cannot contain spaces');
					}

					$new['name'] = $form['name'];
					$new['is_published']=true;
					
					try{
						$this->api->db->beginTransaction();
						$new->createFolder($new);
						$new->userAndDatabaseCreate();
						$new->save();	 
						$this->api->db->commit();
					}catch(\Exception $e){
						$this->api->db->rollback();
						$this->swipeEverything($new['name']);
            			throw $e;
					}

					return $form->js(true,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Epan Published')->execute();	    			
				}
				else{
					$new['is_published']=true;
					$new->save();
					$js_action = [
							$form->js()->closest('.dialog')->dialog('close'),
							$this->js(true)->reload()	
							];	    		
					return $form->js(true,$js_action)->univ()->successMessage('Epan Published')->execute();	    			
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

	function showMyTemplates(){

		$customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn();

		$template_grid = $this->add('xepan\base\Grid',null,'my_template',['view\tool\mytemplate']);
		$template_grid->setModel('xepan\epanservices\Model_MyTemplates');
		
		$template_grid->addHook('formatRow',function($g){			
			$item = $this->add('xepan\commerce\Model_Item')->load($g->model->id);
			$g->current_row_html['preview_image'] =  $item['first_image'];					     				     		
			$g->current_row_html['preview_url'] =  'http://'.$item['sku'].'.epan.in';								     				     							     				     		
     	});
     	
		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$item_id = $this->app->stickyGET('item_id');

			$item = $p->add('xepan\commerce\Model_Item')->load($item_id);
			$template_name = $item['sku'];

			if(!file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$template_name))){
				throw new \Exception('Template not found: Folder do not exist in websites.');	
			}

			$customer = $p->add('xepan\commerce\Model_Customer');
        	$customer->loadLoggedIn();

			$epan = $p->add('xepan\epanservices\Model_Epan');
			$epan->addCondition('created_by_id',$customer->id);			

			$form = $p->add('Form');
			$form->addField('xepan\commerce\DropDown','epan')->setModel($epan);
			
			if($form->isSubmitted()){
				$model_epan = $p->add('xepan\epanservices\Model_Epan')->load($form['epan']);
				$folder_name = $model_epan['name'];

				if(!$model_epan->loaded())
					throw new \Exception("Epan model not loaded");
				
				// NUMBER OF TEMPLATES (NUMBER OF THIS TEMPLATE PURCHASED?)
				$epan_template = $this->add('xepan\epanservices\Model_MyTemplates');
				$epan_template->addCondition('id',$item_id);
				$template_count = $epan_template->count()->getOne();
				
				// NUMBER OF EPANS ON WHICH THIS TEMPLATE IS APPLIED
				$applied_count_epan = $this->add('xepan\epanservices\Model_Epan');
				$applied_count_epan->addCondition('created_by_id',$customer->id);
				$applied_count_epan->addCondition('xepan_template_id',$item_id);
				$epan_count = $applied_count_epan->count()->getOne();
				
				// NO MORE TEMPLATES LEFT TO APPLY
				if($epan_count == $template_count)
					return $form->error('epan','You have already applied this template. Please buy or use any other template');
										

				if(file_exists(realpath($this->app->pathfinder->base_location->base_path.'/websites/'.$folder_name.'/www'))){													
					$fs = \Nette\Utils\FileSystem::delete('./websites/'.$folder_name.'/www');
				}

				$fs = \Nette\Utils\FileSystem::createDir('./websites/'.$folder_name.'/www');
				$fs = \Nette\Utils\FileSystem::copy('./websites/'.$template_name,'./websites/'.$folder_name.'/www',true);
				
				$model_epan['xepan_template_id'] = $item_id;
				$model_epan->save();

				return $form->js()->univ()->successMessage('Template Applied')->execute();
			}
 		});
		
		$template_grid->on('click','.xepan-change-template',function($js,$data)use($vp){			
			return $js->univ()->dialogURL("APPLY NEW TEMPLATE",$this->api->url($vp->getURL(),['item_id'=>$data['id']]));
		});

	}


	function defaultTemplate(){
		return ['view\tool\myepans'];
	}
}