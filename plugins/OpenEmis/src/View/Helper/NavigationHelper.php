<?php
namespace OpenEmis\View\Helper;

use Cake\View\Helper;

class NavigationHelper extends Helper {
	public $helpers = ['Html', 'Url'];

	public function render($navigations) {
		$html = '';
		$path = array();
		$level = 0;
		$this->select($navigations, $path, $level);
		$level = 0;
		$index = 1;
		$this->getMenu($navigations, $html, $level, $index, $path);
		return $html;
	}

	public function select($navigations, &$path, &$level) {
		$controller = $this->request->params['controller'];
		$action = $this->request->params['action'];
		$pass = $this->request->pass;
		if ($level != -1) {
			$level++;
			if (array_key_exists('items', $navigations)) {
				foreach ($navigations['items'] as $name => $attr) {
					$path[$level] = $name;
					if (array_key_exists('items', $attr)) {
						$this->select($attr, $path, $level);
						unset($path[$level]);
						--$level;
					} else {
						$selected = isset($attr['selected']) ? $attr['selected'] : [];
						$url = $controller.'.'.$action;
						$navControllerActionKey = $this->searchSelected($url, $selected);
						if (!empty($pass[0])) {
							$url .= '.'.$pass[0];
						}
						$navControllerActionPassKey = $this->searchSelected($url, $selected);
						if ($navControllerActionKey !== false || $navControllerActionPassKey !== false) {
							$level = -1;
						} elseif (strtolower($attr['url']['controller']) == strtolower($controller) 
							&& strtolower($attr['url']['action']) == strtolower($action)) {
							$level = -1;
						}
					}
				}
			}
		}
	}

	private function searchSelected($url, $selected) {
		return array_search($url, $selected);
	}

	public function getMenu($navigations, &$html, &$level, &$index, $path) {
		$controller = $this->request->params['controller'];
		$action = $this->request->params['action'];
		$pass = $this->request->pass;
		
		$a = '<a class="accordion-toggle %s" href="%s" data-toggle="%s" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-%s"><span>%s</span></a>';
		$ul = '<ul id="nav-menu-%s" class="nav %s" role="tabpanel" data-level="%s">';
		++$level;

		$class = 'nav-level-' . $level . ' collapse';
		$parent = '';
		if (array_key_exists('items', $navigations)) {
			foreach ($navigations['items'] as $name => $attr) {
				// Bug here
				// Same level, same name but different parent
				if (array_key_exists($level, $path) && $name == $path[$level]) {
					$class .= ' in';
					$parent = $name;
					break;
				}
			}

			$html .= sprintf($ul, $index, $class, $level);
			foreach ($navigations['items'] as $name => $attr) {
				$collapsed = false;
				if (array_key_exists($level, $path) && $name == $path[$level]) {
					$collapsed = true;
				}
				$html .= '<li>';
				$aClass = 'panel-heading';
				
				if (array_key_exists('items', $attr)) {
					if (!$collapsed) {
						$aClass .= ' collapsed';
					}

					++$index;
					$toggle = 'collapse';

					if (array_key_exists('url', $attr)) {
						$href = $this->Url->build($attr['url']);
						$toggle = '';
					} else {
						$href = '#nav-menu-' . $index;
					}

					// For icon
					if (array_key_exists('icon', $attr)) {
						$name = $attr['icon'].'<b>'.__($name).'</b>';
					} else {
						$name = __($name);
					}
					
					$html .= sprintf($a, $aClass, $href, $toggle, $index, $name);
	                
					$this->getMenu($attr, $html, $level, $index, $path);
					--$level;
				} else {
					$aOptions = ['escape' => false];
					$selected = isset($attr['selected']) ? $attr['selected'] : [];
					$url = $controller.'.'.$action;
					$navControllerActionKey = $this->searchSelected($url, $selected);
					if (!empty($pass[0])) {
						$url .= '.'.$pass[0];
					}
					$navControllerActionPassKey = $this->searchSelected($url, $selected);

					if ($navControllerActionKey !== false || $navControllerActionPassKey !== false) {
						$aOptions['class'] = 'nav-active';
					} elseif (strtolower($attr['url']['controller']) == strtolower($controller) 
							&& strtolower($attr['url']['action']) == strtolower($action)) {
						$aOptions['class'] = 'nav-active';
					}
					if (array_key_exists('icon', $attr)) {
						$name = $attr['icon'].'<b>'.__($name).'</b>';
					} else {
						$name = __($name);
					}
					$html .= $this->Html->link($name, $attr['url'], $aOptions);
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
	}
}
