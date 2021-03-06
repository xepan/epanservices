<?php


namespace xepan\epanservices;

class Tool_Item extends \xepan\cms\View_Tool{
	public $options = ['next_step_page'=>'new-epan'];

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		$this->add('xepan\epanservices\View_ProgressBar');

		$item = $this->add('xepan\commerce\Model_Item_WebsiteDisplay')
			->setOrder('display_sequence','desc');

		$cl = $this->add('CompleteLister',null,null,['view/item']);
		$cl->setModel($item);
		$paginator = $cl->add('Paginator',['ipp'=>12]);
		$paginator->setRowsPerPage(12);

		$cl->addHook('formatRow',function($l){
			$l->current_row_html['description'] = $l->model['description'];
			$l->current_row_html['selection_url'] = $this->app->url($this->options['next_step_page'],['x-new-product'=>$l->model->id]);
		});
		// deleting not found templates
		if($item->count()->getOne()){
			$cl->template->del('not_found');
		}else{
			$cl->template->set('not_found_message','No Record Found');
		}

	}
}