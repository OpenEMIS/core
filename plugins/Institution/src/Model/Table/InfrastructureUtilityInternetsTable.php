<?php
namespace Institution\Model\Table;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;

use App\Model\Table\ControllerActionTable;
use App\Model\Table\AppTable;

class InfrastructureUtilityInternetsTable extends ControllerActionTable
{
    private $internetPurpose = [
        1 => 'Teaching',
        2 => 'Non-Teaching'
    ];

    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_internets');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityInternetTypes',   ['className' => 'Institution.UtilityInternetTypes', 'foreign_key' => 'utility_internet_type_id']);
        $this->belongsTo('UtilityInternetConditions',   ['className' => 'Institution.UtilityInternetConditions', 'foreign_key' => 'utility_internet_condition_id']);
        $this->belongsTo('UtilityInternetBandwidths',   ['className' => 'Institution.UtilityInternetBandwidths', 'foreign_key' => 'utility_internet_bandwidth_id']);

        $this->toggle('search', false);
        $this->addBehavior('Excel',[
            'excludes' => ['comment', 'academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureUtilityInternets'=>['id']]
        ]);
    }

    public function getPurposeOptions()
    {
        return $this->internetPurpose;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('internet_purpose')
            ->requirePresence('utility_internet_condition_id')
        ;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityInternets';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
        //POCOR-9475 
        $this->field('start_date',['visible' => false]);
        $this->field('end_date',['visible' => false]);
        $this->field('is_current',['visible' => false]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('utility_internet_type_id');
        $this->field('utility_internet_condition_id');
        $this->field('internet_purpose');
        $this->field('utility_internet_bandwidth_id');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment',['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->getQuery();

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
        
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Infrastructure Utility Internet','Details');       
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
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'comment':
                return __('Comment');
            case 'academic_period_id':
                return __('Academic Period');
            case 'utility_internet_type_id':
                return __('Type');
            case 'utility_internet_condition_id':
                return __('Condition');
            case 'internet_purpose':
                return __('Purpose');
            case 'utility_internet_bandwidth_id':
                return __('Bandwidth');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified On');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created On');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['CreatedUser', 'ModifiedUser']);
    }

    public function onGetModifiedUserId(EventInterface $event, Entity $entity)
    {
        if (!empty($entity->modified_user_id)) {
            if ($entity->has('modified_user') && $entity->modified_user) {
                return $entity->modified_user->name;
            }

            $user = TableRegistry::getTableLocator()->get('User.Users')->find()
                ->where(['id' => $entity->modified_user_id])
                ->first();
            if ($user) {
                return $user->name;
            }
        }

        $audit = $this->getVersioningModifiedAudit($entity);

        return $audit['modified_user_name'] ?? '';
    }

    public function onGetModified(EventInterface $event, Entity $entity)
    {
        if (!empty($entity->modified)) {
            return $this->formatDateTime($entity->modified);
        }

        $audit = $this->getVersioningModifiedAudit($entity);
        if (!empty($audit['modified'])) {
            return $this->formatDateTime($audit['modified']);
        }

        return '';
    }

    /**
     * Edit-as-insert (POCOR-9475) leaves modified_* null; use current row created audit when a prior version exists.
     */
    protected function getVersioningModifiedAudit(Entity $entity): array
    {
        if (!$this->hasPreviousUtilityInternetVersion($entity)) {
            return ['modified_user_name' => '', 'modified' => null];
        }

        $modifiedUserName = '';
        if (!empty($entity->created_user_id)) {
            if ($entity->has('created_user') && $entity->created_user) {
                $modifiedUserName = $entity->created_user->name;
            } else {
                $user = TableRegistry::getTableLocator()->get('User.Users')->find()
                    ->where(['id' => $entity->created_user_id])
                    ->first();
                if ($user) {
                    $modifiedUserName = $user->name;
                }
            }
        }

        return [
            'modified_user_name' => $modifiedUserName,
            'modified' => $entity->created ?? null,
        ];
    }

    protected function hasPreviousUtilityInternetVersion(Entity $entity): bool
    {
        if (empty($entity->institution_id) || empty($entity->academic_period_id)) {
            return false;
        }

        $conditions = [
            $this->aliasField('institution_id') => $entity->institution_id,
            $this->aliasField('academic_period_id') => $entity->academic_period_id,
            $this->aliasField('is_current') => 0,
        ];
        $conditions = $this->appendInternetVersionFieldConditions($conditions, $entity, true);
        if (!empty($entity->id)) {
            $conditions[$this->aliasField('id !=')] = $entity->id;
        }

        return $this->exists($conditions);
    }

    /**
     * Fields that identify a distinct current internet utility row (POCOR-9475).
     */
    protected function getInternetVersionGroupFields(): array
    {
        return [
            'utility_internet_type_id',
            'internet_purpose',
            'utility_internet_bandwidth_id',
        ];
    }

    protected function getInternetVersionConditions(Entity $entity): array
    {
        $conditions = [
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
        ];

        return $this->appendInternetVersionFieldConditions($conditions, $entity, false);
    }

    /**
     * @param array $conditions
     * @param \Cake\ORM\Entity $entity
     * @param bool $useAlias Use table alias for find()/exists() queries
     */
    protected function appendInternetVersionFieldConditions(array $conditions, Entity $entity, bool $useAlias = true): array
    {
        foreach ($this->getInternetVersionGroupFields() as $field) {
            if (!$entity->has($field)) {
                continue;
            }
            $value = $entity->get($field);
            $key = $useAlias ? $this->aliasField($field) : $field;
            if ($value === null || $value === '') {
                $conditions[$key . ' IS'] = null;
            } else {
                $conditions[$key] = $value;
            }
        }

        return $conditions;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        if ($institutionId) {
            $this->repairMultipleInternetCurrentFlags($institutionId, $extra['selectedAcademicPeriodId']);
        }

        $conditions = [
            $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'],
            $this->aliasField('is_current') => 1,
        ];
        if ($institutionId) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }

        $query->where($conditions)->orderDesc($this->aliasField('created'));
    }

    /**
     * POCOR-9475: one current row per institution, period, type, purpose, and bandwidth.
     * Repairs rows left inactive when add/edit expired all combinations instead of only the same one.
     */
    protected function repairMultipleInternetCurrentFlags($institutionId, $academicPeriodId): void
    {
        $baseConditions = [
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
        ];

        $groupFields = array_map(function ($field) {
            return $this->aliasField($field);
        }, $this->getInternetVersionGroupFields());

        $comboRows = $this->find()
            ->select($groupFields)
            ->where($baseConditions)
            ->group($groupFields)
            ->all();

        if ($comboRows->count() <= 1) {
            return;
        }

        $currentCount = $this->find()
            ->where($baseConditions + [$this->aliasField('is_current') => 1])
            ->count();

        if ($currentCount >= $comboRows->count()) {
            return;
        }

        foreach ($comboRows as $comboRow) {
            $comboConditions = $this->appendInternetVersionFieldConditions($baseConditions, $comboRow, true);

            if ($this->exists($comboConditions + [$this->aliasField('is_current') => 1])) {
                continue;
            }

            $latest = $this->find()
                ->where($comboConditions)
                ->orderDesc($this->aliasField('id'))
                ->first();

            if ($latest) {
                $this->updateAll(['is_current' => 1], [$this->aliasField('id') => $latest->id]);
            }
        }
    }
    public function onGetInternetPurpose(EventInterface $event, Entity $entity)
    {
        return $this->internetPurpose[$entity->internet_purpose];
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $internetPurposeOptions = $this->getPurposeOptions();

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['utility_internet_type_id']['type'] = 'select';
        $this->field('utility_internet_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['utility_internet_condition_id']['type'] = 'select';
        $this->field('utility_internet_condition_id', ['attr' => ['label' => __('Condition')]]);

        $this->fields['internet_purpose']['type'] = 'select';
        $this->fields['internet_purpose']['options'] = $internetPurposeOptions;
        $this->field('internet_purpose', ['attr' => ['label' => __('Purpose')]]);

        $this->fields['utility_internet_bandwidth_id']['type'] = 'select';
        $this->field('utility_internet_bandwidth_id', ['attr' => ['label' => __('Bandwidth')]]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
     
        $extraField[] = [
            'key'   => 'utility_internet_type_id',
            'field' => 'utility_internet_type_id',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'utility_internet_condition_id',
            'field' => 'utility_internet_condition_id',
            'type'  => 'string',
            'label' => __('Condition')
        ];

        $extraField[] = [
            'key'   => 'internet_purpose_new',
            'field' => 'internet_purpose_new',
            'type'  => 'string',
            'label' => __('Purpose')
        ];

        $extraField[] = [
            'key'   => 'utility_internet_bandwidth_id',
            'field' => 'utility_internet_bandwidth_id',
            'type'  => 'string',
            'label' => __('Bandwidth')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
		//$institutionId  = $this->Session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();
        $academicPeriod = $this->request->getQuery('academic_period_id');

        if (empty($academicPeriod)) {
            $academicPeriod = $this->AcademicPeriods->getCurrent();
        }
		$query
         ->select([
            'internet_purpose_new' => "(CASE WHEN internet_purpose = 1 THEN 'Teaching'
            ELSE 'Non-Teaching' END)"
        ])
        ->where([$this->aliasField('academic_period_id') => $academicPeriod])
        ->orderDesc($this->aliasField('created'));
    }

    //POCOR-9475
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        // Expire prior current row for same institution, period, type, purpose, and bandwidth only
        $this->updateAll(
            ['is_current' => false],
            $this->getInternetVersionConditions($entity)
        );

        //Set dates from academic period
        $academicPeriods = TableRegistry::getTableLocator()
            ->get('AcademicPeriod.AcademicPeriods');

        $period = $academicPeriods->find()
            ->select(['start_date', 'end_date'])
            ->where(['id' => $entity->academic_period_id])
            ->first();

        if ($period) {
            $entity->start_date = $period->start_date;
            $entity->end_date   = $period->end_date;
        }

        //Always make new record current
        $entity->is_current = true;
    }

    //POCOR-9475
    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            return;
        }

        //Store original ID BEFORE unsetting
        $originalId = $entity->id;

        // Expire previous current record for same institution, period, type, purpose, and bandwidth
        $this->updateAll(
            ['is_current' => false],
            $this->getInternetVersionConditions($entity)
        );

        //Convert EDIT into INSERT
        $entity->setNew(true);
        $entity->unset('id');

        $userId = null;
        if (isset($_SESSION['Auth']) && isset($_SESSION['Auth']['User']['id'])) {
            $userId = $_SESSION['Auth']['User']['id'];
        }
        $entity->modified_user_id = $userId ?? 1;
        $entity->modified = FrozenTime::now();

        //Set academic period dates
        $academicPeriods = TableRegistry::getTableLocator()
            ->get('AcademicPeriod.AcademicPeriods');

        $period = $academicPeriods->find()
            ->select(['start_date', 'end_date'])
            ->where(['id' => $entity->academic_period_id])
            ->first();

        if ($period) {
            $entity->start_date = $period->start_date;
            $entity->end_date   = $period->end_date;
        }

        //Always mark new record current
        $entity->is_current = true;
    }

    //POCOR-9475
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // Soft delete: mark record inactive
        $this->updateAll(
            ['is_current' => 0],
            ['id' => $entity->id]
        );

        // Stop actual DELETE
        $event->stopPropagation();
        $event->setResult(false);

        $this->Alert->success(
            __('Record has been deactivated successfully.'),
            ['type' => 'string', 'reset' => true]
        );

        return false;
    }
    
    //POCOR-9475
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $queryString = $this->getQueryString();
            $institutionId  = $queryString['institution_id'];
            $recordId  = $entity->id;
            $queryString = $this->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId, 'record_id' => $recordId]);
            $icon = '<i class="fa fa-history"></i>';
            $buttons['history'] = $buttons['view'];
            $buttons['history']['label'] = $icon . __('History');
            $buttons['history']['url']['plugin'] = 'Institution';
            $buttons['history']['url']['controller'] = 'Institutions';
            $buttons['history']['url']['action'] = 'InfrastructureInternetHistory';
            $buttons['history']['url'][0] = 'index';
            $buttons['history']['url'][1] = $queryString;
        }
            
        return $buttons;
    }

    //POCOR-9475
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

        return $this->controller->redirect($this->url('index'));
        
    }

}
