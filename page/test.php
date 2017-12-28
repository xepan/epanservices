<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\epanservices;

class page_test extends \xepan\base\Page {
	public $title='Test';

	function init(){
		parent::init();
		
		if($_GET['resetdb'] && $_GET['resetdb'] == 1){
			
			$this->add('View')->set('Reseting DB')->addClass('alert alert-danger');
			
			$cm = $this->add('xepan\communication\Model_Communication');
			$cm->deleteAll();

			$ac = $this->add('xepan\base\Model_Activity');
			$ac->deleteAll();

			$lead = $this->add('xepan\base\Model_Contact');
			$lead->addCondition('type','Lead');
			
			foreach ($lead as $model) {
				$qsp = $this->add('xepan\commerce\Model_QSP_Master');
				$qsp->addCondition('contact_id',$model->id);
				foreach ($qsp as $q) {
					$q->delete();
				}

				$model->delete();
			}

			$this->app->redirect($this->app->url(null,['resetdb'=>2]));
		}

		if($_GET['resetdb'] && $_GET['resetdb'] == 2){
			$this->add('View')->set("step 2")->addClass('alert alert-danger');

			$set = $this->add('xepan\commerce\Model_Item_Quantity_Set');
			$set->addExpression('has_item',function($m,$q){
				$c = $m->add('xepan\commerce\Model_Item');
				$c->addCondition('id',$m->getElement('item_id'));
				return $q->expr('IFNULL([0],0)',[$c->count()]);
			});			
			$set->addCondition('has_item',0);
			$set->deleteAll();

			$item_dept_asso = $this->add('xepan\commerce\Model_Item_Department_Association');
			$item_dept_asso->addExpression('has_item',function($m,$q){
				$c = $m->add('xepan\commerce\Model_Item');
				$c->addCondition('id',$m->getElement('item_id'));
				return $q->expr('IFNULL([0],0)',[$c->count()]);
			});
			$item_dept_asso->addCondition('has_item',0);
			$item_dept_asso->deleteAll();

			$ml = $this->add('xepan\marketing\Model_LandingResponse');
			$ml->deleteAll();

			$doc = $this->add('xepan\base\Model_Document');
			$doc->addCondition('type',['SalesOrder','SalesInvoice','PurchaseOrder','PurchaseInvoice','Quotation']);
				
			$doc_ids = ['SalesOrder'=>[],'SalesInvoice'=>[],'PurchaseOrder'=>[],'PurchaseInvoice'=>[]];

			foreach ($doc as $d){
				$m = $this->add('xepan\commerce\Model_QSP_Master');
				$m->addCondition('id',$d->id);
				$m->tryLoadAny();
				if(!$m->loaded())
					$doc_ids[$d['type']][$d->id] = $d->id;
			}

			foreach ($doc_ids as $type => $ids) {
				if(!count($ids)) continue;

				$m = $this->add('xepan\commerce\Model_'.$type)
					->addCondition('id',$ids);
				foreach ($m as $sale) {
					$sale->delete();
				}
			}
		}


	}
}
