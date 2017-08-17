<?php
namespace Page\View\Helper;

use ArrayObject;

use Cake\View\Helper;

class NavigationHelper extends Helper
{
    public $helpers = ['Html'];

    private $html = '<ul id="nav-menu-1" class="nav nav-level-1 collapse in" role="tabpanel" data-level="1">';
    private $menuGroup = [];
    private $menuCount = 1;

    public function addMenuGroup($title, $attr = [])
    {
        $i = ++$this->menuCount;
        $menuId = 'nav-menu-' . $i;
        $isLink = array_key_exists('href', $attr);
        $linkAttr = ['escape' => false];
        $href = '#' . $menuId;
        $title = __($title);

        if (!$isLink) {
            $linkAttr = array_merge($linkAttr, [
                'class' => 'accordion-toggle panel-heading collapsed',
                'data-toggle' => 'collapse',
                'data-parent' => '#accordion',
                'aria-expanded' => 'true',
                'aria-controls' => $menuId
            ]);
        } else {
            $href = $attr['href'];
            $linkAttr['id'] = implode('-', [$href['plugin'], $href['controller'], $href['action']]);
        }

        if (array_key_exists('icon', $attr)) {
            $title = sprintf('<span><i class="%s"></i></span><b>%s</b>', $attr['icon'], $title);
        }

        if ($this->request->query('querystring') && is_array($href)) {
            $href['querystring'] = $this->request->query('querystring');
        }

        $this->menuGroup[] = [
            'id' => $menuId,
            'link' => '<li>' . $this->Html->link($title, $href, $linkAttr),
            'items' => []
        ];
        return $this;
    }

    public function endMenuGroup()
    {
        $i = count($this->menuGroup) + 1;
        $menu = array_pop($this->menuGroup);
        $html = $menu['link'];

        if (count($menu['items']) > 0) {
            $html .= '<ul id="' . $menu['id'] . '" class="nav nav-level-' . $i . ' collapse" role="tabpanel" data-level="' . $i . '">';
            foreach ($menu['items'] as $item) {
                $html .= $item;
            }
            $html .= '</ul>';
        }
        $html .= '</li>';

        if (count($this->menuGroup) > 0) {
            $i = count($this->menuGroup) - 1;
            $this->menuGroup[$i]['items'][] = $html;
        } else {
            $this->html .= $html;
        }
        return $this;
    }

    public function addMenuItem($title, $attr)
    {
        $i = count($this->menuGroup) - 1;
        $title = __($title);
        $options = ['id' => implode('-', [$attr['plugin'], $attr['controller'], $attr['action']])];
        $li = '<li>%s</li>';
        if ($this->request->query('querystring')) {
            $attr['querystring'] = $this->request->query('querystring');
        }

        $html = sprintf($li, $this->Html->link($title, $attr, $options));
        $this->menuGroup[$i]['items'][] = $html;

        return $this;
    }

    public function end()
    {
        $this->html .= '</ul>';
        return $this->html;
    }
}
