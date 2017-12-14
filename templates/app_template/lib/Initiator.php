<?php

namespace {namespace};

class Initiator extends \Controller_Addon {
    
    public $addon_name = '{_namespace}';

    function setup_admin(){
        $this->routePages('{_namespace}');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('../shared/apps/{namespace_fwdslash}/');

        $m = $this->app->top_menu->addMenu('{name}');

        return $this;
    }

    function setup_pre_frontend(){
        $this->routePages('{_namespace}');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('./shared/apps/{namespace_fwdslash}/');

        return $this;
    }

    function setup_frontend(){
        $this->routePages('{_namespace}');
        $this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
        ->setBaseURL('./shared/apps/{namespace_fwdslash}/');

        // {export_tools_here}

        return $this;
    }

}
