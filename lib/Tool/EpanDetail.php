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
		$limits_tab = $tabs->addTab('Limits');

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

		$domain_info = $tab->add('View');
		$domain_info->add('xepan\base\Controller_FLC')
			->makePanelsCoppalsible()
			->layout([
					'new_domain~'=>'Add Domains to your Epan~c1~12~Parked Domains are for Top level domains like .com .in etc. You can park existing domain also.',
					'parked_doamins~Existing Parked Domains'=>'Add Parked Domain~c1~12',
					'new_epan_alias~'=>'Add Epan Aliases~c2~12~Multiple subdomains for same website erp.epan.in, best-erp.epan.in, crm.epan.in etc. useful for SEO',
					'epan_aliases~Existing Epan Aliases'=>'c2~12',
					'park_existing_domain~'=>'Park Existing Domain~c1~12',
					'how_to_park_domain~'=>'c2~12~Just change your domain DNS A Setting to xx.xx.xx.xx to just change website to epan.in <br/>Change nameserver to ns1.epan.in and ns2.epan.in if you wish to let epan server manage your emails also.',
				]);
		
		$domain_info->add('Grid',null,'parked_doamins')->setSource([]);

		$grid = $domain_info->add('Grid',null,'epan_aliases')->setSource(explode(",",$this->selected_epan['aliases']));
		$grid->addColumn('name');
		$grid->removeColumn('id');

		$form_aliases = $domain_info->add('Form',null,'new_epan_alias');
		$form_aliases->add('xepan\base\Controller_FLC')
			->layout([
					'epan_alias_name'=>'c1~6',
					'epan~'=>'c1~3~.epan.in',
					'FormButtons~'=>'c1~3'
				])
			;
		$form_aliases->addField('epan_alias_name')->validate('Required');
		$form_aliases->addSubmit('Purchase Now');
		if($form_aliases->isSubmitted()){

			if($this->selected_epan->checkAliasExist($form_aliases['epan_alias_name']))
				$form_aliases->error('epan_alias_name',$form_aliases['epan_alias_name'].'.epan.in Already used, select another one.');
								
			$this->selected_epan->purchaseEpanAlias($form_aliases['epan_alias_name'],$this->customer,$check_existing=false,$redirect_to_payment=true);
						
		}

		// $domain_info->add('Button',null,'new_domain')->set('PURCHASE NEW DOMAIN')->addClass('btn btn-success btn-block')->js('click',$this->js()->univ()->frameURL('Purchase Domain',$purchase_domain_vp->getURL()));
		// $domain_info->add('Button',null,'new_epan_alias')->set('PURCHASE NEW ALIAS')->addClass('btn btn-success btn-block');
		// $domain_info->add('Button',null,'park_existing_domain')->set('Park Existing Domain')->addClass('btn btn-success btn-block');

	}
}