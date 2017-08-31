<?php


namespace xepan\epanservices;

class View_ProgressBar extends \View{
	public $active_step;

	function init(){
		parent::init();
		
		if(!$this->active_step) $this->active_step = 1;

		switch ($this->active_step) {
			case 1:
				$this->template->set('step1_class','complete');
				$this->template->set('step2_class','active');
				$this->template->set('step3_class','disabled');
			break;
			case 2:
				$this->template->set('step1_class','complete');
				$this->template->set('step2_class','complete');
				$this->template->set('step3_class','active');
			break;
			case 3:
				$this->template->set('step1_class','complete');
				$this->template->set('step2_class','complete');
				$this->template->set('step3_class','complete');
			break;
		}
	
	}

	function defaultTemplate(){
		return ['view/progressbar'];
	}
}