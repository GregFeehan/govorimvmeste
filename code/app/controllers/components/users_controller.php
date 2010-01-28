<?php
App::import('Sanitize');
class UsersController extends AppController 
{
	var $paginate = array('limit' => 25, 'order' => array('User.id' => 'asc'));

	var $name = 'Users';
	var $helpers = array('Html', 'Form', 'Javascript', 'Mailto', 'Multicheckbox');
	var $components = array('Recaptcha', 'PasswordHelper', 'Mailer', 'LanguageHelper','ProjectnameHelper');
	var $def_redirect = array('action' => 'home');

	function mailto()
	{
	}
	
	function beforeFilter() 
	{
		parent::beforeFilter(); 
		//$this->Auth->allowedActions = array('*');
		$this->Auth->allowedActions = array('search', 'add', 'home', 'view', 'forgot', 'logout', 'email', 'activate');
		$this->Auth->autoRedirect = false;
		$this->Recaptcha->publickey = Configure::read('publickey'); 
		$this->Recaptcha->privatekey = Configure::read('privatekey'); 
	}
	
	function beforeRender()
	{
		$model = $this->modelClass;
		foreach ($this->$model->_schema as $var => $field) 
		{ 
			if (strpos($field['type'], 'enum') === FALSE) 
				continue; 

			preg_match_all("/\'([^\']+)\'/", $field['type'], $strEnum); 

			if (is_array($strEnum[1])) 
			{			
				$varName = Inflector::variable(Inflector::pluralize($var)); 
				$options = array(); 
				foreach ($strEnum[1] as $value) 
				{ 
					$options[$value] = __($value, true); 
				}
				$this->set($varName, $options); 
			} 
		}
	}
	
	function login() 
	{
		$this->pageTitle = __('Sign in', true);
		$this->Session->write('current_menu', 'edit_sign');
        if (!$this->Auth->user())  
        {  
            return;  
        }  
  
        if (empty($this->data))  
        {  
            $this->redirect($this->Auth->redirect());  
        } 
		else
		{
			if ($this->Auth->login($this->data))
			{
				// Retrieve user data
				$results =  $this->User->find(array('User.email' => $this->data['User']['email']), array('User.active'), null, false);
				// Check to see if the User�s account isn�t active
				if ($results['User']['active'] == 0) 
				{
						// Uh Oh!
						$this->Session->setFlash(__('account_not_activated', true));
						$this->Auth->logout();
						$this->redirect($this->def_redirect);
				}
				else
				{
					$this->User->id = $this->Auth->user('id');
					$this->User->saveField('last_login', date('Y-m-d H:i:s'),true);
					
					if (empty($this->data['User']['remember_me']))  
					{  
						if (isset($this->RememberMe))
							$this->RememberMe->delete();  
					}  
					else  
					{  
						$this->RememberMe->remember  
							(  
								$this->data['User']['email'],  
								$this->data['User']['password']  
							);
					}  
			  
					//$this->Session->setFlash(__('You are logged in!', true));
					unset($this->data['User']['remember_me']);  

					$this->redirect($this->Auth->redirect());
				}
			}
		}
  
		//$this->redirect($this->Auth->redirect());
	}
	 
	function logout() 
	{
	
		if (isset($this->RememberMe) && $this->RememberMe)
			$this->RememberMe->delete();  
		//$this->Session->setFlash(__('Good-Bye', true));
		$this->redirect($this->Auth->logout());	
	}
	
	function index() 
	{
		$this->User->recursive = 0;
		$this->set('users', $this->paginate());
	}

	function isAdmin()
	{
		// if not logged in, return false
		$tmp = $this->Auth->user();
		if (!isset($tmp))
			return false;
		
		$group_id = $this->Auth->user('group_id');
		$user_id = $this->Auth->user('id');
		$this->User->Group->recursive = 0;
		$group = $this->User->Group->findById($group_id);
		if (strncmp($group['Group']['name'], "users", 5) == 0)
			return false;
		
		return true;
	}
	
	function view($id = null) 
	{
		$this->pageTitle = __('Person details', true);
		if (!$id) 
		{
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect($this->def_redirect);
		}
		if (!$this->_isValidId($id))
		{
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect($this->def_redirect);
		}
		
		$this->User->recursive = 1;
		$this->set('user', $this->User->read(null, $id));

		//$offers = $this->User->Offer->find('list');
		//$wants = $this->User->Want->find('list');
		$cities = $this->User->City->find('list');
		$countries = $this->User->Country->find('list');
		$groups = array();
		if ($this->isAdmin())
		{
			$groups = $this->User->Group->find('list');
		}
		//$this->set(compact('offers','wants','cities','countries', 'groups'));
		$this->set(compact('cities','countries', 'groups'));
	}

