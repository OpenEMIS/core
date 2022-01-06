<?php
namespace ReportCard\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ReportCardsController extends AppController
{
    public function initialize() {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Templates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ReportCard.ReportCards']); }

    public function ReportCardEmail() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ReportCard.ReportCardEmail']); }

    public function Processes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ReportCard.ReportCardProcesses']); }
    // End

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Report Cards');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Report Cards', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->set('contentHeader', $header);
    }

    public function getReportCardTab($id)
    {
        $encodedParam = $this->request->params['pass'][1];

        $tabElements = [
            'ReportCards' => [
                'text' => __('Overview'),
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates', 'view', $encodedParam]
            ],
            'ReportCardEmail' => [
                'text' => __('Email'),
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ReportCardEmail', 'view', $encodedParam]
            ]
        ];

        return $tabElements;
    }

    public function getReportTabElements($options = [])
    {
        $tabElements = [];
        $sessionUrl = ['plugin' => 'ReportCard', 'controller' => 'ReportCards'];
        $sessionTabElements = [
            'Templates' => ['text' => __('Overview')],
            'Processes' => ['text' => __('Processes')]
        ];

        $tabElements = array_merge($tabElements, $sessionTabElements);

        foreach ($sessionTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($sessionUrl, ['action' => $key, 'index']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
