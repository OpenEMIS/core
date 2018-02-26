<?php
namespace StaffAppraisal\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use App\Controller\AppController;

class StaffAppraisalsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Appraisals';
        $this->Navigation->addCrumb($header, ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'Criterias']);
        $this->Navigation->addCrumb(Inflector::humanize($this->request->action));
        $this->getAppraisalsTabElements();
        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Appraisals');
        $header .= ' - ' . __($model->getHeader($model->alias));
        $this->set('contentHeader', $header);
    }

    private function getAppraisalsTabElements() : void
    {
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [
            'criterias' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'criterias'],
                'text' => __('Criterias')
            ],
            'forms' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'forms'],
                'text' => __('Forms')
            ],
            'types' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'types'],
                'text' => __('Types')
            ],
            'periods' => [
                'url' => ['plugin' => 'StaffAppraisal', 'controller' => 'StaffAppraisals', 'action' => 'periods'],
                'text' => __('Periods')
            ]
        ];

        $this->set('tabElements', $this->TabPermission->checkTabPermission($tabElements));
        $this->set('selectedAction', $this->request->param('action'));
    }

    public function criterias()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalCriterias']);
    }

    public function forms()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalForms']);
    }

    public function types()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalTypes']);
    }

    public function periods()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'StaffAppraisal.AppraisalPeriods']);
    }
}
