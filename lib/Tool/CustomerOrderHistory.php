<?php

namespace xepan\epanservices;

class Tool_CustomerOrderHistory extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;


		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();
		
		$saleorder = $this->add('xepan\commerce\Model_SalesOrder');
		$saleorder->addCondition('contact_id',$this->customer->id);

		$saleorder->addExpression('invoice_status',function($m,$q){
			$i = $m->add('xepan\commerce\Model_SalesInvoice',['table_alias'=>'sale']);
			$i->addCondition('related_qsp_master_id',$q->getField('id'));
			return $q->expr('IFNULL([0],"Due")',[$i->fieldQuery('status')]);
		})->caption('Status');
		$saleorder->setOrder('id','desc');

		$this->add('View')->set('My Order History')->addClass(' panel panel-heading xepan-grid-heading');
		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($saleorder,['document_no','invoice_status','created_at','net_amount']);
			
		$grid->addMethod('format_paynow',function($g,$f){
			if($g->model['invoice_status'] == "Paid"){
				$g->current_row_html[$f] = '<p class="label label-success">Paid</p>';
			}
		});
		$grid->addColumn('Button,paynow','pay_now','Pay Now');

		$grid->addPaginator($ipp=25);
		$grid->addSno();

		if($pay_now_order = $_GET['pay_now']){
			$payment_url = $this->app->url('customer-checkout',
										[
											'step'=>"Address",
											'order_id'=>$pay_now_order,
											'next_step'=>'Payment'
										]
									);
			$this->js()->univ()->redirect($payment_url)->execute();
		}

	}
}