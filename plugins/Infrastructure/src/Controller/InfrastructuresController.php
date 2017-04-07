<?php
namespace Infrastructure\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->models = [
            'Fields' => ['className' => 'Infrastructure.InfrastructureCustomFields', 'options' => ['deleteStrategy' => 'restrict']],
            'LandPages' => ['className' => 'Infrastructure.LandCustomForms', 'options' => ['deleteStrategy' => 'restrict']],
            'BuildingPages' => ['className' => 'Infrastructure.BuildingCustomForms', 'options' => ['deleteStrategy' => 'restrict']],
            'FloorPages' => ['className' => 'Infrastructure.FloorCustomForms', 'options' => ['deleteStrategy' => 'restrict']],
            'RoomPages' => ['className' => 'Infrastructure.RoomCustomForms', 'options' => ['deleteStrategy' => 'restrict']]
        ];
        $this->loadComponent('Paginator');
        $this->loadComponent('FieldOption.FieldOption');
    }

    // CAv4
    public function Types()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.InfrastructureTypes']);
    }
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $tabElements = [
            'Fields' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Fields'],
                'text' => __('Fields')
            ],
            'Pages' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'LandPages'],
                'text' => __('Pages')
            ],
            'Types' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'LandTypes'],
                'text' => __('Types')
            ]
        ];

        // Types & RoomTypes share one tab, Pages & RoomPages share one tab
        switch ($this->request->action) {
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
                $selectedAction = $this->request->action;
        }

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $selectedAction);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Infrastructure');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Infrastructure', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
