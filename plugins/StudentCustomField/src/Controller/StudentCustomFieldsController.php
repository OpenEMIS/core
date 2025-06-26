<?php
namespace StudentCustomField\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Event\EventInterface;

class StudentCustomFieldsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function Fields()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StudentCustomField.StudentCustomFields']);
    }

    public function Pages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StudentCustomField.StudentCustomForms']);
    }
    //POCOR-8434 starts
    public function Filters()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StudentCustomField.StudentCustomFilters']);
    }//POCOR-8434 ends
    
    public function beforeFilter(EventInterface $event)
    {
        if ($this->getPlugin() == 'StudentCustomField') {
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
            ],//POCOR-8434 starts
            'Filters' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Filters'],
                'text' => __('Filters')
            ]//POCOR-8434 ends
        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->getParam('action'));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Custom Field (Student)');

        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Custom Field (Student)', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->getAlias()]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

        $this->set('contentHeader', $header);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
