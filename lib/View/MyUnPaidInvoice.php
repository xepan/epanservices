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

		$saleorder->addExpression('extra_info',function($m,$q){
			$i = $m->add('xepan\commerce\Model_QSP_Detail',['table_alias'=>'details']);
			$i->addCondition('qsp_master_id',$q->getField('id'));
			$i->setLimit(1);
			return $q->expr('IFNULL([0],"Due")',[$i->fieldQuery('extra_info')]);
		})->caption('Epan Name');

		if($saleorder->count()->getOne()){
			$this->add('View')->set('My Unpaid Order')->addClass(' panel panel-heading xepan-grid-heading');
			$grid = $this->add('xepan\base\Grid');

			$grid->addHook('formatRow',function($g){
				$jp = new \xepan\base\JsonPath();
				$x1=json_decode($g->model['extra_info'],true);
				$x = $jp->get($x1,'$..custom_field_value_name'); // http://goessner.net/articles/JsonPath/
				$g->current_row_html['extra_info'] = implode(",", $x);
			});

			$grid->setModel($saleorder,['document_no','invoice_status','created_at','net_amount','extra_info']);
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