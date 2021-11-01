<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class InfrastructureWashWatersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waters');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashWaterTypes',   ['className' => 'Institution.InfrastructureWashWaterTypes', 'foreign_key' => 'infrastructure_wash_water_type_id']);
        $this->belongsTo('InfrastructureWashWaterFunctionalities',   ['className' => 'Institution.InfrastructureWashWaterFunctionalities', 'foreign_key' => 'infrastructure_wash_water_functionality_id']);
        $this->belongsTo('InfrastructureWashWaterProximities',   ['className' => 'Institution.InfrastructureWashWaterProximities', 'foreign_key' => 'infrastructure_wash_water_proximity_id']);
        $this->belongsTo('InfrastructureWashWaterQuantities',   ['className' => 'Institution.InfrastructureWashWaterQuantities', 'foreign_key' => 'infrastructure_wash_water_quantity_id']);
        $this->belongsTo('InfrastructureWashWaterQualities',   ['className' => 'Institution.InfrastructureWashWaterQualities', 'foreign_key' => 'infrastructure_wash_water_quality_id']);
        $this->belongsTo('InfrastructureWashWaterAccessibilities',   ['className' => 'Institution.InfrastructureWashWaterAccessibilities', 'foreign_key' => 'infrastructure_wash_water_accessibility_id']);

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashWaters';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_water_type_id');
        $this->field('infrastructure_wash_water_functionality_id');
        $this->field('infrastructure_wash_water_proximity_id');
        $this->field('infrastructure_wash_water_quantity_id');
        $this->field('infrastructure_wash_water_quality_id');
        $this->field('infrastructure_wash_water_accessibility_id');
        $this->field('academic_period_id', ['visible' => false]);


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
            case 'infrastructure_wash_water_type_id':
                return __('Type');
            case 'infrastructure_wash_water_functionality_id':
                return __('Functionality');
            case 'infrastructure_wash_water_proximity_id':
                return __('Proximity');
            case 'infrastructure_wash_water_quantity_id':
                return __('Quantity');
            case 'infrastructure_wash_water_quality_id':
                return __('Quality');
            case 'infrastructure_wash_water_accessibility_id':
                return __('Accessibility');
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

        $this->fields['infrastructure_wash_water_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_water_functionality_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_functionality_id', ['attr' => ['label' => __('Functionality')]]);

        $this->fields['infrastructure_wash_water_proximity_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_proximity_id', ['attr' => ['label' => __('Proximity')]]);

        $this->fields['infrastructure_wash_water_quantity_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_quantity_id', ['attr' => ['label' => __('Quantity')]]);

        $this->fields['infrastructure_wash_water_quality_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_quality_id', ['attr' => ['label' => __('Quality')]]);

        $this->fields['infrastructure_wash_water_accessibility_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_accessibility_id', ['attr' => ['label' => __('Accessibility')]]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id');
        $requestQuery = $this->request->query;
        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        
        $query
        ->where([
            $this->aliasField('academic_period_id') => $selectedAcademicPeriodId,
            $this->aliasField('institution_id') => $institutionId
        ])
        ->orderDesc($this->aliasField('created'));
    }
}
