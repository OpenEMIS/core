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
        $fileNameField = isset($attributes['fileNameField']) ? $attributes['fileNameField'] : 'file_name';
        $fileContentField = $element->getKey();
        if ($type == 'image') {
            if ($this->request->param('_ext') == 'json') {
                $primaryKey = $entity->primaryKey;
                $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                if (isset($attributes['keyField'])) {
                    $key = TableRegistry::get($source)->primaryKey();
                    if (!is_array($key)) {
                        $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                    }
                }
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
            } else {
                switch ($this->request->param('action')) {
                    case 'view':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            $file = stream_get_contents($entity->{$fileContentField});
                            rewind($entity->{$fileContentField});
                            $entity->{$fileNameField} = 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file);
                            return $entity->{$fileNameField};
                        }
                        break;
                    case 'index':
                        $primaryKey = $entity->primaryKey;
                        $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                        if (isset($attributes['keyField'])) {
                            $key = TableRegistry::get($source)->primaryKey();
                            if (!is_array($key)) {
                                $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                            }
                        }
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
                    case 'edit':
                    case 'delete':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            if (is_resource($entity->{$fileContentField})) {
                                $file = stream_get_contents($entity->{$fileContentField});
                            } else {
                                $file = $entity->{$fileContentField};
                            }

                            $returnValue = [
                                'extension' => $pathInfo['extension'],
                                'filename' => $fileName,
                                'src' => 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file)
                            ];

                            rewind($entity->{$fileContentField});
                            return $returnValue;
                        }
                        break;
                }
            }
        } else {
            switch ($this->request->param('action')) {
                case 'view':
                    $primaryKey = $entity->primaryKey;
                    $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                    if (isset($attributes['keyField'])) {
                        $key = TableRegistry::get($source)->primaryKey();
                        if (!is_array($key)) {
                            $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                        }
                    }
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
                    break;
            }
        }
    }
}
