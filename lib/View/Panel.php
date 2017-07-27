<?php


namespace xepan\epanservices;

class View_Panel extends \View{
	public $theme_class = "panel-primary";
	public $heading = "panel heading";
	public $footer = "panel heading";
	public $content = "panel content";

	function init(){
		parent::init();
		
		$this->addClass('xepan-panel');
		$this->template->trySetHTML('theme_class',$this->theme_class);	
		$this->template->trySetHTML('panel_heading',$this->heading);	
		$this->template->trySetHTML('panel_footer',$this->footer);	
		$this->template->trySetHTML('panel_content',$this->content);
	
	}

	function defaultTemplate(){
		return ['view/panel'];
	}
}