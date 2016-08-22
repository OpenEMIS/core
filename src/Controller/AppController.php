<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link	  http://cakephp.org CakePHP(tm) Project
 * @since	 0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use ControllerAction\Model\Traits\ControllerActionTrait;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
	use ControllerActionTrait;

	public $_productName = 'OpenEMIS Core';

	public $helpers = [
		'Text',

		// Custom Helper
		'ControllerAction.ControllerAction',
		'OpenEmis.Navigation',
		'OpenEmis.Resource'
	];

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['Controller.onUpdateProductList'] = 'onUpdateProductList';
		return $events;
	}

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		// ControllerActionComponent must be loaded before AuthComponent for it to work
		$this->loadComponent('ControllerAction.ControllerAction', [
			'ignoreFields' => ['modified_user_id', 'created_user_id', 'order']
		]);

		$this->loadComponent('Auth', [
			'authenticate' => [
				'Form' => [
					'userModel' => 'User.Users',
					'passwordHasher' => [
						'className' => 'Fallback',
						'hashers' => ['Default', 'Legacy']
					]
				],
			],
			'loginAction' => [
				'plugin' => 'User',
            	'controller' => 'Users',
            	'action' => 'login'
            ],
			'logoutRedirect' => [
				'plugin' => 'User',
				'controller' => 'Users',
				'action' => 'login'
			]
		]);

		$this->loadComponent('Paginator');

		$this->Auth->config('authorize', ['Security']);

		// Custom Components
		$this->loadComponent('Navigation');
		$this->loadComponent('Localization.Localization');
		$this->loadComponent('OpenEmis.OpenEmis', [
			'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
			'headerMenu' => [
				'Preferences' => [
					'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
				]
			],
			'theme' => 'core'
		]);

		// Angular initialization
		$this->loadComponent('Angular.Angular', [
			'app' => 'OE_Core',
			'modules' => [
				'bgDirectives', 'ui.bootstrap', 'ui.bootstrap-slider', 'ui.tab.scroll', 'agGrid', 'app.ctrl', 'advanced.search.ctrl', 'kd-elem-sizes', 'kd-angular-checkbox-radio'
			]
		]);

		$this->loadComponent('ControllerAction.Alert');
		$this->loadComponent('AccessControl', [
			'ignoreList' => [
				'Users' => ['login', 'logout', 'postLogin', 'login_remote'],
				'Dashboard' => [],
				'Preferences' => [],
				'About' => []
			]
		]);

		$this->loadComponent('Workflow.Workflow');
		$this->loadComponent('SSO.SSO', [
			'homePageURL' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
			'loginPageURL' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login'],
			'userModel' => 'User.Users',
			'cookie' => [
				'domain' => Configure::read('domain')
			]
		]); // for single sign on authentication
		$this->loadComponent('Security.SelectOptionsTampering');
		$this->loadComponent('Security', [
			'unlockedFields' => [
				'area_picker'
			],
			'unlockedActions' => [
				'postLogin'
			]
		]);
		$this->loadComponent('Csrf');
		if ($this->request->action == 'postLogin') {
            $this->eventManager()->off($this->Csrf);
        }
	}

	public function onUpdateProductList(Event $event, array $productList)
	{
		$displayProducts = [];
		$session = $this->request->session();
		if (!$session->check('ConfigProductLists.list')) {
			$ConfigProductLists = TableRegistry::get('Configuration.ConfigProductLists');
			$productListOptions = $ConfigProductLists-> find('list', [
								    'keyField' => 'name',
								    'valueField' => 'url'
								])
								-> toArray();

	        $productListData = $productListOptions;
	        $productListData[$this->_productName] = '';
	        $productLists = array_diff_key($productList, $productListData);
	        foreach ($productLists as $product => $value) {
	            $data = [
	                'name' => $product,
	                'url' => ''
	            ];
	            $entity = $ConfigProductLists->newEntity($data);
	            $ConfigProductLists->save($entity);
	        }

			foreach ($productList as $name => $item) {
				if (!empty($productListOptions[$name])) {
					$displayProducts[$name] = [
						'name' => $item['name'],
						'icon' => $item['icon'],
						'url' => $productListOptions[$name]
					];
				}
			}

			$session->write('ConfigProductLists.list', $displayProducts);
		} else {
			$displayProducts = $session->read('ConfigProductLists.list');
		}

		return $displayProducts;
	}
}
