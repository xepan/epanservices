<?php


namespace xepan\epanservices;

class Tool_Theme extends \xepan\cms\View_Tool{
	public $options = [
		'dashboard_page' => "customer-dashboard"
		];

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		$epan_detail = $this->app->recall('newepan');

		$this->add('xepan\epanservices\View_ProgressBar',['active_step'=>3]);
		
		$this->add('xepan\cms\View_Theme',[
						'apply_theme_on_website'=>$epan_detail['epan_name'],
						'apply_theme_on_website_id'=>$epan_detail['epan_id'],
						'dashboard_page'=>$this->options['dashboard_page']
					]);

		$this->add('View',null,'skip')->setElement('a')->setAttr('href',$this->app->url($this->options['dashboard_page']))->set('Skip Step');
	}

	function defaultTemplate(){
		return ['view/theme'];
	}
}