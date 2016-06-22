<?php

namespace xepan\epanservices;

class Model_MyTemplates extends \xepan\commerce\Model_Item{

	function init(){
		parent::init();

		$customer = $this->add('xepan\commerce\Model_Customer');
        $customer->loadLoggedIn();

		$template_cat_model = $this->add('xepan\commerce\Model_Category')->addCondition('name','Templates');

		$cat_assoc_j = $this->join('category_item_association.item_id');
		$cat_assoc_j->addField('category_id');

		$order_items_j = $this->join('qsp_detail.item_id');

		$order_j = $order_items_j->join('qsp_master.document_id','qsp_master_id');
		$order_j->addField('contact_id');

		$invoice_j = $order_j->join('qsp_master.related_qsp_master_id','document_id');
		$invoice_doc_j = $invoice_j->join('document','document_id');
		$invoice_doc_j->addField('invoice_status','status');

		$this->addCondition('invoice_status','Paid');
		$this->addCondition('contact_id',$customer->id);
		$this->addCondition('category_id',$template_cat_model->fieldQuery('id'));
		
	}
}