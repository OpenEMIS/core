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

class InfrastructureUtilityElectricitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_utility_electricities');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityElectricityTypes',   ['className' => 'Institution.UtilityElectricityTypes', 'foreign_key' => 'utility_electricity_type_id']);
        $this->belongsTo('UtilityElectricityConditions',   ['className' => 'Institution.UtilityElectricityConditions', 'foreign_key' => 'utility_electricity_condition_id']);

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['comment', 'academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityElectricities';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('utility_electricity_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('utility_electricity_condition_id', ['attr' => ['label' => __('Condition')]]);
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
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'utility_electricity_type_id':
                return __('Type');
            case 'utility_electricity_condition_id':
                return __('Condition');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['utility_electricity_type_id']['type'] = 'select';
        $this->field('utility_electricity_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['utility_electricity_condition_id']['type'] = 'select';
        $this->field('utility_electricity_condition_id', ['attr' => ['label' => __('Condition')]]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
    
        $extraField[] = [
            'key'   => 'utility_electricity_type_id',
            'field' => 'utility_electricity_type_id',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'utility_electricity_condition_id',
            'field' => 'utility_electricity_condition_id',
            'type'  => 'string',
            'label' => __('Condition')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $academicPeriod = $this->request->query['academic_period_id'];

        if (empty($academicPeriod)) {
            $academicPeriod = $this->AcademicPeriods->getCurrent();
        }
        $query->where([$this->aliasField('academic_period_id') => $academicPeriod])
        ->orderDesc($this->aliasField('created'));
    }
}
