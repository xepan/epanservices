<?php

namespace xepan\epanservices;

class Tool_AgencyPanel extends \xepan\cms\View_Tool {
	public $options = [];
	public $agency;
	function init(){
		parent::init();	

		if($this->owner instanceof \AbstractController) return;

		$this->agency = $agency = $this->add('xepan\epanservices\Model_Agency');

		$agency->addExpression('total_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$this->agency->id);
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_trial_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$this->agency->id);
			$a->addCondition('status','Trial');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_paid_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$this->agency->id);
			$a->addCondition('status','Paid');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_grace_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$this->agency->id);
			$a->addCondition('status','Grace');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->addExpression('total_expired_epan')->set(function($m,$q){
			$a = $m->add('xepan\epanservices\Model_Epan');
			$a->addCondition('created_by_id',$this->agency->id);
			$a->addCondition('status','Expired');
			return $q->expr('IFNULL([0],0)',[$a->count()]);
		});

		$agency->loadLoggedIn();
		if(!$agency->loaded()){
			$this->add('View')->set('alert alert-danger')->set('you are not the agency');
			return;
		}

		$col = $this->add('Columns')->addClass('row');
		$col1 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col2 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col3 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col4 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
				
		$col1->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-success',
											'heading'=>'Total Epan',
					                    	'content'=>$agency['total_epan'],
											'footer'=>' '
										]);

		$col2->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-info',
											'heading'=>'Trial',
											'content'=>$agency['total_trial_epan'],
											'footer'=>' '
										]);

		$col3->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-success',
											'heading'=>'Paid',
											'content'=>$agency['total_paid_epan'],
											'footer'=>' '
										]);

		$col4->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-danger',
											'heading'=>'Expired',
											'content'=>$agency['total_expired_epan'],
											'footer'=>' '
										]);
		
		
		$menu_html = '<a class="btn btn-default" href="'.$this->app->url(null,['view'=>'trialepan']).'">New Epan</a>';
		$this->add('View')->setHtml($menu_html);

		$view = $this->app->stickyGET('view');
		switch ($view) {
			case "trialepan":
				$this->add('xepan\epanservices\View_CreateEpan');
				break;
		}
		// $this->add('View')->set('Expired in next 15 days');
		// $epan = $this->add('xepan\base\Model_Epan');
		// $g = $this->add('CRUD');
		
		// $g->setModel($epan);
		// $g->grid->addQuickSearch(['name']);
	
	}

}