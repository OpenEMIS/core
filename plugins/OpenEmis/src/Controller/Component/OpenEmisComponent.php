<?php
namespace OpenEmis\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use OpenEmis\Model\Traits\ProductListsTrait;

class OpenEmisComponent extends Component {
	use ProductListsTrait;

	private $controller;
	protected $_defaultConfig = [
		'theme' => 'auto',
		'homeUrl' => ['controller' => '/'],
		'logoutUrl' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'],
		'headerMenu' => [
			'About' => [
				'url' => ['plugin' => false, 'controller' => 'About', 'action' => 'index'],
				'icon' => 'fa-info-circle'
			],
			'Preferences' => [
				'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index'],
				'icon' => 'fa-cog'
			],
			'Help' => [
				'url' => 'https://support.openemis.org/',
				'icon' => 'fa-question-circle',
				'target' => '_blank'
			]
		]
	];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
	}

	private function onEvent($subject, $eventKey, $method) {
		$eventMap = $subject->implementedEvents();
		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
	}

	private function dispatchEvent($subject, $eventKey, $method=null, $params=[], $autoOff=false) {
		$this->onEvent($subject, $eventKey, $method);
		$event = new Event($eventKey, $this, $params);
		$event = $subject->eventManager()->dispatch($event);
		if(!is_null($method) && $autoOff) {
			$this->offEvent($subject, $eventKey, $method);
		}
		return $event;
	}

	private function offEvent($subject, $eventKey, $method) {
		$subject->eventManager()->off($eventKey, [$subject, $method]);
	}

	public function getProductList()
	{
		$productList = $this->productList;
		$event = $this->dispatchEvent($this->controller, 'Controller.onUpdateProductList', 'onUpdateProductList', [$productList], true);

		if ($event->result || is_array($event->result)) {
			$productList = $event->result;
		}

		return $productList;
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		$controller = $this->controller;
		$session = $this->request->session();

		$displayProducts = $this->getProductList();

		$theme = $this->getTheme();
		$controller->set('theme', $theme);
		$controller->set('homeUrl', $this->config('homeUrl'));
		$controller->set('headerMenu', $this->getHeaderMenu());
		$controller->set('SystemVersion', $this->getCodeVersion());
		$controller->set('_productName', $controller->_productName);
		$controller->set('products', $displayProducts);
		$controller->set('showProductList', !empty($displayProducts));

		//Retriving the panel width size from session
		if ($session->check('System.layout')) {
			$layout = $session->read('System.layout');
			$controller->set('SystemLayout_leftPanel', 'width:'.$layout['panelLeft'].'px');
			$controller->set('SystemLayout_rightPanel','width:'.$layout['panelRight'].'px');
		} else {
			$controller->set('SystemLayout_leftPanel', 'width: 10%');
			$controller->set('SystemLayout_rightPanel','width: 90%');
		}
	}

	private function getTheme() {
		$controller = $this->controller;
		$session = $this->request->session();

		$theme = 'OpenEmis.themes/';
		$product = '';
		$css = Configure::read('debug') ? '/layout' : '/layout.min';
		if ($this->config('theme') == 'auto') {
			$query = $this->request->query;

			if (isset($query['theme'])) {
				$product = $query['theme'];
				$theme .= $product . $css;
				$session->write('theme.layout', $theme);
				$session->write('theme.product', $product);
			} else {
				$theme = $session->read('theme.layout');
				$product = $session->read('theme.product');
			}
			if (!empty($theme)) {
				$controller->_productName .= ' ' . Inflector::camelize($product);
			}
		} else {
			$theme .= $this->config('theme') . $css;
		}
		return $theme;
	}

	private function getHeaderMenu() {
		$headerMenu = $this->config('headerMenu');

		$headerMenu[] = '_divider';
		$headerMenu['Logout'] = [
			'url' => $this->config('logoutUrl'),
			'icon' => 'fa-power-off'
		];

		return $headerMenu;
	}

	public function getCodeVersion() {
		$path = 'version';
		$session = $this->request->session();
		$version = '';

		if (file_exists($path)) {
			$version = file_get_contents($path);
			$session->write('System.version', $version);
		} else if ($session->check('System.version')) {
			$version = $session->read('System.version');
		}
		return $version;
	}
}
