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

class InfrastructureWashSewagesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sewages');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashSewageTypes',   ['className' => 'Institution.InfrastructureWashSewageTypes', 'foreign_key' => 'infrastructure_wash_sewage_type_id']);
        $this->belongsTo('InfrastructureWashSewageFunctionalities',   ['className' => 'Institution.InfrastructureWashSewageFunctionalities', 'foreign_key' => 'infrastructure_wash_sewage_functionality_id']);

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashSewages';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_sewage_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('infrastructure_wash_sewage_functionality_id', ['attr' => ['label' => __('Functionality')]]);
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
        
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Infrastructure WASH Sewage','Details');       
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
            case 'infrastructure_wash_sewage_type_id':
                return __('Type');
            case 'infrastructure_wash_sewage_functionality_id':
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

        $this->fields['infrastructure_wash_sewage_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_sewage_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['infrastructure_wash_sewage_functionality_id']['type'] = 'select';
        $this->field('infrastructure_wash_sewage_functionality_id', ['attr' => ['label' => __('Functionality')]]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $requestQuery = $this->request->query;
        $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $query
        ->where([
            $this->aliasField('institution_id = ') .  $institutionId,
            $this->aliasField('academic_period_id = ') .  $academyPeriodId,
        ])
        ->orderDesc($this->aliasField('id'));
    }
}
