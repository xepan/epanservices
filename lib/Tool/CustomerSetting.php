<?php

namespace xepan\epanservices;

class Tool_CustomerSetting extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		$this->app->stickyGET('action');

		if($this->owner instanceof \AbstractController) return;

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer')
							->addCondition('user_id',$this->app->auth->model->id);
		$customer->loadLoggedIn();

		if(!$customer->loaded()){
			$this->add('View_Error')->set('you are not a customer.');
			return;
		}

		$action = $this->app->stickyGET('action');
		$this->app->stickyGET('profile');
		if($_GET['profile'] == "incomplete"){
			$action = "profile";
		}

		switch ($action) {
			case 'profile':
				$this->profileUpdate();
				break;
			case 'changepassword':
				$this->changePassword();
				break;
		}	
	
	}

	function profileUpdate(){

		$col = $this->add('Columns');
		$col1 = $col->addColumn(4)->addClass('text-center');
		$col2 = $col->addColumn(4);
		
		$src = 'shared/apps/xepan/epanservices/templates/images/profile.png';
		if($this->customer['image']){
			$this->customer->reload();
			$src = $this->customer['image'];
		}
		$img_view = $col1->add('View')->setElement('img')->setAttr('src',$src)->addClass('thumbnail')->setStyle('margin','0 auto');

		$i_form = $col1->add('Form');
		$attachment_field = $i_form->addField('xepan\base\Upload','profile_image_id','Profile Image')->addClass('well well-sm');
		$attachment_field->setModel('xepan\filestore\Image');

		if($this->customer['image_id'] && $this->customer['image'])
			$attachment_field->set($this->customer['image_id']);

		$i_form->addSubmit('Save Image')->addClass('btn btn-primary');

		if($i_form->isSubmitted()){
			$this->customer['image_id'] = $i_form['profile_image_id'];
			$this->customer->save();

			$this->customer->reload();
			$i_form->js(null,[$img_view->js()->reload(),$i_form->js(true)->_selector('img.ds-dp')->attr('src',$this->customer['image'])])->univ()->successMessage('Profile Photo Updated')->execute();
		}


		$form = $col2->add('Form');
		$form->add('xepan\base\Controller_FLC')
				->addContentSpot()
				->layout([
						'first_name'=>'Update Profile~c1~12',
						'last_name'=>'c2~12',
						'organization'=>'c3~12',
						'country_id~Country'=>'c4~12',
						'state_id~State'=>'c4~12',
						'city'=>'c4~12',
						'pin_code'=>'c4~12',
						'address'=>'c7~12',
						'email_id'=>'c8~12',
						'phone_no'=>'c9~12',
					]);
		$fields = ['first_name','last_name','country_id','state_id','organization','address','city','pin_code'];
		$form->setModel($this->customer,$fields);
		$email_field = $form->addField('email_id')->validate('email');
		$phone_no_field = $form->addField('phone_no')->validate('required');

		$country_field = $form->getElement('country_id')->validate('required');
		$country_field->getModel()->addCondition('status','Active');

		$state_field = $form->getElement('state_id')->validate('required');
		$state_field->getModel()->addCondition('status','Active');

		if($_GET['country_id']){
			$state_field->getModel()->addCondition('country_id',$_GET['country_id']);
		}
		$country_field->js('change',$state_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$state_field->name]),'country_id'=>$country_field->js()->val()]));

		// load email
		$email_field->set($this->customer->getEmails()[0]);
		$phone_no_field->set($this->customer->getPhones()[0]);

		foreach ($fields as $key => $field_name) {
			$form->getElement($field_name)->validate('required');
		}

		$form->addSubmit('Update Profile')->addClass('btn btn-primary btn-block');

		if($form->isSubmitted()){

			$epan = $this->add('xepan\epanservices\Model_Epan');
			$epan->addCondition('created_by_id',$this->customer->id);
			$epan_count = $epan->count()->getOne();

			$form->model['billing_address'] = $form['address'];
			$form->model['billing_city'] = $form['city'];
			$form->model['billing_state_id'] = $form['state_id'];
			$form->model['billing_country_id'] = $form['country_id'];
			$form->model['billing_pincode'] = $form['pin_code'];
			$form->model['same_as_billing_address'] = 1;
			$form->model['shipping_address'] = $form['address'];
			$form->model['shipping_city'] = $form['city'];
			$form->model['shipping_state_id'] = $form['state_id'];
			$form->model['shipping_country_id'] = $form['country_id'];
			$form->model['shipping_pincode'] = $form['pin_code'];

			$form->save();

			$em = $this->add('xepan\base\Model_Contact_Email');
			$em->addCondition('value',$form['email_id']);
			$em->addCondition('contact_id',$this->customer->id);
			$em->tryLoadAny();
			if(!$em->loaded())
				$form->model->addEmail($form['email_id'],'Personal',true,true,'email_id',true);

			$pm = $this->add('xepan\base\Model_Contact_Phone');
			$pm->addCondition('value',$form['phone_no']);
			$pm->addCondition('contact_id',$this->customer->id);
			$pm->tryLoadAny();
			if(!$pm->loaded())
				$form->model->addPhone($form['phone_no'],'Personal',true,true,'phone_no',true);

			if(!$epan_count OR ($_GET['profile'] == "incomplete")){
				$this->app->stickyforget('profile');
				$form->js()->univ()->redirect($this->app->url('new-account'))->execute();
			}
				
			$form->js(null,$form->js()->reload())->univ()->successMessage('Profile Updated Successfully')->execute();
		}
	}


	function changePassword(){

		// change password form	
		$col = $this->add('Columns');
		$col->addColumn(4);
		$col2 = $col->addColumn(4);

		$user = $this->add('xepan\base\Model_User')->load($this->api->auth->model->id);
		$this->api->auth->addEncryptionHook($user);

		$change_pass_form = $col2->add('Form');
		$change_pass_form->add('xepan\base\Controller_FLC')
			->addContentSpot()
			->layout([
					'user_name'=>'Change Password~c1~12',
					'old_password'=>'c1~12',
					'new_password'=>'c1~12',
					'retype_password'=>'c1~12'
				]);

		$change_pass_form->addField('user_name')->set($user['username'])->setAttr('disabled',true);
		$change_pass_form->addField('password','old_password')->validate('required');
		$change_pass_form->addField('password','new_password')->validate('required');
		$change_pass_form->addField('password','retype_password')->validate('required');
		$change_pass_form->addSubmit('Update Password')->addClass('btn btn-primary btn-block');

		if($change_pass_form->isSubmitted()){
			if( $change_pass_form['new_password'] != $change_pass_form['retype_password'])
				$change_pass_form->displayError('new_password','Password not match');
			
			if(!$this->api->auth->verifyCredentials($user['username'],$change_pass_form['old_password']))
				$change_pass_form->displayError('old_password','wrong password ');

			if($user->updatePassword($change_pass_form['new_password'])){				
				$this->app->auth->logout();
				$this->app->redirect($this->app->url('login'));
			}
			$change_pass_form->js()->univ()->errorMessage('some thing happen wrong')->execute();
		}
	}
}