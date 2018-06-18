<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\I18n\Time;

class WorkflowsTable extends AppTable  {
    use OptionsTrait;

    private $modelList = [
        'Report.WorkflowRecords' => [
            'Report.WorkflowStaffLeave' => 'Staff > Career > Leave', // __('Staff Leave')
            'Report.WorkflowInstitution' => 'Institutions > Survey > Forms',
            'Report.WorkflowTrainingCourse' => 'Administration > Training > Courses',
            'Report.WorkflowTrainingSession' => 'Administration > Training > Sessions',
            'Report.WorkflowTrainingSessionResult' => 'Administration > Training > Results',
            'Report.WorkflowStaffTrainingNeed' => 'Staff > Training > Needs',
            'Report.WorkflowInstitutionPosition' => 'Institutions > Positions',
            'Report.WorkflowStaffPositionProfile' => 'Institutions > Staff > Change in Assignment',
            'Report.WorkflowVisitRequest' => 'Institutions > Visits > Requests',
            'Report.WorkflowTrainingApplication' => 'Administration > Training > Applications',
            'Report.WorkflowStaffLicense' => 'Staff > Professional Development > Licenses',
            'Report.WorkflowInstitutionCase' => 'Institutions > Cases',
            'Report.WorkflowStaffTransferIn' => 'Institutions > Staff Transfer > Receiving',
            'Report.WorkflowStaffTransferOut' => 'Institutions > Staff Transfer > Sending',
            'Report.WorkflowStudentWithdraw' => 'Institutions > Students > Student Withdraw',
            'Report.WorkflowStudentAdmission' => 'Institutions > Students > Student Admission',
            'Report.WorkflowStudentTransferIn' => 'Institutions > Student Transfer > Receiving',   
            'Report.WorkflowStudentTransferOut' => 'Institutions > Student Transfer > Sending',
            'Report.WorkflowStaffAppraisal' => 'Staff > Career > Appraisals',
            'Report.WorkflowScholarshipsApplication' => 'Administration > Scholarships > Applications'

        ]
    ];

    public function initialize(array $config) {
        $this->table("workflow_models");
        parent::initialize($config);

        $this->addBehavior('Report.ReportList');

    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        // validation for start/end Date
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        if(array_key_exists('feautre', $context['data'])) {
                            $feature = $context['data']['feature'];
                            return in_array($feature, [$context['data']['feature']]);
                        }
                        return true;
                    }]
            ]);
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', [
            'select' => false,
            'type' => 'select'
        ]);

        $this->ControllerAction->field('format');
        $this->ControllerAction->field('model', [
            'select' => false,
            'type' => 'select'
        ]);
        $this->ControllerAction->field('category', [
            'select' => false,
            'type' => 'select'
        ]);

        $this->ControllerAction->field('academic_period_id');
        $this->ControllerAction->field('report_start_date');
        $this->ControllerAction->field('report_end_date');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $featureOptions = $this->controller->getFeatureOptions($this->alias());

        $attr['options'] = $featureOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldModel(Event $event, array $attr, $action, Request $request)
    {
        if (!isset($this->request->data[$this->alias()]['feature'])) {
            $selectedFeature = key($this->modelList);
        } else {
            $selectedFeature = $this->request->data[$this->alias()]['feature'];
        }

        $attr['options'] = $this->modelList[$selectedFeature];
        return $attr;
    }

    public function onUpdateFieldCategory(Event $event, array $attr, $action, Request $request)
    {
        $categoryOptions = $this->getSelectOptions('WorkflowSteps.category');
        $categoryOptions = ['-1' => __('All Categories')] + $categoryOptions;
        $attr['options'] = $categoryOptions;
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $AcademicPeriodTable->getYearList();
        $currentPeriod = $AcademicPeriodTable->getCurrent();

        $attr['options'] = $academicPeriodOptions;
        $attr['type'] = 'select';
        $attr['select'] = false;
        $attr['onChangeReload'] = true;

        if (empty($request->data[$this->alias()]['academic_period_id'])) {
            $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
        }
        return $attr;
    }

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
        $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
        $attr['value'] = $selectedPeriod->start_date;
        return $attr;
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
        $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
        if ($academicPeriodId != $AcademicPeriods->getCurrent()) {
            $attr['value'] = $selectedPeriod->end_date;
        } else {
            $attr['value'] = Time::now();
        }
        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        
        if (isset($requestData['submit']) && $requestData['submit'] == 'save') {
            if (isset($requestData[$this->alias()]['feature']) && isset($requestData[$this->alias()]['model'])) {
                $requestData[$this->alias()]['feature'] = $requestData[$this->alias()]['model'];

                $this->fields['feature']['options'] = [
                    $requestData[$this->alias()]['feature'] => __('Workflow Records')
                ];
            }
        }
    }
}
