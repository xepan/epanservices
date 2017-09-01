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

        if(!$this->app->isAjaxOutput() && !$this->app->getConfig('hidden_xepan_epanservices',false)){
            $m = $this->app->top_menu->addMenu('Epans');
            $m->addItem(['Epans','icon'=>'fa fa-sitemap'],'xepan_epanservices_epans');
            $m->addItem(['Templates','icon'=>'fa fa-sitemap'],'xepan_epanservices_epantemplates');
            $m->addItem(['Agency','icon'=>'fa fa-sitemap'],'xepan_epanservices_agency');
            $m->addItem(['Channel Partner','icon'=>'fa fa-sitemap'],'xepan_epanservices_channelpartner');
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
        $this->routePages('xepan_epanservices');
            $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
            ->setBaseURL('./vendor/xepan/epanservices/');


        $epan_model = $this->add('xepan/epanservices/Model_Epan');
        $this->app->addHook('order_placed',[$epan_model,'createFromOrder']);
        $this->app->addHook('invoice_paid',[$epan_model,'invoicePaid']);

        if($this->app->isEditing){
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_EpanTrial','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_AfterCreation','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_AgencyPanel','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerPanel','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerMenu','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_Item','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_Theme','EpanTrial');
        }

        $this->app->addHook('cron_executor',function($app){
            $now = \DateTime::createFromFormat('Y-m-d H:i:s', $this->app->now);
            echo "Running All Epans Cron <br/>";
            var_dump($now);
            if($this->app->current_website_name !='www'){
                echo "leaving ". $this->app->current_website_name. '<br/>';
                return;
            }
            $job1 = new \Cron\Job\ShellJob();
            $job1->setSchedule(new \Cron\Schedule\CrontabSchedule('*/5 * * * *'));
            if(!$job1->getSchedule() || $job1->getSchedule()->valid($now)){
                $urls=[];
                foreach ($this->add('xepan\base\Model_Epan')->addCondition('id','<>',$this->app->epan->id)->addCondition('is_published',true) as $other_epans) {
                    // $command = 'wget http://'. $other_epans['name'].'.epan.in?page=xepan_base_cron&cut_page=true';
                    // echo "<br/> executing ". $command. '<br/>';
                    // shell_exec($command);
                    $urls[] = 'http://'. $other_epans['name'].'.epan.in?page=xepan_base_cron&cut_page=true&now='.urlencode($this->app->now);
                }
                if(count($urls)){
                    $results = $this->multi_request($urls);
                }
            }
        });

        // login hook
        $this->app->addHook('login_panel_user_loggedin',function($app,$user){
            $model = $this->add('xepan\epanservices\Model_Agency');
            $model->loadLoggedIn();
            if($model->loaded())
                $this->app->redirect($this->app->url('agency-dashboard'));

            $model = $this->add('xepan\commerce\Model_Customer');
            $model->loadLoggedIn();
            if($model->loaded())
                $this->app->redirect($this->app->url('customer-dashboard'));

        });

    	return $this;
    }

    function multi_request($urls)
    {
        $curly = array();
        $result = array();
        $mh = curl_multi_init();

        foreach ($urls as $id => $url) {
            $curly[$id] = curl_init();
            curl_setopt($curly[$id], CURLOPT_URL, $url);
            curl_setopt($curly[$id], CURLOPT_HEADER, 0);
            curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curly[$id], CURLOPT_TIMEOUT, 30);
            curl_setopt($curly[$id], CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curly[$id], CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curly[$id], CURLOPT_SSL_VERIFYHOST, 0);
            curl_multi_add_handle($mh, $curly[$id]);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while($running > 0);

        foreach($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }
        curl_multi_close($mh);
        return $result;
    }

    function exportEntities($app,&$array){
        $array['Epan'] = ['caption'=>'Epan','type'=>'xepan\base\Basic','model'=>'xepan\epanservices\Model_Epan'];

    }


    function resetDB($write_sql=false){
    }
}