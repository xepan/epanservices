<?php

namespace xepan\epanservices;

class Tool_AgencyPanel extends \xepan\cms\View_Tool {
	public $options = [];

	function init(){
		parent::init();	

		$col = $this->add('Columns')->addClass('row');
		$col1 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col2 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col3 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');
		$col4 = $col->addColumn('3')->addClass('col-md-3 col-sm-3 col-lg-3 col-xs-3');

		$col1->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-success',
											'heading'=>'Total Epan',
					                    						'content'=>'100',
											'footer'=>' '
										]);
		
		$col2->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-primary',
											'heading'=>'Pending Approvals',
											'content'=>'100',
											'footer'=>' '
										]);

		$col3->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-danger',
											'heading'=>'Trial',
											'content'=>'20',
											'footer'=>' '
										]);

		$col4->add('xepan\epanservices\View_Panel',[
											'theme_class'=>'panel-info',
											'heading'=>'Total Amount Pending',
											'content'=>'5',
											'footer'=>' '
										]);


		$this->add('View')->set('Expired in next 15 days');
		$epan = $this->add('xepan\base\Model_Epan');
		$g = $this->add('CRUD');
		
		$g->setModel($epan);
		$g->grid->addQuickSearch(['name']);
	
	}

}