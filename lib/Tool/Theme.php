<?php


namespace xepan\epanservices;

class Tool_Theme extends \xepan\cms\View_Tool{
	public $options = [];

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		$epan_detail = $this->app->recall('newepan');

		$this->add('View')->set($epan_detail['epan_name']." = ".$epan_detail['epan_id']." === ");
		
		$this->add('xepan\cms\View_Theme',[
						'apply_theme_on_website'=>$epan_detail['epan_name'],
						'apply_theme_on_website_id'=>$epan_detail['epan_id']
					]);
	}
}