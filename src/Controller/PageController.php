<?php
namespace App\Controller;

use Cake\Event\Event;
use Page\Controller\PageController as BaseController;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use Cake\Routing\Router;

class PageController extends BaseController
{
    public $helpers = ['Page.Page'];

    public function initialize()
    {
        parent::initialize();

        $labels = [
            'openemis_no' => 'OpenEMIS ID',
            'modified' => 'Modified On',
            'modified_user_id' => 'Modified By',
            'created' => 'Created On',
            'created_user_id' => 'Created By'
        ];

        $this->Page->config('sequence', 'order');
        $this->Page->config('is_visible', 'visible');
        $this->Page->config('labels', $labels);

        $this->loadComponent('Page.RenderLink');
        $this->loadComponent('RenderDate');
        $this->loadComponent('RenderDatetime');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderBinary'] = 'onRenderBinary';
        return $events;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $page = $this->Page;
        $request = $this->request;
        $action = $request->action;
        $ext = $this->request->params['_ext'];

        if ($ext != 'json') {
            if ($request->is(['put', 'post'])) {
                $page->showElements(true);
            }
            $this->set('menuItemSelected', [$this->name]);

            if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
                $viewFile = 'Page.Page/' . $action;
                $this->viewBuilder()->template($viewFile);
            }
        }
    }

    public function onRenderBinary(Event $event, Entity $entity, PageElement $element)
    {
        $attributes = $element->getAttributes();
        $type = isset($attributes['type']) ? $attributes['type'] : 'binary';
        $attributes = $element->getAttributes();
        $fileNameField = $attributes['fileNameField'];
        $fileContentField = $element->getKey();
        if ($type == 'image') {
            switch ($this->request->getParam('action')) {
                case 'view':
                    $fileName = $entity->{$fileNameField};
                    $pathInfo = pathinfo($fileName);
                    if ($entity->{$fileContentField}) {
                        $file = stream_get_contents($entity->{$fileContentField});
                        $entity->{$fileNameField} = 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file);
                        return $entity->{$fileNameField};
                    }
                    break;
                case 'index':
                    $primaryKey = $entity->primaryKey;
                    $source = $entity->source();
                    if ($entity->{$fileContentField}) {
                        return Router::url([
                            'plugin' => null,
                            '_method' => 'GET',
                            'version' => 'v2',
                            'model' => $source,
                            'controller' => 'Restful',
                            'action' => 'image',
                            'id' => $primaryKey,
                            'fileName' => $fileNameField,
                            'fileContent' => $fileContentField,
                            '_ext' => 'json'
                        ], true);
                    }
                    break;
                default:
                    $fileName = $entity->{$fileNameField};
                    $pathInfo = pathinfo($fileName);
                    if ($entity->{$fileContentField}) {
                        $file = stream_get_contents($entity->{$fileContentField});
                        $returnValue = [
                            'extension' => $pathInfo['extension'],
                            'filename' => $fileName,
                            'src' => 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file)
                        ];
                        return $returnValue;
                    }
                    break;
            }
        } else {
            $primaryKey = $entity->primaryKey;
            $source = $entity->source();
            $fileName = $entity->{$fileNameField};
            $element->setAttributes('file_name', $fileName);
            if ($entity->{$fileContentField}) {
                return Router::url([
                    'plugin' => null,
                    '_method' => 'GET',
                    'version' => 'v2',
                    'model' => $source,
                    'controller' => 'Restful',
                    'action' => 'download',
                    'id' => $primaryKey,
                    'fileName' => $fileNameField,
                    'fileContent' => $fileContentField,
                    '_ext' => 'json'
                ], true);
            }
        }
    }
}
