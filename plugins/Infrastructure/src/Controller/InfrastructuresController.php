<?php
namespace Infrastructure\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;

class InfrastructuresController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
        $this->loadComponent('FieldOption.FieldOption');
    }

    public function Fields()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.LandCustomFields']);
    }

    public function LandPages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.LandCustomForms']);
    }

    public function BuildingPages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.BuildingCustomForms']);
    }

    public function FloorPages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.FloorCustomForms']);
    }

    public function RoomPages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.RoomCustomForms']);
    }

    // CAv4
    public function LandTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.LandTypes']);
    }
    public function BuildingTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.BuildingTypes']);
    }
    public function FloorTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.FloorTypes']);
    }
    public function RoomTypes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.RoomTypes']);
    }
    // End

    public function beforeFilter(EventInterface $event)
    {
        if ($this->getPlugin() == 'Infrastructure') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);

        $tabElements = [
            'Fields' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Fields'],
                'text' => __('Fields')
            ],
            'Pages' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'LandPages'],
                'text' => __('Pages')
            ],
            'Types' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'LandTypes'],
                'text' => __('Types')
            ]
        ];

        // Types & RoomTypes share one tab, Pages & RoomPages share one tab
        switch ($this->request->getParam('action')) {
            case 'LandTypes':
            case 'BuildingTypes':
            case 'FloorTypes':
            case 'RoomTypes':
                $selectedAction = 'Types';
                break;
            case 'LandPages':
            case 'BuildingPages':
            case 'FloorPages':
            case 'RoomPages':
                $selectedAction = 'Pages';
                break;
            default:
                $selectedAction = $this->request->getParam('action');
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $selectedAction);
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $header = __('Infrastructure');
        $header .= ' - ' . __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
        $this->Navigation->addCrumb('Infrastructure', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->getAlias()]);
        $this->Navigation->addCrumb(__(Inflector::humanize(Inflector::underscore($this->request->getParam('action')))));

        $this->set('contentHeader', $header);
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
