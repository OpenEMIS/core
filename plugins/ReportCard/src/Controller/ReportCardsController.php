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

    // Added
    public function ReportCardEmail() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'ReportCard.ReportCardEmail']); }
    // End

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Report Cards');
        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Report Cards', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->set('contentHeader', $header);
    }

    // Added
    public function getReportCardTab($id)
    {
        $encodedParam = $this->request->params['pass'][1];

        // $queryClassId = $this->request->query['class_id'];
        // $queryAcademicPeriodId = $this->request->query['academic_period_id'];
        // $queryReportCardId = $this->request->query['report_card_id'];

        // $queryString = '?class_id='.$queryClassId.'&academic_period_id='.$queryAcademicPeriodId.'&report_card_id='.$queryReportCardId;

        // $tabElements = [
        //     'ReportCardStatuses' => [
        //         'text' => __('Overview'),
        //         'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ReportCardStatuses', 'view', $encodedParam, $queryString]
        //     ],
        //     'ReportCardStatusesEmail' => [
        //         'text' => __('Email'),
        //         'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ReportCardStatusesEmail', 'view', $encodedParam, $queryString]
        //     ]
        // ];

        $tabElements = [
            'ReportCards' => [
                'text' => __('Overview'),
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Templates', 'view', $encodedParam]
            ],
            'ReportCardEmail' => [
                'text' => __('Email'),
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ReportCardEmail', 'view', $encodedParam]
                // 'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'ReportCardEmail', 'edit', $encodedParam]
            ]
        ];

        return $tabElements;
    }
}
