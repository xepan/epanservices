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
        $this->app->side_menu->addItem([' DB Version Generate','icon'=>' fa fa-edit'],'xepan_epanservices_dbversion')->setAttr(['title'=>'DB Version Generate ']);

    	return $this;
    }

    function setup_pre_frontend(){
        $this->routePages('xepan_epanservices');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('vendor/xepan/epanservices/')
        ;        

        return $this;
    }

    function setup_frontend(){

        $epan_model = $this->add('xepan/epanservices/Model_Epan');
        $this->app->addHook('order_placed',[$epan_model,'createFromOrder']);
        $this->app->addHook('invoice_paid',[$epan_model,'invoicePaid']);

        $this->app->exportFrontEndTool('xepan\epanservices\Tool_EpanTrial','Epan Trial');
        $this->app->exportFrontEndTool('xepan\epanservices\Tool_AfterCreation','Epan Trial');

        $this->app->addHook('cron_executor',function($app){
            $now = \DateTime::createFromFormat('Y-m-d H:i:s', $this->app->now);
            echo "Running All Epans Cron <br/>";
            var_dump($now);
            if($this->app->current_website_name !='www'){
                echo "leaving ". $this->app->current_website_name. '<br/>';
                return;
            }
            $job1 = new \Cron\Job\ShellJob();
            $job1->setSchedule(new \Cron\Schedule\CrontabSchedule('*/1 * * * *'));
            if(!$job1->getSchedule() || $job1->getSchedule()->valid($now)){
                foreach ($this->add('xepan\base\Epan')->addCondition('id','<>',$this->app->epan->id) as $other_epans) {
                    // wget all epans cron page with cut_page=true
                    $command = 'wget https://'. $other_epans['name'].'.epan.in?page=xepan_base_cron&cut_page=true';
                    shell_exec($command);
                }
            }
        });

    	return $this;
    }


    function resetDB($write_sql=false){
    }
}