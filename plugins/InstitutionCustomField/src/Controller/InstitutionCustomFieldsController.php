<?php
namespace InstitutionCustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class InstitutionCustomFieldsController extends AppController
{
    public function initialize()
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $tabElements = [
            'Fields' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Fields'],
                'text' => __('Fields')
            ],
            'Pages' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Pages'],
                'text' => __('Pages')
            ]
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Custom Field (Institution)');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Custom Field (Institution)', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
