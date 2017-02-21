<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;

class InstitutionIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('indexes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);

        $this->hasMany('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('search', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name',['sort' => false]);
        $this->field('number_of_risk_index',['sort' => false]);
        $this->field('academic_period_id',['visible' => false]);
        $this->field('generated_on',['sort' => false, 'after' => 'generated_by']);
        $this->field('status',['after' => 'generated_on']);
        $this->field('pid',['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Indexes/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']]);
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $Indexes = TableRegistry::get('Indexes.Indexes');
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);

        $institutionId = $params['institution_id'];
        $userId = $params['user_id'];
        $indexId = $params['index_id'];
        $academicPeriodId = $params['academic_period_id'];

        // update indexes pid and status
        $pid = getmypid();
        $runningPid = $this->find()->where(['id' => $indexId])->first()->pid;

        // if processing id not empty (process still running or process stuck)
        if (!empty($runningPid)) {
            exec("kill -9 " . $runningPid);
        }

        $this->updateAll([
            'pid' => $pid,
            'status' => 2 // processing
        ],
        ['id' => $indexId]);

        // trigger shell
        $Indexes->triggerUpdateIndexesShell('UpdateIndexes', $institutionId, $userId, $indexId, $academicPeriodId);

        // redirect to index page
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionIndexes',
            'index'
        ];

        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by',['visible' => false]);
        $this->field('status',['visible' => false]);
        $this->field('pid',['visible' => false]);
    }

    public function onGetNumberOfRiskIndex(Event $event, Entity $entity)
    {
        $indexId = $entity->id;
        $indexTotal = $this->IndexesCriterias->getTotalIndex($indexId);

        return $indexTotal;
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $userName = '';
        if (isset($entity->generated_by)) {
            $generatedById = $entity->generated_by;

            $Users = TableRegistry::get('Security.Users');
            $userName = $Users->get($generatedById)->first_name . ' ' . $Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $Indexes = TableRegistry::get('Indexes.Indexes');
        return $Indexes->getIndexesStatus($entity->status);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $userId = $session->read('Auth.User.id');
        $indexId = $entity->id;

        if (array_key_exists('view', $buttons)) {
            $buttons['view']['url'] = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'InstitutionStudentIndexes',
                'index_id' => $entity->id,
                'academic_period_id' => $entity->academic_period_id
            ];

            // generate button
            if ($this->AccessControl->check(['Institutions', 'InstitutionIndexes', 'generate'])) {
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'InstitutionIndexes',
                    'generate'
                ];

                $buttons['generate'] = $buttons['view'];
                $buttons['generate']['label'] = '<i class="fa fa-refresh"></i>' . __('Generate');
                $buttons['generate']['url'] = $this->setQueryString($url, [
                    'institution_id' => $institutionId,
                    'user_id' => $userId,
                    'index_id' => $indexId,
                    'academic_period_id' => $entity->academic_period_id,
                    'action' => 'index'
                ]);
            }
            // end generate button
        }

        return $buttons;
    }
}
