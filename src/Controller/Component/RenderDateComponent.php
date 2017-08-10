<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\Log\Log;

use Page\Model\Entity\PageElement;

class RenderDateComponent extends Component
{
    private $controller = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    public function implementedEvents()
    {
        $eventMap = [
            'Controller.Page.onRenderDate' => ['callable' => 'onRenderDate', 'priority' => 5]
        ];

        $events = parent::implementedEvents();
        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method['callable'])) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function onRenderDate(Event $event, Entity $entity, PageElement $element)
    {
        $key = $element->getKey();
        $value = $entity->{$key};
        $format = 'Y-m-d';
        if ($value instanceof Date) {
            $value = $value->format('Y-m-d');
        } else {
            $value = date('Y-m-d', strtotime($value));
        }
        return $value;
    }
}
