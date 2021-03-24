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
        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('Report.ReportList');
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
        if (!isset($this->request->data[$this->alias()]['feature'])) {
            $selectedFeature = key($this->modelList);
        } else {
            $selectedFeature = $this->request->data[$this->alias()]['model'];
        }
        if (in_array($selectedFeature, 
        [
            'Report.WorkflowStudentTransferIn'
        ])
        ) {
        $this->ControllerAction->field('institution_id', [
            'select' => false,
            'type' => 'select'
        ]);
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

        return $validator;
	}

    public function onUpdateFieldArea(Event $event, array $attr, $action, Request $request)
    {
        
        $AreaTable = TableRegistry::get('Area.Areas');
        $AreaQuery = $AreaTable
                        ->find('list', [
                        'keyField' => 'id',
                            'valueField' => 'name'
                        ])
                        ->order([
                        $AreaTable->aliasField('code') => 'ASC',
                            $AreaTable->aliasField('name') => 'ASC'
                        ]);

        $AreaList = $AreaQuery->toArray();
        $AreaOptions = ['' => '-- ' . __('Select') . ' --'] + $AreaList;//POCOR-5906 ends
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['attr']['multiple'] = false;
        $attr['options'] = $AreaOptions;
        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $Areaid = $request['data']['Workflows']['area'];
        //for POCOR-5992
        if($Areaid != ''){
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $institutionQuery = $InstitutionsTable
                            ->find('list', [
                            'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where(['Institutions.area_id' => $Areaid])
                            ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                                $InstitutionsTable->aliasField('name') => 'ASC'
                            ]);

            $institutionList = $institutionQuery->toArray();
        }
        else{
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

            $institutionList = $institutionQuery->toArray();
        }
        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
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
}
