<?php

namespace xepan\epanservices;

class Tool_EpanDetail extends \xepan\cms\View_Tool {

	public $options = [];

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;


		$purchase_domain_vp = $this->add('VirtualPage');
		$this->manageDomainPurchase($purchase_domain_vp);

		$epan_id = $this->app->stickyGET('selected');

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer');
		$customer->loadLoggedIn();
		if(!$customer->loaded()) throw new \Exception("customer not found");

		$this->selected_epan = $epan = $this->add('xepan\epanservices\Model_Epan');
		$this->selected_epan = $epan->tryLoad($epan_id);

		if(!$epan->loaded()) throw new \Exception("epan not exist");
		if($epan['created_by_id'] != $customer->id) throw new \Exception("you are not the authorize the customer for this customer");

		$this->add('View')->setElement('h2')->set($epan['name'].".epan.in")->addClass('text-center');

		$tabs = $this->add('Tabs');
		$basic_info_tab = $tabs->addTab('Basic info');
		$domains_tab = $tabs->addTab('Domains');
		$emails_tab = $tabs->addTab('Emails');
		// $limits_tab = $tabs->addTab('Limits');

		$basic_info = $basic_info_tab->add('View');
		$basic_info->add('xepan\base\Controller_FLC')
			// ->makePanelsCoppalsible()
			->layout([
					'epan_name'=>'Basic Info~c1~6~closed',
					'created_at'=>'c2~6',
					'renewal_date'=>'c3~6'
				]);
		
		$basic_info->add('View',null,'epan_name')->set($epan['name'].'.epan.in');
		$basic_info->add('View',null,'created_at')->set($epan['created_at']);
		$basic_info->add('View',null,'renewal_date')->set($epan['created_at']);

		$this->domainInfo($domains_tab);
		$this->emailPurchase($emails_tab);
		// $email_info = $emails_tab->add('View');
		// $email_info->add('xepan\base\Controller_FLC')
		// 	// ->makePanelsCoppalsible()
		// 	->layout([
		// 			'new_email~'=>'Manage your Email Accounts~c1~6~',
		// 			'existing_emails~Existing Email Accounts'=>'c1~6',
		// 			'new_epan_alias~'=>'c2~6~You can configure any 3rd party email accounts in your EPAN, but if you need emails from Epan Services, use this panel and book some emails',
		// 		]);
		
		// $email_info->add('Grid',null,'existing_emails')->setSource([]);
		// $email_info->add('Button',null,'new_email')->set('PURCHASE NEW EMAIL')->addClass('btn btn-success btn-block');

		// $limits_info = $limits_tab->add('View');
		// $limits_info->add('xepan\base\Controller_FLC')
		// 	// ->makePanelsCoppalsible()
		// 	->layout([
		// 			'users'=>'Existing Limits~c1~4',
		// 			'bandwidth'=>'c2~4',
		// 			'space'=>'c3~4',
		// 			'purchase_user'=>'Update Limits~c1~4',
		// 			'purchase_bandwidth'=>'c2~4',
		// 			'purchase_space'=>'c3~4',
		// 		]);

	}

	function manageDomainPurchase($page){
		$page->set(function($p){
			$p->add('View')->set('Hiii');
		});
	}

	function domainInfo($tab){
		
		
		$col = $tab->add('Columns');
		$col1 = $col->addColumn(6);
		$col2 = $col->addColumn(6);
		
		$view = $col2->add('View');
		$view->add('xepan\base\Controller_FLC')
			->makePanelsCoppalsible()
			->layout([
				'aliases~'=>'Existing Epan Aliases/ Parked Domain~c2~12'
			]);

		$aliases_array = explode(",",$this->selected_epan['aliases']);
		$grid = $view->add('Grid',null,'aliases')->setSource($aliases_array);
		$grid->addColumn('name');
		$grid->removeColumn('id');


		$domain_info = $col1->add('View');
		// Park Domain
		$form_park_domain = $domain_info->add('Form');
		$form_park_domain->add('xepan\base\Controller_FLC')
			->layout([
					'domain_name'=>'Parking Domain Price: <i class="fa fa-rupee"></i> 200 per/domain~c1~12',
					'FormButtons~'=>'c2~12'
				])
			;
		$form_park_domain->addField('domain_name')->validate('Required');
		$form_park_domain->addSubmit('Park Domain Now')->addClass('btn btn-primary btn-block');
		if($form_park_domain->isSubmitted()){
			// domain validation
			
			$re = '/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/m';
			preg_match_all($re, $form_park_domain['domain_name'], $matches, PREG_SET_ORDER, 0);
			if(!count($matches)) $form_park_domain->error('domain_name','must be a valide domain name ie. xavoc.com, www.epan.in');
						
			if($this->selected_epan->checkAliasExist($form_park_domain['domain_name']))
				$form_park_domain->error('domain_name',$form_park_domain['domain_name'].' is already parked.');
			
			$this->selected_epan->parkDomain($form_park_domain['domain_name'],$this->customer,$check_existing=false,$redirect_to_payment=true);
		}

		
		// Epan Alias
		$form_aliases = $domain_info->add('Form');
		$form_aliases->add('xepan\base\Controller_FLC')
			->layout([
					'epan_alias_name'=>'Epan Aliases Price: <i class="fa fa-rupee"></i> 200 per/aliase~c1~8',
					'epan~'=>'c2~4~<h2>.epan.in</h2>',
					'FormButtons~'=>'c3~12'
				])
			;
		$form_aliases->addField('epan_alias_name')->validate('Required');
		$form_aliases->addSubmit('Purchase Now')->addClass('btn btn-primary btn-block');

		if($form_aliases->isSubmitted()){
			if($this->selected_epan->checkAliasExist($form_aliases['epan_alias_name']))
				$form_aliases->error('epan_alias_name',$form_aliases['epan_alias_name'].'.epan.in Already used, select another one.');

			$this->selected_epan->purchaseEpanAlias($form_aliases['epan_alias_name'],$this->customer,$check_existing=false,$redirect_to_payment=true);
		}

		// $domain_info->add('Button',null,'new_domain')->set('PURCHASE NEW DOMAIN')->addClass('btn btn-success btn-block')->js('click',$this->js()->univ()->frameURL('Purchase Domain',$purchase_domain_vp->getURL()));
		// $domain_info->add('Button',null,'new_epan_alias')->set('PURCHASE NEW ALIAS')->addClass('btn btn-success btn-block');
		// $domain_info->add('Button',null,'park_existing_domain')->set('Park Existing Domain')->addClass('btn btn-success btn-block');

	}


	function emailPurchase($tab){
		$tab->add('View_Info')->addClass('alert alert-success text-center')->setHtml("You can configure any 3rd party email accounts in your EPAN, but if you need emails from Epan Services, use this panel and book some emails");
		$tab->add('View_Info')->addClass('alert alert-info text-center')->setHtml("comming soon, for now, send request to <strong>support@xavoc.com</strong>");
	}

}