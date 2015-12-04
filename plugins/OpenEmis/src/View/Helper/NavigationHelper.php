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

		$newHtml = $this->newSelect($this->getDashboard());
		pr($newHtml);
		pr(htmlspecialchars($newHtml));
		return $html;
	}

	public function getDashboard() {
		$navigation = [
			'Institutions.index' => ['title' => 'Institutions', 'selected' => ['Institutions.index'], 'collapse' => true, 'params' => ['plugin' => 'Institution']],
				'Institutions.dashboard' => 		['title' => 'Dashboard', 'parent' => 'Institutions.index', 'selected' => ['Institutions.dashboard'], 'collapse' => true, 'params' => ['plugin' => 'Institution']],
				'Institution.General' =>			['title' => 'General', 'parent' => 'Institutions.index'],
					'Institutions.view' => 				['title' => 'Overview', 'parent' => 'Institution.General', 'selected' => ['Institutions.view'],'collapse' => true, 'params' => ['plugin' => 'Institution']],
					'Institutions.Attachments.index' => ['title' => 'Attachments', 'parent' => 'Institution.General', 'selected' => ['Institutions.Attachments.index'],'collapse' => true, 'params' => ['plugin' => 'Institution']],
				'Institution.Students.index' =>			['title' => 'Students', 'parent' => 'Institutions.index'],
				'Guardians.index' => ['title' => 'Guardians', 'selected' => ['Guardians.index'], 'collapse' => true, 'params' => ['plugin' => 'Guardian']],
		];

		return $navigation;
	}

	public function newSelect($navigations) {
		// Processing variables
		$parentStack = [];
		$html = '';
		$index = 1;
		$level = 1;
		$isRoot = false;
		$hasUL = false;
		$closeUl = 0;
		$closeLi = 0;

		$a = '<a class="accordion-toggle %s" href="%s" data-toggle="%s" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-%s"><span>%s</span></a>';
		$ul = '<ul id="nav-menu-%s" class="nav %s" role="tabpanel" data-level="%s">';
		$class = 'nav-level-' . $level . ' collapse';		

		$controller = $this->request->params['controller'];
		$action = $this->request->params['action'];
		$pass = [];
		
		if (!empty($this->request->pass)) {
			$pass = $this->request->pass;
		} else {
			$pass[0] = '';
		}	

		$html .= sprintf($ul, $index, $class, $level);
		foreach ($navigations as $key => $value) {
			$aClass = 'panel-heading';
			// Root parent
			if (!isset($value['parent'])) {
				$html .= $this->closeUlTag($closeUl, $closeLi, true);
				$html .= '<li>';
				$closeLi++;
				$level = 2;
				// $ulSet = false;
				++$index;
				$html .= $value['title'];
				$parentStack = [];
				array_push($parentStack, $key);
				$hasUL = true;
			} 
			// Sub parents
			elseif ($value['parent'] != current(array_slice($parentStack, -1))) {
				++$index;
				if (in_array($value['parent'], $parentStack)) {
					$parentKey = array_search($value['parent'], $parentStack);
					while (count($parentStack) > ($parentKey + 1)) {
						array_pop($parentStack);
						$html .= $this->closeUlTag($closeUl, $closeLi);
					}
					$html .= $value['title'];
				} else {
					array_push($parentStack, $value['parent']);
					$html .= sprintf($ul, $index, $class, $level);
					$closeUl++;
				}
				$ulSet = false;
				++$level;
				$isRoot = false;
				$hasUL = false;
				$html .= '<li>';
				$html .= $value['title'];
			} 
			// Children
			else {
				$isRoot = false;
				if ($hasUL) {
					$html .= sprintf($ul, $index, $class, $level);
					$hasUL = false;
					$closeUl++;
				}
				$html .= '<li>';
				$html .= $value['title'];
				$closeLi++;
			}

			// if ($level > 1 && !$isRoot) {
			// 	$i = $level;
			// 	while ($i > 1) {
			// 		$html .= ' ';
			// 		$i--;
			// 	}
			// 	$html .= $value['title'].'<br />';
			// }
			// // Check if the ul for the sub level is already set
			// if (!$ulSet) {
			// 	$html .= sprintf($ul, $index, $class, $level);
			// }
			
		}

		$html .= $this->closeUlTag($closeUl, $closeLi, true);
		$html .= '</ul>';
		return $html;
	}

	public function closeUlTag(&$ulCounter, &$liCounter, $closeAll = false) {
		$html = '';
		if ($closeAll) {
			for($i = $ulCounter; $i > 0; $i--) {
				$html .= '</ul>';
				// $html .= '</li>';
				$liCounter--;
			}
			$ulCounter = 0;
			return $html;
		} else {
			$html .= '</ul>';
			// $html .= '</li>';
			$ulCounter--;
			$liCounter--;
			return $html;
		}
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