	function search()
	{
		$this->Session->write('current_menu', 'search');
		$allowed = array('0','1','2','3','4','5','6','7','8','9');
		$this->pageTitle = __('Search for the partner', true);
		//print_r($this->passedArgs);
		
		if (isset($this->params['url']['language']) || !empty($this->passedArgs))
		{
			$page = 1;
			if (isset($this->passedArgs['page'])) 
			{
				$page = $this->passedArgs['page'];
			}
			
			if (isset($this->params['url']['language']))
				$language_id = Sanitize::paranoid($this->params['url']['language'], $allowed);
			else
				$language_id = 0;
			if (isset($this->params['url']['country']))
				$country_id = Sanitize::paranoid($this->params['url']['country'], $allowed);
			else
				$country_id = 0;

			if (isset($this->params['url']['city']))
				$city_id = Sanitize::paranoid($this->params['url']['city'], $allowed);
			else
				$city_id = 0;
				
			// if (isset($this->passedArgs['language']))
			// {
				// $language_id = $this->passedArgs['language'];
				// $country_id= $this->passedArgs['country'];
				// $city_id = $this->passedArgs['city'];
			// }
			// else
			// {
				// //if (isset($this->params['url']['language']))
				// //{
					// $language_id = $this->params['url']['language'];
				// //}
				// //elseif (isset($this->data['User']['language']))
				// //{
					// //$languages = $this->data['User']['language'];
				// //}
				
				// // get information about countries
				// $country_id = $this->params['url']['country'];
				// // if (isset($this->data['country']))
				// // {
					// // $countries = $this->data['country'];
				// // }
				// // elseif (isset($this->data['User']['country']))
				// // {
					// // $countries = $this->data['User']['country'];
				// // }

				// // get information about city
				// $city_id = $this->params['url']['city'];
				// // if (isset($this->data['city']))
				// // {
					// // $cities = $this->data['city'];
				// // }
				// // elseif (isset($this->data['User']['city']))
				// // {
					// // $cities = $this->data['User']['city'];
				// // }

				// // if (isset($languages) && isset($countries) && isset($cities))
				// // {
					// // $language_id = $languages[0];
					// // $country_id = $countries[0];
					// // $city_id = $cities[0];
				// // }
				// // else
				// // {
					// // $this->Session->setFlash(__('No idndices', true));
					// // return; //TODO:
				// // }
			// }
				
			$this->User->bindModel(array('hasOne' => array('LanguagesUsers')), false);
			$conditions = array('LanguagesUsers.offer' => 1, 'LanguagesUsers.language_id' => $language_id);
			if (strcmp($country_id, '0') != 0)
			{
				$conditions = array_merge($conditions, array('Country.id' => $country_id) );
			}
			if (strcmp($city_id, '0') != 0)
			{
				$conditions = array_merge($conditions, array('City.id' => $city_id) );
			}
			//print_r($conditions);
			$this->User->recursive = 1;
			$users = $this->paginate($conditions);
			
			//print_r($users);
			$this->set('users', $users);
		}
		else
		{
			//$this->redirect('/');
		}
	}

//------------------------------------------------------------------------------------------------	
	function _get_levels(&$offer_selected, &$levels)
	{
		$offer_levels = array();
		for ($j = 0; $j < count($offer_selected); $j++)
		{
			// take value from offer_selected, find index 
			$level_index = $offer_selected[$j] - 1;
			$level_value = $levels[$level_index];
			$offer_levels[$offer_selected[$j]] = $level_value;
		}
		return $offer_levels;
	}
	
//------------------------------------------------------------------------------------------------	
	function add() 
	{
		$this->Session->write('current_menu', 'logged');
		$this->pageTitle = __('Create an account', true);
		App::import('Controller', 'LanguagesUsers');
		$LangsUsers = new LanguagesUsersController;
		$LangsUsers->constructClasses();

		$user_id = $this->Auth->user('id');
		if (isset($user_id) && $user_id > 0)
		{
			$this->redirect($this->def_redirect);
		}
		
		if (!empty($this->data)) 
		{
			$this->User->set($this->data);

			$offer_levels = $this->_get_levels($this->data['User']['offer'], $this->data['User']['level']['offer']);
			
			if (!$this->User->validates()) 
			{
				$this->set('errors', $this->User->validationErrors);
				$this->data['User']['password'] = "";
			}
			else
			{
				if (!$this->Recaptcha->valid($this->params['form']))
				{
					$this->data['User']['password'] = "";
					$this->Session->setFlash(__('Captcha is incorrect', true));
				}
				else
				{
					//print_r($this->data);
					//$this->data['User']['password'] = $this->Auth->password($this->data['User']['password']);
					$this->data['User']['message'] = Sanitize::html($this->data['User']['message']);
					$this->User->data = Sanitize::clean($this->data);
					if (!$this->isAdmin())
					{
						$group = $this->User->Group->findByName('users');
						$group_id = $group['Group']['id'];
						$this->data['User']['group_id'] = $group_id;
					}
					$this->User->create();
					//if ($this->User->saveAll($this->data, array('validate' => false) )) 
					if ($this->User->save($this->data)) 
					{
						//$this->Auth->login($this->data);
						
						$this->data['User']['password'] = "";
						//TODO: probably we need to check the email field
						$params = array(
							'conditions' => array('User.email' => $this->data['User']['email']),
							'recursive' => 0,
							'fields' => array('User.id')
							);
						//$res = $this->User->find('first', $params);
						//$user_id = $res['User']['id'];
						$user_id = $this->User->getLastInsertID();
						
						$user = $this->data['User'];
						$offers = $user['offer'];
						foreach ($this->data['User']['offer'] as $offer_language_id)
						{
							$data['language_id'] = $offer_language_id;
							$data['user_id'] = $user_id;
							$data['offer'] = 1;
							$data['level'] = $offer_levels[$offer_language_id];
									
							$LangsUsers->LanguagesUser->create();
							$LangsUsers->LanguagesUser->save($data);
						}
						unset($data);

						$wants = $user['want'];
						foreach ($this->data['User']['want'] as $want_language_id)
						{
							$data['language_id'] = $want_language_id;
							$data['user_id'] = $user_id;
							$data['offer'] = 0;
							$LangsUsers->LanguagesUser->create();
							$LangsUsers->LanguagesUser->save($data);
						}
						unset($data);
						
						$name = $this->data['User']['name'] . " " . $this->data['User']['surname'];
						//$this->_send_greetings($user_id, $name, $this->data['User']['email']);
						$this->__send_activation_email($user_id, $name, $this->data['User']['email']);
						
						$this->Session->setFlash(__('thanks_for_registration', true));
						$this->redirect($this->def_redirect);
					} 
					else 
					{
							$this->data['User']['password'] = "";
							$this->Session->setFlash(__('The User could not be saved. Please, try again.', true));
					}
				}
			} // validation
		}
		App::import('Controller', 'Languages');
		$Languages = new LanguagesController;
		$Languages->constructClasses();

		
		$offer = $Languages->Language->find('list');
		$want = $offer;

		$cities = $this->User->City->find('list');
		$countries = $this->User->Country->find('list');
		$groups = array();
		if ($this->isAdmin())
		{
			$groups = $this->User->Group->find('list');
		}
		$this->set(compact('cities', 'countries', 'offer', 'want', 'groups'));
	}

//------------------------------------------------------------------------------------------------	
	function edit($id = null) 
	{
		$this->Session->write('current_menu', 'edit_sign');
		App::import('Controller', 'LanguagesUsers');
		$LangsUsers = new LanguagesUsersController;
		$LangsUsers->constructClasses();

		$this->pageTitle = __('Edit personal data', true);
		$user_id = $this->Auth->user('id');
		//echo "saved id: $user_id \n";
		if (!$id && empty($this->data)) 
		{
			$this->Session->setFlash(__('Invalid user', true));
			//$this->redirect(array('action'=>'index'));
			$this->redirect($this->def_redirect);
		}
		
		$offer = $this->User->Offer->find('list');
		$want = $this->User->Want->find('list');
		$cities = $this->User->City->find('list');
		$countries = $this->User->Country->find('list');
		$groups = array();
		if ($this->isAdmin())
		{
			$groups = $this->User->Group->find('list');
		}

		$params = array(
			'conditions' => array('LanguagesUser.user_id' => $user_id, 'LanguagesUser.offer' => 1),
			'recursive' => 0,
			'fields' => array('LanguagesUser.language_id', 'LanguagesUser.level')
			);
		$levels = $LangsUsers->LanguagesUser->find('list', $params);

		$this->set(compact('offer','want','cities','countries', 'groups', 'levels'));
		
		if (!empty($this->data)) 
		{
			if ($id == $user_id)
			{
				if (!$this->isAdmin())
				{
					// add to users group
					$group = $this->User->Group->findByName('users');
					$group_id = $group['Group']['id'];
					$this->data['User']['group_id'] = $group_id;
				}
				
				// see if users has changed the password
				if ((isset($this->data['User']['oldpassword']) && isset($this->data['User']['newpassword'])) 
					&& ($this->data['User']['oldpassword'] != null && $this->data['User']['newpassword'] != null) )
				{
					$hashed = $this->Auth->password($this->data['User']['oldpassword']);
					$this->User->recursive = 0;
					$user = $this->User->findByPassword($hashed);
					//print_r($user);
					if (isset($user) && ($user['User']['id'] == $id))
					{
						$this->data['User']['password'] = $this->Auth->password($this->data['User']['newpassword']);
					}
					else
					{
						$this->Session->setFlash(__('You have entered the wrong password', true));
						$this->redirect(array('action' => 'edit', $id));
					}
				}
				
				$offer_levels = $this->_get_levels($this->data['User']['offer'], $this->data['User']['level']['offer']);
				
				if ($this->User->save($this->data)) 
				{
					$this->Session->setFlash(__('The User has been saved', true));
					$this->data['User']['oldpassword'] = "";
					$this->data['User']['newpassword'] = "";
					$this->data['User']['password'] = "";

					// update session
					$this->Session->write('Auth.User.name', $this->data['User']['name']);
					$this->Session->write('Auth.User.surname', $this->data['User']['surname']);

					// delete all from offers
					$LangsUsers->LanguagesUser->deleteAll(array('offer' => 1, 'user_id' => $this->User->id), false);
					$user_id = $this->User->id;
					
					$user = $this->data['User'];
					$offers = $user['offe