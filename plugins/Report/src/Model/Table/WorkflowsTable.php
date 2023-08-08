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

class WorkflowsTable extends AppTable
{
    use OptionsTrait;

    private $modelList = [
        'Report.WorkflowRecords' => [
            'Report.WorkflowStaffLeave' => 'Staff > Career > Leave',
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
            'Report.WorkflowScholarshipsApplication' => 'Administration > Scholarships > Applications',
            'Report.WorkflowStudentVisitRequest' => 'Student > Visits > Requests'
        ]
    ];

    public function initialize(array $config)
    {
        $this->table("workflow_models");
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Area', ['className' => 'Area.Areas', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('Report.ReportList');
        $this->belongsTo('AreaLevels', ['className' => 'AreaLevel.AreaLevels']);

        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Institution.Institutions',
            'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
        parent::initialize($config);

        $this->addBehavior('Report.ReportList');

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
            'attr' => ['label'=>'Workflow'],
            'type' => 'select'
        ]);
        $this->ControllerAction->field('category', [
            'select' => false,
            'type' => 'select'
        ]);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['type' => 'hidden']);

        if (!isset($this->request->data[$this->alias()]['feature'])) {
            $selectedFeature = key($this->modelList);
        } else {
            $selectedFeature = $this->request->data[$this->alias()]['model'];
        }
        if (in_array($selectedFeature,
        [
            'Report.WorkflowStudentTransferIn',
            'Report.WorkflowStudentTransferOut',
            'Report.WorkflowInstitutionCase',
            'Report.WorkflowInstitution',
            'Report.WorkflowInstitutionPosition',
            'Report.WorkflowStaffPositionProfile',
            'Report.WorkflowVisitRequest',
            'Report.WorkflowStaffTransferIn',
            'Report.WorkflowStaffTransferOut',
            'Report.WorkflowStudentWithdraw',
            'Report.WorkflowStudentAdmission',
            'Report.WorkflowStudentTransferIn'
        ])
        ) {
        $this->ControllerAction->field('institution_id', [
            'select' => false,
            'type' => 'select'
        ]);
        $this->ControllerAction->field('report_start_date',['type'=>'hidden']);
        $this->ControllerAction->field('report_end_date',['type'=>'hidden']);
            $this->ControllerAction->field('academic_period_id', ['select' => false]);

            $this->ControllerAction->field('area', ['type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => false]);
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $featureOptions = $this->controller->getFeatureOptions($this->alias());

        $attr['options'] = $featureOptions;
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
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldCategory(Event $event, array $attr, $action, Request $request)
    {
        $categoryOptions = $this->getSelectOptions('WorkflowSteps.category');
        $categoryOptions = ['-1' => __('All Categories')] + $categoryOptions;
        $attr['options'] = $categoryOptions;
        return $attr;
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmpty('institution_id');
        if($this->request['data']['Workflows']['institution_id'] ==0){
            $validator
            ->notEmpty('report_start_date');
            $validator
            ->notEmpty('report_end_date');
        }
        return $validator;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['model'])) {
            $feature = $this->request->data[$this->alias()]['model'];
            if (in_array($feature, ['Report.WorkflowInstitution', 'Report.WorkflowInstitutionPosition', 'Report.WorkflowStaffPositionProfile'
                , 'Report.WorkflowVisitRequest', 'Report.WorkflowInstitutionCase', 'Report.WorkflowStaffTransferIn',
                'Report.WorkflowStaffTransferOut', 'Report.WorkflowStudentWithdraw', 'Report.WorkflowStudentAdmission',
                'Report.WorkflowStudentTransferIn', 'Report.WorkflowStudentTransferOut'])) {
                $attr['options'] = $this->AcademicPeriods->getYearList();
                $attr['default'] = $this->AcademicPeriods->getCurrent();
            }
        }
        return $attr;
    }
    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['model'])) {
            $feature = $this->request->data[$this->alias()]['model'];
            if (in_array($feature, ['Report.WorkflowInstitution','Report.WorkflowInstitutionPosition','Report.WorkflowStaffPositionProfile'
                ,'Report.WorkflowVisitRequest','Report.WorkflowInstitutionCase','Report.WorkflowStaffTransferIn',
                'Report.WorkflowStaffTransferOut','Report.WorkflowStudentWithdraw','Report.WorkflowStudentAdmission',
                'Report.WorkflowStudentTransferIn','Report.WorkflowStudentTransferOut'])) {
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldArea(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['model'])) {
            $feature = $this->request->data[$this->alias()]['model'];
            if (in_array($feature, ['Report.WorkflowInstitution', 'Report.WorkflowInstitutionPosition', 'Report.WorkflowStaffPositionProfile'
                , 'Report.WorkflowVisitRequest', 'Report.WorkflowInstitutionCase', 'Report.WorkflowStaffTransferIn',
                'Report.WorkflowStaffTransferOut', 'Report.WorkflowStudentWithdraw', 'Report.WorkflowStudentAdmission',
                'Report.WorkflowStudentTransferIn', 'Report.WorkflowStudentTransferOut'])) {
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];
                $Areas = TableRegistry::get('Area.Areas');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                        ->order([$Areas->aliasField('order')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '0' => __('All Areas')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $areaId = $request['data']['Workflows']['area'];
        $feature = $this->request->data[$this->alias()]['model'];
        if(!empty($areaId) && $areaId != 0) {
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $institutionQuery = $InstitutionsTable
                            ->find('list', [
                            'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where(['Institutions.area_id' => $areaId])
                            ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                                $InstitutionsTable->aliasField('name') => 'ASC'
                            ]);

            $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

            $institutionList = $institutionQuery->toArray();
        } else {
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $institutionQuery = $InstitutionsTable
                            ->find('list', [
                            'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                                $InstitutionsTable->aliasField('name') => 'ASC'
                            ]);

            $superAdmin = $this->Auth->user('super_admin');
            if (!$superAdmin) { // if user is not super admin, the list will be filtered
                $userId = $this->Auth->user('id');
                $institutionQuery->find('byAccess', ['userId' => $userId]);
            }
            
            $institutionList = $institutionQuery->toArray();
        }
        if (in_array($feature, ['Report.WorkflowInstitution','Report.WorkflowInstitutionPosition','Report.WorkflowStaffPositionProfile'
                ,'Report.WorkflowVisitRequest','Report.WorkflowInstitutionCase','Report.WorkflowStaffTransferIn',
                'Report.WorkflowStaffTransferOut','Report.WorkflowStudentWithdraw','Report.WorkflowStudentAdmission',
                'Report.WorkflowStudentTransferIn','Report.WorkflowStudentTransferOut']) && count($institutionList) > 1) {
            $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
        } else {
            $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
        }
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['attr']['multiple'] = false;
        $attr['options'] = $institutionOptions;
        $attr['attr']['required'] = true;
        return $attr;
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $fieldsOrder[] = 'feature';
        $fieldsOrder[] = 'model';
        $fieldsOrder[] = 'category';
        $fieldsOrder[] = 'area';
        $fieldsOrder[] = 'institution_id';
        $fieldsOrder[] = 'format';
        /*POCOR-6176 Starts*/
        if ($entity->has('feature')) {
            $feature = $entity->feature;
            $fieldsOrder = ['feature'];
            switch ($feature) {
                case 'Report.WorkflowRecords':
                case 'Report.WorkflowInstitutionPosition':
                case 'Report.WorkflowStaffPositionProfile':
                case 'Report.WorkflowVisitRequest':
                case 'Report.WorkflowInstitutionCase':
                case 'Report.WorkflowStaffTransferIn':
                case 'Report.WorkflowStaffTransferOut':
                case 'Report.WorkflowStudentWithdraw':
                case 'Report.WorkflowStudentAdmission':
                case 'Report.WorkflowStudentTransferIn':
                case 'Report.WorkflowStudentTransferOut':
                    $fieldsOrder[] = 'feature';
                    $fieldsOrder[] = 'model';
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'category';
                    $fieldsOrder[] = 'format';
                    break;
                default:
                    break;
            }
            if ($feature == 'Report.WorkflowRecords' || 'Report.WorkflowInstitutionPosition' || 'Report.WorkflowStaffPositionProfile' || 'Report.WorkflowVisitRequest' || 'Report.WorkflowInstitutionCase' || 'Report.WorkflowStaffTransferIn' || 'Report.WorkflowStaffTransferOut' || 'Report.WorkflowStudentWithdraw' || 'Report.WorkflowStudentAdmission' || 'Report.WorkflowStudentTransferIn' || 'Report.WorkflowStudentTransferOut') {
                $this->ControllerAction->field('area', [
                    'select' => false,
                    'attr' => ['label'=>'Area Name'], //POCOR-7415
                    'type' => 'hidden'
                ]);
            }
        }
        /*POCOR-6176 Ends*/
        $this->ControllerAction->setFieldOrder($fieldsOrder);
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

     public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($request['data']['Workflows']['institution_id'] == 0) {
            $attr['type'] = 'date';
            $attr['null'] = false;
            $attr['label'] = __('test');
            return $attr;
        }
        
    }


    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
       if ($request['data']['Workflows']['institution_id'] == 0) {
            $attr['type'] = 'date';
            $attr['null'] = false;
            return $attr;
        }
    }
}
