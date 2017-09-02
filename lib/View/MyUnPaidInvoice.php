<?php


namespace xepan\epanservices;

class View_MyUnPaidInvoice extends \View{

	function init(){
		parent::init();
			
		$this->addClass('epan-service-grid');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();
		
		// unpaid orders
		$saleorder = $this->add('xepan\commerce\Model_SalesOrder');
		$saleorder->addCondition('contact_id',$this->customer->id);

		$saleorder->addExpression('invoice_status',function($m,$q){
			$i = $m->add('xepan\commerce\Model_SalesInvoice',['table_alias'=>'sale']);
			$i->addCondition('related_qsp_master_id',$q->getField('id'));
			return $q->expr('IFNULL([0],"Due")',[$i->fieldQuery('status')]);
		})->caption('Status');
		$saleorder->setOrder('id','desc');
		$saleorder->addCondition('invoice_status','Due');

		if($saleorder->count()->getOne()){
			$this->add('View')->set('My Unpaid Order')->addClass(' panel panel-heading xepan-grid-heading');
			$grid = $this->add('xepan\base\Grid');
			$grid->setModel($saleorder,['document_no','invoice_status','created_at','net_amount']);
			$grid->addColumn('Button','pay_now','Pay Now');
			$grid->addPaginator($ipp=5);
			$grid->addSno();
		}

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