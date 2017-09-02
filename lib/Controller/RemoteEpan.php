<?php

namespace xepan\epanservices;


class Controller_RemoteEpan extends \AbstractController{
	public $epan = null;

	function setEpan($epan){
		if(is_string($epan)) 
			$epan = $this->add('xepan\base\Model_Epan')->tryLoadBy('name',$epan);
		$this->epan = $epan;
	}

	function do($method){
		if(!is_callable($method)){
			throw $this->exception('Must be callable - '.$method);
		}

		// save $this->app->db 

		call_user_func($method, $this->app);

		// restore $this->app->db 
	}

}