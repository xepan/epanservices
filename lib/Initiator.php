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
            ->setBaseURL('../shared/apps/xepan/epanservices/')
            ;

		if($this->app->getConfig('multi-xepans',false)){
            $this->app->side_menu->addItem(['Epans','icon'=>' fa fa-globe','badge'=>[1 ,'swatch'=>' label label-primary pull-right']],'xepan_epanservices_epans')->setAttr(['title'=>'Epans']);
        }

        if($this->app->inConfigurationMode)
            $this->populateConfigurationMenus();
        else
            $this->populateApplicationMenus();

        
        $this->app->addHook('entity_collection',[$this,'exportEntities']);
        $this->app->addHook('collect_shortcuts',[$this,'collect_shortcuts']);        
    	return $this; 
    }

    function populateConfigurationMenus(){

    }

    function populateApplicationMenus(){
        if(!$this->app->isAjaxOutput() && !$this->app->getConfig('hidden_xepan_epanservices',false)){
            $m = $this->app->top_menu->addMenu('Epans');
            $m->addItem(['Category','icon'=>'fa fa-sitemap'],'xepan_epanservices_category');
            $m->addItem(['Epans','icon'=>'fa fa-sitemap'],$this->app->url('xepan_epanservices_epans',['status'=>'Trial,Paid']));
            $m->addItem(['Templates','icon'=>'fa fa-sitemap'],'xepan_epanservices_epantemplates');
            $m->addItem(['Agency','icon'=>'fa fa-sitemap'],'xepan_epanservices_agency');
            $m->addItem(['Channel Partner','icon'=>'fa fa-sitemap'],'xepan_epanservices_channelpartner');
            $m->addItem(['Applications','icon'=>'fa fa-sitemap'],'xepan_epanservices_applications');
            $m->addItem(['Release Management','icon'=>'fa fa-sitemap'],'xepan_epanservices_release');
        }

        $this->app->side_menu->addItem([' DB Version Generate','icon'=>' fa fa-edit'],'xepan_epanservices_dbversion')->setAttr(['title'=>'DB Version Generate ']);
        
    }

    function collect_shortcuts($app,&$shortcuts){
        $shortcuts[]=["title"=>"Epan Categories","keywords"=>"epan categories","description"=>"Manage Epan Categories","normal_access"=>"Epans -> Category","url"=>$this->app->url('xepan_epanservices_category'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Epans","keywords"=>"epan epans websites","description"=>"Manage Epans","normal_access"=>"Epans -> Epans","url"=>$this->app->url('xepan_epanservices_epans'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Epan Templates","keywords"=>"epan templates","description"=>"Manage Epan Templates","normal_access"=>"Epans -> Templates","url"=>$this->app->url('xepan_epanservices_epantemplates'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Epan Agency","keywords"=>"epan agency","description"=>"Manage Epan Agency","normal_access"=>"Epans -> Agency","url"=>$this->app->url('xepan_epanservices_agency'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Epan Channel Partner","keywords"=>"epan channel partner","description"=>"Manage Epan Channel Partners","normal_access"=>"Epans -> Channel Partners","url"=>$this->app->url('xepan_epanservices_channelpartner'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Epan Application","keywords"=>"epan applications","description"=>"Manage Epan Application","normal_access"=>"Epans -> Applications","url"=>$this->app->url('xepan_epanservices_applications'),'mode'=>'frame'];
    }

    function setup_pre_frontend(){
        $this->routePages('xepan_epanservices');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('shared/apps/xepan/epanservices/')
        ;

        return $this;
    }

    function setup_frontend(){
        $this->routePages('xepan_epanservices');
            $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
            ->setBaseURL('./shared/apps/xepan/epanservices/');


        $epan_model = $this->add('xepan/epanservices/Model_Epan');
        $this->app->addHook('order_placed',[$epan_model,'createFromOrder']);
        $this->app->addHook('invoice_paid',[$epan_model,'invoicePaid']);

        if($this->app->isEditing){
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_EpanTrial','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_EpanDetail','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_AfterCreation','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_AgencyPanel','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerDashboard','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerMenu','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerSetting','EpanTrial');
            $this->app->exportFrontEndTool('xepan\epanservices\Tool_CustomerOrderHistory','EpanTrial');
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
                $other_epans = $this->add('xepan\base\Model_Epan')
                                    ->addCondition('id','<>',$this->app->epan->id)
                                    ->addCondition('is_published',true)
                                    ->addCondition('is_template',false)
                                    ->addCondition('status',['Trial','Paid','Grace'])
                                    ;
                foreach ($other_epans as $other_epan) {
                    // $command = 'wget http://'. $other_epans['name'].'.epan.in?page=xepan_base_cron&cut_page=true';
                    // echo "<br/> executing ". $command. '<br/>';
                    // shell_exec($command);
                    $urls[] = 'http://'. $other_epan['name'].'.xavoc.com?page=xepan_base_cron&cut_page=true&now='.urlencode($this->app->now);
                }
                if(count($urls)){
                    var_dump($urls);
                    $results = $this->multi_request($urls);
                }
            }

            $job2 = new \Cron\Job\ShellJob();
            $job2->setSchedule(new \Cron\Schedule\CrontabSchedule('0 0 * * *'));
            if(!$job2->getSchedule() || $job2->getSchedule()->valid($now)){
                foreach ($this->add('xepan\epanservices\Model_Epan')->addCondition('expiry_date','<',$this->app->today)->addCondition('status','<>','Paid') as $demo_finished) {
                    $demo_finished->expire('Demo Expiered');
                }
            }
        },[],3);

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
        // $array['Employee'] = ['caption'=>'Employee', 'type'=>'xepan\base\Basic','model'=>'xepan\hr\Model_Employee'];
    }


    function resetDB($write_sql=false){
    }
}