<?php

namespace xepan\epanservices;

class Tool_AgencyPanel extends \xepan\cms\View_Tool {
	public $options = [];
	public $agency;
	function init(){
		parent::init();
		
		$url = "{$_SERVER['HTTP_HOST']}";
        $this->domain = str_replace('www.','',$this->app->extract_domain($url))?:'www';
        $this->sub_domain = str_replace('www.','',$this->app->extract_subdomains($url))?:'www';
		
		if($this->owner instanceof \AbstractController) return;

		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/epanservices/templates/css/agency.css" />');

		$this->agency = $agency = $this->add('xepan\epanservices\Model_Agency');

		$agency->addExpression('total_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$m->getElement('id'));
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_trial_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$m->getElement('id'));
			$a->addCondition('status','Trial');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_paid_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$m->getElement('id'));
			$a->addCondition('status','Paid');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_grace_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$m->getElement('id'));
			$a->addCondition('status','Grace');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_expired_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$m->getElement('id'));
			$a->addCondition('status','Expired');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->loadLoggedIn();
		if(!$agency->loaded()){
			$this->add('View')->set('alert alert-danger')->set('you are not the agency');
			return;
		}
		
		$this->menubar();

		$view = $this->app->stickyGET('view')?:"dashboard";
		$this->container = $this->add('View')->addClass('container agency-panel-conatiner');

		switch ($view) {
			case "newepan":
				$this->container->add('xepan\epanservices\View_CreateEpan');
				break;
			case "dashboard":
				$this->dashboard();
				break;
		}
		// $this->add('View')->set('Expired in next 15 days');
		// $epan = $this->add('xepan\base\Model_Epan');
		// $g = $this->add('CRUD');
		
		// $g->setModel($epan);
		// $g->grid->addQuickSearch(['name']);
	
	}

	function dashboard(){

		$col = $this->container->add('Columns')->addClass('row margin-20');
		$col1 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col2 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col3 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col4 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
				
		$col1->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-success',
											'heading'=>'Total Epan',
					                    	'content'=>$this->agency['total_epan'],
											'footer'=>' '
										]);

		$col2->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-info',
											'heading'=>'Trial',
											'content'=>$this->agency['total_trial_epan'],
											'footer'=>' '
										]);

		$col3->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-success',
											'heading'=>'Paid',
											'content'=>$this->agency['total_paid_epan'],
											'footer'=>' '
										]);

		$col4->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-danger',
											'heading'=>'Expired',
											'content'=>$this->agency['total_expired_epan'],
											'footer'=>' '
										]);

		$a = $this->container->add('xepan\epanservices\Model_Epan');
		$a->addCondition('created_by_id',$this->agency->id);
		$a->addCondition([['is_template',false],['is_template',null]]);
		$a->setOrder('id','desc');
		$this->container->add('View')->setElement('h2')->set("Your Current Epan");
		$grid_epan = $this->container->add('xepan\hr\Grid');
		$grid_epan->setModel($a,['name','status','is_published','created_at']);
		$grid_epan->addColumn('visit_site');

		$btn_class = [
					'Trial'=>'btn btn-info',
					'Paid'=>'btn btn-success',
					'Expired'=>'btn btn-success',
					'Grace'=>'btn btn-warning',
				];

		$grid_epan->addHook('formatRow',function($g)use($btn_class){
			$g->current_row_html['is_published'] = $g->model['is_published']?"<i class='fa fa-check-square-o'>&nbsp;</i>Yes":"No";
			$g->current_row_html['visit_site'] = '<a target="_blank" class="'.$btn_class[$g->model['status']].'" href="http://www.'.$g->model['name'].'.'.$this->domain.'" ><i class="fa fa-globe">&nbsp;</i>Visit Site</a>';
		});

		$grid_epan->addPaginator($ipp=5);
	}

	function menubar(){
		$menu = [
				['key'=>$this->app->url('agency-dashboard',['view'=>'dashboard']),'name'=>'Dashboard'],
				['key'=>$this->app->url('agency-dashboard',['view'=>'newepan']), 'name'=>'New Epan'],
			];

		$this->complete_lister = $cl = $this->add('CompleteLister',null,null,['view/agencymenu']);
		$cl->setSource($menu);
		// $page = $this->app->url('agency-dashboard',['view'=>'dashboard']);
		$view = $_GET['view'];
		if(!$view){
			$view = "dashboard";
		}
		$page = "view=".$view;

		$cl->addHook('formatRow',function($g)use($page){
			if(strpos($g->model['key'], $page)){
				$g->current_row_html['active_menu'] = "active";
			}else{
				$g->current_row_html['active_menu'] = "deactive";
			}
		});

		$cl->template->trySet('agency_name',$this->agency['name']);
		$cl->template->trySet('agency_dp',($this->agency['image']?:"shared/apps/xavoc/mlm/templates/img/profile.png"));
	}

}