<?php

namespace xepan\epanservices;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_epanservices';

	function init(){
		parent::init(); 

    }


    function setup_admin(){
        $this->routePages('xepan_epanservices');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('../vendor/xepan/epanservices/')
        ;
		if($this->app->getConfig('multi-xepans',false)){
            $this->app->side_menu->addItem(['Epans','icon'=>' fa fa-globe','badge'=>[1 ,'swatch'=>' label label-primary pull-right']],'xepan_epanservices_epans')->setAttr(['title'=>'Epans']);
        }

    	return $this;
    }

    function setup_frontend(){
        $this->routePages('xepan_epanservices');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('vendor/xepan/epanservices/')
        ;

        $epan_model = $this->add('xepan/epanservices/Model_Epan');
        $this->app->addHook('order_placed',[$epan_model,'createFromOrder']);
        $this->app->addHook('invoice_paid',[$epan_model,'invoicePaid']);

        $this->app->exportFrontEndTool('xepan\epanservices\Tool_EpanTrial','Epan Trial');
    	return $this;
    }


    function resetDB($write_sql=false){
    }
}