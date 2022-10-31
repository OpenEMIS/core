<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class InfrastructureWashWastesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_wastes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashWasteTypes',   ['className' => 'Institution.InfrastructureWashWasteTypes', 'foreign_key' => 'infrastructure_wash_waste_type_id']);
        $this->belongsTo('InfrastructureWashWasteFunctionalities',   ['className' => 'Institution.InfrastructureWashWasteFunctionalities', 'foreign_key' => 'infrastructure_wash_waste_functionality_id']);

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashWastes';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_waste_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('infrastructure_wash_waste_functionality_id', ['attr' => ['label' => __('Functionality')]]);
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
            case 'infrastructure_wash_waste_type_id':
                return __('Type');
            case 'infrastructure_wash_waste_functionality_id':
                return __('Functionality');
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

        $this->fields['infrastructure_wash_waste_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_waste_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_waste_functionality_id']['type'] = 'select';
        $this->field('infrastructure_wash_waste_functionality_id', ['attr' => ['label' => __('Functionality')]]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $academicPeriodId = !empty($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();

        $query
        ->where([
            $this->aliasField('institution_id = ') .  $institutionId,
            $this->aliasField('academic_period_id = ') .  $academicPeriodId,
        ])
        ->orderDesc($this->aliasField('id'));
    }

    // POCOR-6148 start
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'InfrastructureWashWastes.infrastructure_wash_waste_type_id',
            'field' => 'infrastructure_wash_waste_type_id',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key'   => 'InfrastructureWashWastes.infrastructure_wash_waste_functionality_id',
            'field' => 'infrastructure_wash_waste_functionality_id',
            'type'  => 'string',
            'label' => __('Functionality')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6148 end
}
