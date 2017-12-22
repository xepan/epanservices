<?php

namespace xepan\epanservices;


class Controller_RemoteEpan extends \AbstractController{
	public $epan = null;

	function setEpan($epan){
		
		if(is_numeric($epan)){
			$epan = $this->add('xepan\base\Model_Epan')->tryLoadBy('id',$epan);
		}elseif(is_string($epan)){
			$epan = $this->add('xepan\base\Model_Epan')->tryLoadBy('name',$epan);			
		} 

		$this->epan = $epan;

		return $this;
	}

	function do($method){
		if(!is_callable($method)){
			throw $this->exception('Must be callable - '.$method);
		}				

		
		$config = file_get_contents($this->app->pathfinder->base_location->base_path.'/websites/'.$this->epan['name'].'/config.php');
		$config = preg_match('/.*config.*dsn.*=(.*);/i', $config, $dsn_config);
		$dsn = $dsn_config[1];
		
		$new_db = $this->add('DB');
		$new_db->connect($dsn);

		$saved_db = clone $this->app->db;
		$saved_currenct_website_name = $this->app->current_website_name;
		
		$this->app->db = $new_db;
		$this->app->current_website_name = $this->epan['name'];

		call_user_func($method, $this->app);

		$this->app->db = $saved_db;
		$this->app->current_website_name = $saved_currenct_website_name;
		// restore $this->app->db 
	}

}