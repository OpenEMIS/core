<?php
namespace InstitutionCustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class InstitutionCustomFieldsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function Fields()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'InstitutionCustomField.InstitutionCustomFields']);
    }

    public function Pages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'InstitutionCustomField.InstitutionCustomForms']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $tabElements = [
            'Fields' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Fields'],
                'text' => __('Fields')
            ],
            'Pages' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Pages'],
                'text' => __('Pages')
            ]
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->getParam('action;'));
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Custom Field (Institution)');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Custom Field (Institution)', ['plugin' => $this->plugin, 'controller' => $this->getName(), 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
