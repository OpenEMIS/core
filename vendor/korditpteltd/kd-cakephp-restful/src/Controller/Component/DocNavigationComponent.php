<?php
namespace Restful\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class DocNavigationComponent extends Component {
	public $controller;
	public $action;

	// PHP 5.5 array_column alternative
	public function array_column($array, $column_name) {
        return array_map(
        	function($element) use($column_name) {
        		if (isset($element[$column_name])) {
        			return $element[$column_name];
        		}
       		}, $array);
    }

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Controller.initialize'] = ['callable' => 'beforeFilter', 'priority' => '11'];
		return $events;
	}

	public function beforeFilter(Event $event) {
		$controller = $this->controller;
		$navigations = $this->_buildNavigation();
		$this->_checkSelectedLink($navigations);
		$controller->set('_navigations', $navigations);
	}

	private function _buildNavigation() {
		$navigations = $this->_getMainNavigation();

		$controller = $this->controller;
		$action = $this->action;
		$pass = [];
		if (!empty($this->request->pass)) {
			$pass = $this->request->pass;
		} else {
			$pass[0] = '';
		}

		// $navigations = $this->_appendNavigation('Default', $navigations, $this->_getDefaultNavigation());

		return $navigations;
	}

	private function _getMainNavigation() {
		$navigation = [
			'restful.doc.index' => [
				'title' => 'Main', 
				'icon' => '<span><i class="fa fa-home"></i></span>'
			],
			'restful.doc.listing' => [
				'title' => 'List Operation',
				'icon' => '<span><i class="fa fa-list"></i></span>'
			],
			'restful.doc.viewing' => [
				'title' => 'View Operation',
				'icon' => '<span><i class="fa fa-eye"></i></span>'
			],
			'restful.doc.adding' => [
				'title' => 'Add Operation',
				'icon' => '<span><i class="fa fa-plus"></i></span>'
			],
			'restful.doc.editing' => [
				'title' => 'Edit Operation',
				'icon' => '<span><i class="fa fa-edit"></i></span>'
			],
			'restful.doc.deleting' => [
				'title' => 'Delete Operation',
				'icon' => '<span><i class="fa fa-trash"></i></span>'
			],
			'restful.doc.curl' => [
				'title' => 'Using CURL',
				'icon' => '<span><i class="fa fa-arrows-h"></i></span>'
			],
		];

		return $navigation;
	}

	private function _appendNavigation($key, $originalNavigation, $navigationToAppend) {
		$count = 0;
		foreach ($originalNavigation as $navigationKey => $navigationValue) {
			$count++;
			if ($navigationKey == $key) {
				break;
			}
		}
		$result = [];
		if ($count < count($originalNavigation)) {
			$result = array_slice($originalNavigation, 0, $count, true) + $navigationToAppend + array_slice($originalNavigation, $count, count($originalNavigation) - 1, true) ;
		} elseif ($count == count($originalNavigation)) {
			$result = $originalNavigation + $navigationToAppend;
		} else {
			$result = $originalNavigation;
		}
		return $result;
	}

	private function _getDefaultNavigation() {
		$navigation = [
			'SystemSetup' => [
				'title' => 'System Setup',
				'parent' => 'restful.index',
				'link' => false,
			],
				'Areas.Areas' => [
					'title' => 'Administrative Boundaries', 
					'parent' => 'SystemSetup', 
					'params' => ['plugin' => 'Area'], 
					'selected' => ['Areas.Areas', 'Areas.Levels', 'Areas.AdministrativeLevels', 'Areas.Administratives']
				],

			'Workflows.Workflows' => [
				'title' => 'Workflow',
				'parent' => 'Administration',
				'params' => ['plugin' => 'Workflow'],
				'selected' => ['Workflows.Workflows', 'Workflows.Steps', 'Workflows.Statuses']
			],
		];
		return $navigation;
	}

	private function _getLink($controllerActionModelLink, $params = []) {
		$url = ['plugin' => null, 'controller' => null, 'action' => null];
		if (isset($params['plugin'])) {
			$url['plugin'] = $params['plugin'];
			unset($params['plugin']);
		}
		$link = explode('.', $controllerActionModelLink);
		if (isset($link[0])) {
			$url['controller'] = $link[0];
		}
		if (isset($link[1])) {
			$url['action'] = $link[1];
		}
		if (isset($link[2])) {
			$url['0'] = $link[2];
		}
		if (!empty($params)) {
			$url = array_merge($url, $params);
		}
		return $url;
	}

	private function _checkSelectedLink(array &$navigations) {
		// Set the pass variable
		if (!empty($this->request->pass)) {
			$pass = $this->request->pass;
		} else {
			$pass[0] = '';
		}

		// The URL name "Controller.Action.Model or Controller.Action"
		$controller = $this->controller->name;
		$action = $this->action;
		$linkName = $controller.'.'.$action;
		$controllerActionLink = $linkName;
		if (!empty($pass[0])) {
			$linkName .= '.'.$pass[0];
		}
		if (!in_array($linkName, $navigations)) {
			$selectedArray = $this->array_column($navigations, 'selected');
			foreach($selectedArray as $k => $selected) {
				if (is_array($selected) && (in_array($linkName, $selected) || in_array($controllerActionLink, $selected))) {
					$linkName = $k;
					break;
				}
			}
		}
		$children = $this->array_column($navigations, 'parent');
		foreach ($children as $key => $child) {
			if ($child == $linkName) {
				unset($navigations[$key]);
			}
		}
	}

}
