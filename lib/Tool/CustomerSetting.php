<?php

namespace xepan\epanservices;

class Tool_CustomerSetting extends \xepan\cms\View_Tool {

	public $options = [];
	public $customer;

	function init(){
		parent::init();
		
		if($this->owner instanceof \AbstractController) return;

		$this->customer = $customer = $this->add('xepan\commerce\Model_Customer')
							->addCondition('user_id',$this->app->auth->model->id);
		$customer->loadLoggedIn();

		if(!$customer->loaded()){
			$this->add('View_Error')->set('you are not a customer.');
			return;
		}

		if($_GET['profile'] == "incomplete")
			$this->add('View_Info')->set('Update Your Profile First')->addClass('alert alert-info text-center');

		$col = $this->add('Columns');
		$col1 = $col->addColumn(8);
		$col2 = $col->addColumn(4);

		$col1->add('View')->setElement('h2')->set('Profile');
		$form = $col1->add('Form');
		$form->add('xepan\base\Controller_FLC')
				->addContentSpot()
				->layout([
						'first_name'=>'Basic Profile Detail~c1~4',
						'last_name'=>'c2~4',
						'organization'=>'c3~4',
						'country_id~Country'=>'Address~c4~6',
						'state_id~State'=>'c4~6',
						'city'=>'c4~6',
						'pin_code'=>'c4~6',
						'address'=>'c7~6',
						'email_id'=>'Contact Detail~c8~6',
						'phone_no'=>'c9~6',
					]);
		$fields = ['first_name','last_name','country_id','state_id','organization','address','city','pin_code'];
		$form->setModel($customer,$fields);
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
		$email_field->set($customer->getEmails()[0]);
		$phone_no_field->set($customer->getPhones()[0]);

		foreach ($fields as $key => $field_name) {
			$form->getElement($field_name)->validate('required');
		}

		$form->addSubmit('Update Profile')->addClass('btn btn-primary');

		if($form->isSubmitted()){

			$form->save();
			$form->model->addEmail($form['email_id'],'Personal',true,true,'email_id',true);
			$form->model->addPhone($form['phone_no'],'Personal',true,true,'phone_no',true);
			$form->js(null,$form->js()->reload())->univ()->successMessage('Profile Updated Successfully')->execute();
		}

		
		// change password form
		$col2->add('View')->setElement('h2')->set('Change Password');
		$user = $col2->add('xepan\base\Model_User')->load($this->api->auth->model->id);
		$this->api->auth->addEncryptionHook($user);

		$change_pass_form = $col2->add('Form');
		$change_pass_form->add('xepan\base\Controller_FLC')
			->addContentSpot()
			->layout([
					'user_name'=>'want to update password ~c1~12',
					'old_password'=>'c1~12',
					'new_password'=>'c1~12',
					'retype_password'=>'c1~12',
					'FormButtons~'=>'c1~12'
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