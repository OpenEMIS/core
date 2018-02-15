<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\i18n\Date;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;

class RisksTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);

        $this->hasMany('RiskCriterias', ['className' => 'Risk.RiskCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRisks', ['className' => 'Institution.InstitutionRisks', 'dependent' => true, 'cascadeCallbacks' => true]);

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
        $this->field('name');
        $this->field('number_of_risk_index',['sort' => false]);
        $this->field('academic_period_id',['visible' => false]);
        $this->field('generated_by',['after' => 'number_of_risk_index']);
        $this->field('generated_on',['after' => 'generated_by']);
        $this->field('status',['after' => 'generated_on']);
        $this->field('pid',['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
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
        $Risks = TableRegistry::get('Risk.Risks');
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);

        $institutionId = $params['institution_id'];
        $userId = $params['user_id'];
        $riskId = $params['risk_id'];
        $academicPeriodId = $params['academic_period_id'];

        // update indexes pid and status
        $pid = getmypid();
        $record = $this->getInstitutionIndexesRecords($riskId, $institutionId)->first();

        // if processing id not null (process still running or process stuck)
        if (!empty($record->pid)) {
            exec("kill -9 " . $record->pid);
        }

        if (!empty($record)) {
            // update the status to processing
            $this->InstitutionRisks->updateAll([
                'pid' => $pid,
                'status' => 2 // processing
            ],
            ['id' => $record->id]);
        } else {
            $entity = $this->InstitutionRisks->newEntity([
                'status' => 2, // processing
                'pid' => $pid,
                'generated_on' => NULL,
                'generated_by' => NULL,
                'risk_id' => $riskId,
                'institution_id' => $institutionId,
            ]);
            $this->InstitutionRisks->save($entity);
        }

        // trigger shell
        $Risks->triggerUpdateIndexesShell('UpdateIndexes', $institutionId, $userId, $riskId, $academicPeriodId);

        // redirect to index page
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Risks',
            'index',
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by', ['visible' => false]);
        $this->field('status', ['visible' => false]);
        $this->field('pid', ['visible' => false]);
    }

    public function onGetNumberOfRiskIndex(Event $event, Entity $entity)
    {
        $riskId = $entity->id;
        $riskTotal = $this->RiskCriterias->getTotalRisk($riskId);

        return $riskTotal;
    }

    public function getInstitutionIndexesRecords($riskId, $institutionId)
    {
        return $this->InstitutionRisks->find('Record', ['risk_id' => $riskId, 'institution_id' => $institutionId]);
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $riskId = $entity->id;
        $institutionId = $this->request->session()->read('Institution.Institutions.id');

        $record = $this->getInstitutionIndexesRecords($riskId, $institutionId)->first();

        $userName = '';
        if (isset($record->generated_by)) {
            $generatedById = $record->generated_by;

            $Users = TableRegistry::get('Security.Users');
            $userName = $Users->get($generatedById)->first_name . ' ' . $Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetGeneratedOn(Event $event, Entity $entity)
    {
        $riskId = $entity->id;
        $institutionId = $this->request->session()->read('Institution.Institutions.id');

        $record = $this->getInstitutionIndexesRecords($riskId, $institutionId)->first();

        $generatedOn = '';
        if (isset($record->generated_on)) {
            $generatedOn = $record->generated_on->format('F d, Y - H:i:s');
        }

        return $generatedOn;
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $Risks = TableRegistry::get('Risk.Risks');
        $riskId = $entity->id;
        $institutionId = $this->request->session()->read('Institution.Institutions.id');

        $record = $this->getInstitutionIndexesRecords($riskId, $institutionId)->first();

        $statusId = isset($record['status']) ? $record['status']: 1; // 1 = not generated
        return $Risks->getIndexesStatus($statusId);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $userId = $session->read('Auth.User.id');
        $riskId = $entity->id;

        if (array_key_exists('view', $buttons)) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'InstitutionStudentRisks'
            ];

            $buttons['view']['url'] = $this->setQueryString($url, [
                'risk_id' => $entity->id,
                'academic_period_id' => $entity->academic_period_id
            ]);

            // generate button
            if ($this->AccessControl->check(['Institutions', 'Risks', 'generate'])) {
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Risks',
                    'generate'
                ];

                $buttons['generate'] = $buttons['view'];
                $buttons['generate']['label'] = '<i class="fa fa-refresh"></i>' . __('Generate');
                $buttons['generate']['url'] = $this->setQueryString($url, [
                    'institution_id' => $institutionId,
                    'user_id' => $userId,
                    'risk_id' => $riskId,
                    'academic_period_id' => $entity->academic_period_id,
                    'action' => 'index'
                ]);
            }
            // end generate button
        }

        return $buttons;
    }
}
