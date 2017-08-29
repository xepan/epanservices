<?php


namespace xepan\epanservices;

class View_Item extends \View{

	function init(){
		parent::init();
		
		$item = $this->add('xepan\commerce\Model_Item_WebsiteDisplay');

		$cl = $this->add('CompleteLister',null,null,['view/item']);
		$cl->setModel($item);
		$paginator = $cl->add('Paginator',['ipp'=>4]);
		$paginator->setRowsPerPage(4);

		$cl->addHook('formatRow',function($l){
			$l->current_row_html['description']=$l->model['description'];
			$l->current_row_html['selection_url']= $this->app->url(null,['action'=>'create-epan','x-select-id'=>$l->model->id]);
		});
		// deleting not found templates
		if($item->count()->getOne()){
			$cl->template->del('not_found');
		}else{
			$cl->template->set('not_found_message','No Record Found');
		}

	}
}