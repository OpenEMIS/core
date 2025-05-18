<?php
namespace OpenEmis\View\Helper;

use Cake\View\Helper;
use Cake\Http\ServerRequest;
use Cake\Log\Log;

class NavigationHelper extends Helper
{
    public $helpers = ['Html', 'Url'];

    public function render($navigations)
    {
        return $this->printNavigation($navigations);
    }

    public function printNavigation($navigations)
    {
        //$serverRequest = $this->request;
        $parentStack = [];
        $html = '';
        $index = 1;
        $level = 1;
        $hasUL = false;
        $in = false;
        $closeUl = 0;
        $parentNodes = [];
        $a = '<a class="accordion-toggle %s" href="%s" data-toggle="%s" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-%s"><span>%s</span></a>';
        $ul = '<ul id="nav-menu-%s" class="nav %s" role="tabpanel" data-level="%s">';
        $class = 'nav-level-' . $level . ' collapse';
        $html .= sprintf($ul, $index++, ($class.' in'), $level);
        $controller = $this->_View->getRequest()->getParam('controller');
        $action = $this->_View->getRequest()->getParam('action');
        $pass = [];

        // Build all the parent nodes
        foreach ($navigations as $navigation) {
            if (isset($navigation['parent'])) {
                if (!in_array($navigation['parent'], $parentNodes)) {
                    $parentNodes[] = $navigation['parent'];
                }
            }
        }

        if (!empty($this->_View->getRequest()->getParam('pass'))) {
            $pass = $this->_View->getRequest()->getParam('pass');
        } else {
            $pass[0] = '';
        }

        // The URL name "Controller.Action.Model or Controller.Action"
        $linkName = $controller.'.'.$action;
        $controllerActionLink = $linkName;
        if (!empty($pass[0])) {
            $linkName .= '.'.$pass[0];
        }

        // Getting the full path of the from the node to the root
        $path = [];
        $this->getPath($linkName, $navigations, $path);
        if (empty($path)) {
            $this->getPath($controllerActionLink, $navigations, $path);
        }

        // Print each of the navigation
        foreach ($navigations as $key => $value) {
            // Root Node
            if (!isset($value['parent'])) {
                $html .= $this->closeUlTag($closeUl, true);
                $level = 2;
                $parentStack = [];
                array_push($parentStack, $key);
                $hasUL = true;
                // If the parent is part of the path and has children nodes in the the path
                if (in_array($key, $path) && $key != $linkName && $this->hasChildren($key, $parentNodes)) {
                    // If the link is in the root's selected list, do not expand as the root is selected
                    if (isset($value['selected'])) {
                        $in = true;
                        foreach ($value['selected'] as $selected) {
                            if ($selected == $linkName) {
                                $in = false;
                                // To implement collapsed logic so that the > arrow in the navigation bar will not appear if the link is selected
                                break;
                            }
                        }
                    } else {
                        $in = true;
                    }
                } elseif ($key == $linkName) {
                    // To implement collapsed logic so that the > arrow in the navigation bar will not appear if the link is selected
                }
            } elseif ($value['parent'] != current(array_slice($parentStack, -1))) {
                // Sub parents
                // If it is back to any of the other parents in the trunk
                if (in_array($value['parent'], $parentStack)) {
                    $parentKey = array_search($value['parent'], $parentStack);
                    while (count($parentStack) > ($parentKey + 1)) {
                        array_pop($parentStack);
                        $html .= $this->closeUlTag($closeUl);
                        $level--;
                    }
                } else {
                    // If the node is just below it's parent
                    array_push($parentStack, $value['parent']);
                    // Not to expand if the parent is the url and only to expand if the parent is found in the path
                    if (in_array($value['parent'], $path) && $value['parent'] != $linkName) {
                        $class = 'nav-level-' . $level . ' collapse';

                        // To check if the parent node's selected list contain the current link as selected
                        if (isset($navigations[$value['parent']]['selected'])) {
                            // If it contains the link in the selected
                            if (in_array($linkName, $navigations[$value['parent']]['selected']) || in_array($controllerActionLink, $navigations[$value['parent']]['selected'])) {
                                // do nothing
                            } else {
                                $class = $class.' in';
                            }
                        } else {
                            // If the parent does not have any selected values, just expand the navigation
                            $class = $class.' in';
                        }
                        $html .= sprintf($ul, $index++, $class, $level++);
                    } else {
                        $class = 'nav-level-' . $level . ' collapse';
                        $html .= sprintf($ul, $index++, $class, $level++);
                    }
                    $closeUl++;
                }
                $hasUL = false;
            } else {
                // Children
                // If the root set the has unorder list flag
                if ($hasUL) {
                    $class = 'nav-level-' . $level . ' collapse';
                    if ($in) {
                        $html .= sprintf($ul, $index++, $class.' in', $level++);
                        $in = false;
                    } else {
                        $html .= sprintf($ul, $index++, $class, $level++);
                    }
                    $hasUL = false;
                    $closeUl++;
                }
            }

            $aClass = 'panel-heading';
            // If the current link is the link then collapse the arrow, or if the key is not in the same path collapse the arrow
            if (!in_array($key, $path) || $key == $linkName) {
                $aClass .= ' collapsed';
            } elseif (isset($value['selected'])) {
                // If the value fall in the selected options
                if (in_array($linkName, $value['selected']) || in_array($controllerActionLink, $value['selected'])) {
                    $aClass .= ' collapsed';
                }
            }

            // For processing icons
            if (isset($value['icon'])) {
                $name = $value['icon'].'<b>'.__($value['title']).'</b>';
            } else {
                $name = __($value['title']);
            }
            $href = '#nav-menu-' . $index;
            $toggle = 'collapse';
            $html .= '<li>';

            // If the node has children
            if ($this->hasChildren($key, $parentNodes)) {
                // If the link flag is not set in the array, if there is a link flag then it will just be a parent without any url
                if (!isset($value['link'])) {
                    $params = [];
                    if (isset($value['params'])) {
                        $params = $value['params'];
                    }
                    $href = $this->Url->build($this->getLink($key, $params));
                    $toggle = '';
                }

                // Setting the selected navigation item for navigation items that has children
                if ($linkName == $key || $controllerActionLink == $key) {
                    $aClass .= ' nav-active';
                } elseif (isset($value['selected'])) {
                    if (in_array($linkName, $value['selected']) || in_array($controllerActionLink, $value['selected'])) {
                        $aClass .= ' nav-active';
                    }
                }

                $html .= sprintf($a, $aClass, $href, $toggle, $index, $name);
            } else {
                $params = [];
                $aOptions = ['escape' => false];
                if (isset($value['params'])) {
                    $params = $value['params'];
                }

                // Setting the selected navigation item for navigation items that does not have children
                // deprecated
                if ($linkName == $key || $controllerActionLink == $key) {
                    $aOptions['class'] = 'nav-active';
                } elseif (isset($value['selected'])) {
                    if (in_array($linkName, $value['selected']) || in_array($controllerActionLink, $value['selected'])) {
                        $aOptions['class'] = 'nav-active';
                    }
                }
                // end deprecated

                $url = $this->getLink($key, $params);
                $id = $url;
                if (isset($id['plugin'])) {
                    unset($id['plugin']);
                }
                $aOptions['id'] = implode('-', $id);
                if(!isset($url['action'])){//POCOR-7485 add for test
                    Log::debug(print_r($url, true));
                    $url['action'] = 'index';
                }
                //$html .= $this->Html->link($name, $url, $aOptions);
                $link = $this->Html->link($name, $url, $aOptions);//POCOR-7485 add for test
                $html .= $link;//POCOR-7485 add for test
            }
        }
        $html .= $this->closeUlTag($closeUl, true);
        $html .= '</ul>';
        return $html;
    }

