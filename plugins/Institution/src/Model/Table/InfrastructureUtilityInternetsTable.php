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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
        /* ->select([
            'status' => "(SELECT CASE WHEN internet_purpose = 1 THEN 'Teaching'
            ELSE 'Non-Teaching' END AS internet_purpose
            FROM ".$this->table()." where  academic_period_id = ".$extra['selectedAcademicPeriodId'].")"
        ]) */
        ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'], $this->aliasField('is_current') => 1])
        ->orderDesc($this->aliasField('created'));
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
        //Expire old records for same institution + academic year
        $this->updateAll(
             ['is_current' => false],
            [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]
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

        //Expire previous current record for that institution + academic year
        $this->updateAll(
            ['is_current' => false],
            [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]
        );

        //Convert EDIT into INSERT
        $entity->setNew(true);
        $entity->unset('id');
        
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
