<?php
namespace Institution\Model\Table;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Table\AppTable;

class InfrastructureUtilityInternetsTable extends ControllerActionTable
{
    private $internetPurpose = [
        1 => 'Teaching',
        2 => 'Non-Teaching'
    ];

    public function initialize(array $config)
    {
        $this->table('infrastructure_utility_internets');
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
    }

    public function getPurposeOptions()
    {
        return $this->internetPurpose;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('internet_purpose')
            ->requirePresence('utility_internet_condition_id')
        ;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityInternets';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('utility_internet_type_id');
        $this->field('utility_internet_condition_id');
        $this->field('internet_purpose');
        $this->field('utility_internet_bandwidth_id');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment',['visible' => false]);

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
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'utility_internet_type_id':
                return __('Type');
            case 'utility_internet_condition_id':
                return __('Condition');
            case 'internet_purpose':
                return __('Purpose');
                case 'utility_internet_bandwidth_id':
                    return __('Bandwidth');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
        /* ->select([
            'status' => "(SELECT CASE WHEN internet_purpose = 1 THEN 'Teaching'
            ELSE 'Non-Teaching' END AS internet_purpose
            FROM ".$this->table()." where  academic_period_id = ".$extra['selectedAcademicPeriodId'].")"
        ]) */
        ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }
    public function onGetInternetPurpose(Event $event, Entity $entity)
    {
        return $this->internetPurpose[$entity->internet_purpose];
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
		$institutionId  = $this->Session->read('Institution.Institutions.id');
        $academicPeriod = $this->request->query['academic_period_id'];

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
}
