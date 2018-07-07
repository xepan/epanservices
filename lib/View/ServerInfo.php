<?php

namespace xepan\epanservices;


class View_ServerInfo extends \View {
	
	function init(){
		parent::init();
		
		$this->v= $this->add('View');
		$this->cols = $cols = $this->v->add('Columns');
		$memory_col = $cols->addColumn(4);
		$cpu_col = $cols->addColumn(4);
		$space_col = $cols->addColumn(4);

		$this->memory_view = $memory_view = $memory_col->add('xepan\base\View_Widget_ProgressStatus');
		$memory_view->setHeading('Memory Status');
		$memory_view->setIcon('fa fa-dot-circle-o');

		$this->cpu_load_view = $cpu_load_view = $cpu_col->add('xepan\base\View_Widget_ProgressStatus');
		$cpu_load_view->setHeading('Server Load');
		$cpu_load_view->setIcon('fa fa-cogs');

		$this->space_view = $space_view = $space_col->add('xepan\base\View_Widget_ProgressStatus');
		$space_view->setHeading('Storage');
		$space_view->setIcon('fa fa-hdd-o');
		$total_space = disk_total_space('/');
		$free_space = disk_free_space('/');
		$per = round(($total_space-$free_space)/$total_space*100,0);
		$this->space_view->setProgressPercentage($per);
		$this->space_view->setFooter($per.'% [Free '. $this->app->byte2human($free_space).']');

	}

	function get_server_memory_usage(){
	
		$free = shell_exec('free');
		if(!$free) return;
		$free = (string)trim($free);
		$free_arr = explode("\n", $free);
		$mem = explode(" ", $free_arr[1]);
		$mem = array_filter($mem);
		$mem = array_merge($mem);

		if(isset($free_arr[2])){
			$swap = explode(" ", $free_arr[2]);
			$swap = array_filter($swap);
			$swap = array_merge($swap);
		}else{
			$swap=[0,0,0];
		}

		$memory_usage = ($mem[2]+$swap[2])/($mem[1]+$swap[1])*100;

		return $memory_usage;
	}

	function get_server_cpu_usage(){

		$load = sys_getloadavg();
		return round($load[0],1);

	}

	function recursiveRender(){
		if($_GET[$this->memory_view->name]){
			$this->memory_view->setProgressPercentage($per=$this->get_server_memory_usage());
			$this->memory_view->setFooter($per.'%');
		}

		if($_GET[$this->cpu_load_view->name]){
			$this->cpu_load_view->setProgressPercentage($per=$this->get_server_cpu_usage());
			$this->cpu_load_view->setFooter($per.'%');
		}

		if(!$this->app->isAjaxOutput()){
			$this->memory_view->setProgressPercentage($per=$this->get_server_memory_usage());
			$this->memory_view->setFooter($per.'%');

			$this->cpu_load_view->setProgressPercentage($per=$this->get_server_cpu_usage());
			$this->cpu_load_view->setFooter($per.'%');
				
			$this->js(true)->univ()->setInterval($this->memory_view->js()->reload([$this->memory_view->name=>true])->_enclose(),20000);
			$this->js(true)->univ()->setInterval($this->cpu_load_view->js()->reload([$this->cpu_load_view->name=>true])->_enclose(),20000);
		}

		return parent::recursiveRender();
	}
}