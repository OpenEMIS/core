<?php
namespace App\View\Helper;

use Cake\View\Helper;

class NavigationHelper extends Helper {
	public $helpers = ['Html'];

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
		$controller = $this->request->controller;
		$action = $this->request->action;
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
						if ($attr['url']['controller'] == $controller && $attr['url']['action'] == $action) {
							$level = -1;
						}
					}
				}
			}
		}
	}

	public function getMenu($navigations, &$html, &$level, &$index, $path) {
		$controller = $this->request->controller;
		$action = $this->request->action;
		
		$a = '<a class="accordion-toggle %s" href="#nav-menu-%s" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-%s"><span>%s</span></a>';
		$ul = '<ul id="nav-menu-%s" class="nav %s" role="tabpanel" data-level="%s">';
		++$level;

		$class = 'nav-level-' . $level . ' collapse';
		
		if (array_key_exists('items', $navigations)) {
			foreach ($navigations['items'] as $name => $attr) {
				if (array_key_exists($level, $path) && $name == $path[$level]) {
					$class .= ' in';
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
	                $html .= sprintf($a, $aClass, $index, $index, $name);
					$this->getMenu($attr, $html, $level, $index, $path);
					--$level;
				} else {
					$aOptions = array();
					if ($attr['url']['controller'] == $controller && $attr['url']['action'] == $action) {
						$aOptions['class'] = 'nav-active';
					}
					$html .= $this->Html->link($name, $attr['url'], $aOptions);
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
	}
}
