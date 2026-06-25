<?php
namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class ThemesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // $this->loadComponent('Paginator');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name;
        $action  = $this->request->getParam('action');
        $actionName = __(Inflector::humanize($action));
        $header = $name .' - '.$actionName;
        $this->Navigation->addCrumb(__($name), ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb($actionName);
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $this->request->getParam('action'));

    }

    private function darkenColour($rgb, $darker = 2)
    {
        $hash = (strpos($rgb, '#') !== false) ? '#' : '';
        $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
        if (strlen($rgb) != 6) {
            return $hash.'000000';
        }
        $darker = ($darker > 1) ? $darker : 1;

        list($R16,$G16,$B16) = str_split($rgb, 2);

        $R = sprintf("%02X", floor(hexdec($R16)/$darker));
        $G = sprintf("%02X", floor(hexdec($G16)/$darker));
        $B = sprintf("%02X", floor(hexdec($B16)/$darker));

        return $hash.$R.$G.$B;
    }
}
