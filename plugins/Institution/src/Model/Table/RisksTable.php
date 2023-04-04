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
        $this->addBehavior('Excel', ['pages' => ['index']]);
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

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Risks','Students');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];
    
            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
		// End POCOR-5188
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
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    { 
    
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
    
        $User = TableRegistry::get('security_users');
		$query
		->select(['name' => 'Risks.name', 
        'generated_by' => $User->find()->func()->concat([
            'first_name' => 'literal',
            " ",
            'last_name' => 'literal'
        ]), 
        'generated_on' => 'InstitutionRisks.generated_on',
        'risk_index' => "(SELECT SUM(risk_value) FROM ".$this->RiskCriterias->table()." WHERE risk_id = Risks.id)",
        'status' => "(SELECT CASE WHEN status = 1 THEN 'Not Generated'
        WHEN status = 2 THEN 'Processing' 
        WHEN status = 3 THEN 'Generated'  
        ELSE 'Not Generated' END AS status 
        FROM ".$this->InstitutionRisks->table()." where risk_id = Risks.id AND institution_id = ".$institutionId.")"
        ])
		->LeftJoin([$this->RiskCriterias->alias() => $this->RiskCriterias->table()],[
			$this->RiskCriterias->aliasField('risk_id').' = ' . 'Risks.id'
        ])
        ->LeftJoin([$this->InstitutionRisks->alias() => $this->InstitutionRisks->table()],[
			$this->InstitutionRisks->aliasField('risk_id').' = ' . 'Risks.id'
        ])
        ->LeftJoin([$User->alias() => $User->table()],[
			$User->aliasField('id').' = ' . $this->InstitutionRisks->aliasField('generated_by')
        ])
        ->where(['Risks.academic_period_id' =>  $academicPeriod,
            'InstitutionRisks.institution_id' =>  $institutionId
        ])
        ->group([
            $this->RiskCriterias->aliasField('risk_id')
            
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
     
        $extraField[] = [
            'key' =>  'Risks.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];
        
        $extraField[] = [
            'key' =>  "",
            'field' => 'risk_index',
            'type' => 'integer',
            'label' => __('Number Of Risk Index')
        ];

        $extraField[] = [
            'key' =>  'Users.generated_by',
            'field' => 'generated_by',
            'type' => 'string',
            'label' => __('Generated By')
        ];

        $extraField[] = [
            'key' => 'InstitutionRisks.generated_on',
            'field' => 'generated_on',
            'type' => 'date',
            'label' => __('Generated On')
        ];

        $extraField[] = [
            'key' =>  "",
            'field' => 'status',
            'type' => 'string',
            'label' => __('Status')
        ];
        $fields->exchangeArray($extraField);
    }
}
