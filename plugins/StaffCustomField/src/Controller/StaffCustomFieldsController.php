<?php
namespace StaffCustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class StaffCustomFieldsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function Fields()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffCustomField.StaffCustomFields']);
    }

    public function Pages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffCustomField.StaffCustomForms']);
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
        $header = __('Custom Field (Staff)');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Custom Field (Staff)', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
