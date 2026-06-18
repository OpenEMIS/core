<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Http\ServerRequest;

class WorkflowsTable extends AppTable
{
    use OptionsTrait;

    private $institutionWorkflowModels = [
        'Report.WorkflowInstitution',
        'Report.WorkflowInstitutionPosition',
        'Report.WorkflowStaffPositionProfile',
        'Report.WorkflowVisitRequest',
        'Report.WorkflowInstitutionCase',
        'Report.WorkflowStaffTransferIn',
        'Report.WorkflowStaffTransferOut',
        'Report.WorkflowStudentWithdraw',
        'Report.WorkflowStudentAdmission',
        'Report.WorkflowStudentTransferIn',
        'Report.WorkflowStudentTransferOut',
    ];

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

    public function initialize(array $config): void
    {
        $this->setTable("workflow_models");
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Area', ['className' => 'Area.Areas', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('Report.ReportList');
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Institution.Institutions',
            'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
        parent::initialize($config);

        $this->addBehavior('Report.ReportList');

    }

    public function beforeAction(EventInterface $event)
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

        if (!isset($this->request->getData($this->getAlias())['feature'])) {
            $selectedFeature = key($this->modelList);
        } else {
            $selectedFeature = $this->request->getData($this->getAlias())['model'];
        }
        if (in_array($selectedFeature, $this->institutionWorkflowModels)) {
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

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $featureOptions = $this->controller->getFeatureOptions($this->getAlias());

        $attr['options'] = $featureOptions;
        return $attr;
    }

    public function onUpdateFieldModel(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (!isset($this->request->getData($this->getAlias())['feature'])) {
            $selectedFeature = key($this->modelList);
        } else {
            $selectedFeature = $this->request->getData($this->getAlias())['feature'];
        }

        $attr['options'] = $this->modelList[$selectedFeature];
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldCategory(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $categoryOptions = $this->getSelectOptions('WorkflowSteps.category');
        $categoryOptions = ['-1' => __('All Categories')] + $categoryOptions;
        $attr['options'] = $categoryOptions;
        return $attr;
    }

    /*public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmpty('institution_id');
        if($request['data']['Workflows']['institution_id'] == 0){
            $validator
            ->notEmpty('report_start_date');
            $validator
            ->notEmpty('report_end_date');
        }
        return $validator;
    }*/

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['model'])) {
            $feature = $this->request->getData($this->getAlias())['model'];
            if (in_array($feature, $this->institutionWorkflowModels)) {
                $attr['options'] = $this->AcademicPeriods->getYearList();
                $attr['default'] = $this->AcademicPeriods->getCurrent();
            }
        }
        return $attr;
    }
    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['model'])) {
            $feature = $this->request->getData($this->getAlias())['model'];
            if (in_array($feature, $this->institutionWorkflowModels)) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
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

    public function onUpdateFieldArea(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['model'])) {
            $feature = $this->request->getData($this->getAlias())['model'];
            if (in_array($feature, $this->institutionWorkflowModels)) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $entity = $attr['entity'];
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
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

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $areaId = $request->getData()['Workflows']['area'];
        $feature = $request->getData($this->getAlias())['model'];
        if(!empty($areaId) && $areaId != 0) {
            $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
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
            $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
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
        if (in_array($feature, $this->institutionWorkflowModels) && count($institutionList) > 1) {
            $institutionOptions = ['0' => __('All Institutions')] + $institutionList;
        } else {
            $institutionOptions = $institutionList;
        }
        if (in_array($feature, $this->institutionWorkflowModels)) { //POCOR-8417
            $attr['attr']['multiple'] = true;
        } else {
            $attr['attr']['multiple'] = false;
            $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionOptions;
        }
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['options'] = $institutionOptions;
        $attr['attr']['required'] = true;
        return $attr;
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
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
            if ($feature == 'Report.WorkflowRecords'
                || 'Report.WorkflowInstitutionPosition'
                || 'Report.WorkflowStaffPositionProfile'
                || 'Report.WorkflowVisitRequest'
                || 'Report.WorkflowInstitutionCase'
                || 'Report.WorkflowStaffTransferIn'
                || 'Report.WorkflowStaffTransferOut'
                || 'Report.WorkflowStudentWithdraw'
                || 'Report.WorkflowStudentAdmission'
                || 'Report.WorkflowStudentTransferIn'
                || 'Report.WorkflowStudentTransferOut') {
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

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {

        if (isset($requestData['submit']) && $requestData['submit'] == 'save') {
            if (isset($requestData[$this->getAlias()]['feature']) && isset($requestData[$this->getAlias()]['model'])) {
                $requestData[$this->getAlias()]['feature'] = $requestData[$this->getAlias()]['model'];

                $this->fields['feature']['options'] = [
                    $requestData[$this->getAlias()]['feature'] => __('Workflow Records')
                ];
            }
        }

        //POCOR-8417
        if (isset($requestData[$this->getAlias()]['model'])
            && in_array($requestData[$this->getAlias()]['model'], $this->institutionWorkflowModels)) {
            $patchOptions['validate'] = 'WorkflowInstitution';
        }
    }

    public function validationWorkflowInstitution(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->add('institution_id', 'required', [
            'rule' => function ($value, $context) {
                if (!empty($context['data']['reload'])) {
                    return true;
                }
                if (empty($value) || !isset($value['_ids'])) {
                    return false;
                }
                $ids = (array)$value['_ids'];
                $ids = array_filter($ids, function ($v) {
                    return $v !== '' && $v !== null;
                });

                return !empty($ids);
            },
            'message' => __('This field cannot be left empty')
        ]);

        return $validator;
    }

    private function parseFilterInstitutionIds($institutionId): array
    {
        $filterInstitutionIds = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId->_ids, function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $filterInstitutionIds = array_values(array_filter((array)$institutionId['_ids'], function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (!empty($institutionId) && $institutionId > 0 && !is_array($institutionId)) {
            $filterInstitutionIds = [(int)$institutionId];
        }

        return $filterInstitutionIds;
    }

    private function isAllInstitutionsSelected($institutionId): bool
    {
        return empty($this->parseFilterInstitutionIds($institutionId));
    }

     public function onUpdateFieldReportStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $institutionId = $request->getData()['Workflows']['institution_id'] ?? null;
        if ($this->isAllInstitutionsSelected($institutionId)) {
            $attr['type'] = 'date';
            $attr['null'] = false;
            $attr['label'] = __('Start Date');
            return $attr;
        }
        
    }


    public function onUpdateFieldReportEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $institutionId = $request->getData()['Workflows']['institution_id'] ?? null;
        if ($this->isAllInstitutionsSelected($institutionId)) {
            $attr['type'] = 'date';
            $attr['null'] = false;
            $attr['label'] = __('End Date');
            return $attr;
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'report_start_date':
                return __('Start Date');
            case 'report_end_date':
                return __('End Date');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            case 'category':
                return __('Category');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