    // Function to check if the children exist in the list of parent nodes array
    private function hasChildren($parentKey, $parentNodes)
    {
        return in_array($parentKey, $parentNodes);
    }

    // Function to get the path of the nodes in the navigation array
    private function getPath($node, $navigationArray, array &$path)
    {
        // If the array contains the node as the key
        if (isset($navigationArray[$node])) {
            $path[] = $node;
            // If the node contains a parent node, continue the recursive call
            if (isset($navigationArray[$node]['parent'])) {
                $node = $navigationArray[$node]['parent'];
                $this->getPath($node, $navigationArray, $path);
            }
        } else {
            // If the node is a selected value
            foreach ($navigationArray as $key => $value) {
                if (isset($value['selected'])) {
                    $found = false;
                    foreach ($value['selected'] as $selected) {
                        if ($selected == $node) {
                            $this->getPath($key, $navigationArray, $path);
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        break;
                    }
                }
            }
        }
    }

    // Function to generate the url from the key array
    private function getLink($controllerActionModelLink, $params = [])
    {
        $url = ['plugin' => null, 'controller' => null, 'action' => null];
        if (isset($params['plugin'])) {
            $url['plugin'] = $params['plugin'];
            unset($params['plugin']);
        }

        $link = explode('.', $controllerActionModelLink);
        if (sizeof($link) <= 3) {
            if (isset($params['controller'])) {
                $url['controller'] = $params['controller'];
                unset($params['controller']);
            } else if (isset($link[0])) {
                $url['controller'] = $link[0];
            }

            if (isset($params['action'])) {
                $url['action'] = $params['action'];
                unset($params['action']);
            } else if (isset($link[1])) {
                $url['action'] = $link[1];
            }

            if (isset($link[2])) {
                $url['0'] = $link[2];
            }
        }else{
            if (isset($params['plugin'])) {
                $url['plugin'] = $params['plugin'];
                unset($params['plugin']);
            } else if (isset($link[0])) {
                $url['plugin'] = $link[0];
            }
            if (isset($params['controller'])) {
                $url['controller'] = $params['controller'];
                unset($params['controller']);
            } else if (isset($link[1])) {
                $url['controller'] = $link[1];
            }

            if (isset($params['action'])) {
                $url['action'] = $params['action'];
                unset($params['action']);
            } else if (isset($link[2])) {
                $url['action'] = $link[2];
            }

            if (isset($link[3])) {
                $url['0'] = $link[3];
            }
        }
        if (!empty($params)) {
            $url = array_merge($url, $params);
        }
        return $url;
    }

    // Function to close the <ul> tags of the html. If the closeAll is set to true,
    // it will close all ul tags that are not close base on the counter
    private function closeUlTag(&$ulCounter, $closeAll = false)
    {
        $html = '';
        if ($closeAll) {
            for ($i = $ulCounter; $i > 0; $i--) {
                $html .= '</ul>';
                // $html .= '</li>';
            }
            $ulCounter = 0;
            return $html;
        } else {
            $html .= '</ul>';
            // $html .= '</li>';
            $ulCounter--;
            return $html;
        }
    }
}
