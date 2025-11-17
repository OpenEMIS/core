<?php
namespace StaffCustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class StaffCustomFieldsController extends AppController
{
    public function initialize(): void
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

    public function beforeFilter(EventInterface $event)
    {
        if ($this->getPlugin() == 'StaffCustomField') {
            $this->Security->setConfig('validatePost', false);
        }
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
        $this->set('selectedAction', $this->request->getParam('action'));
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Custom Field (Staff)');

        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Custom Field (Staff)', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

        $this->set('contentHeader', $header);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
