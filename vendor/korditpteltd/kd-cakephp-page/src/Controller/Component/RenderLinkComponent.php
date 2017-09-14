<?php
namespace Page\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Log\Log;

use Page\Model\Entity\PageElement;

class RenderLinkComponent extends Component
{
    private $controller = null;
    private $reservedKeys = [
        'controller',
        'action',
        'plugin',
        'pass',
        '_matchedRoute',
        '_Token',
        '_csrfToken',
        'paging'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    public function implementedEvents()
    {
        $eventMap = [
            'Controller.Page.onRenderLink' => ['callable' => 'onRenderLink', 'priority' => 5]
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

    public function onRenderLink(Event $event, Entity $entity, PageElement $element)
    {
        $request = $this->request;
        $requestParams = $request->params;
        foreach ($requestParams as $key => $value) {
            if (is_numeric($key) || in_array($key, $this->reservedKeys)) {
                unset($requestParams[$key]);
            }
        }

        $url = ['action' => 'download', $entity->primaryKey, $element->getKey()];
        $url = array_merge($url, $requestParams);
        $url = Router::url($url, true);

        $element->setAttributes('href', $url);
        return $value;
    }
}
