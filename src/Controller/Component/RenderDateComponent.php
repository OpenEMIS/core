<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
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
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format');
        $key = $element->getKey();
        $value = $entity->{$key};

        if (!is_null($value)) {
            if ($value instanceof Date) {
                $value = $value->format($format);
            } else {
                $value = date($format, strtotime($value));
            }
        }
        return $value;
    }
}
