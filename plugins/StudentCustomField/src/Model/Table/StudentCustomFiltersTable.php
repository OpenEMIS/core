<?php
namespace StudentCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;//POCOR-8434
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class StudentCustomFiltersTable extends ControllerActionTable
{
    private $dataCount = null;

    public function initialize(array $config): void
    {
        $this->setTable('student_custom_filters');
        parent::initialize($config);
        $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules', 'foreignKey' => 'custom_module_id']);
        $this->belongsTo('StudentCustomForms', ['className' => 'StudentCustomField.StudentCustomForms', 'foreignKey' => 'student_custom_form_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'foreignKey' => 'education_programme_id']);
        
        $this->CustomModules = TableRegistry::get('CustomField.CustomModules');
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('custom_module_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);
		$this->field('student_custom_form_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);
		$this->field('education_programme_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);
		$this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);

		$this->setFieldOrder(['custom_module_id', 'student_custom_form_id', 'academic_period_id', 'education_programme_id', 'name']);
	}

    public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
        ->requirePresence('custom_module_id')
        ->requirePresence('student_custom_form_id')
        ->requirePresence('academic_period_id')
        ->requirePresence('education_programme_id')
        ->requirePresence('name', [
            'ruleUnique' => [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('This field has to be unique')
            ]
        ]);
        return $validator;
	}

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
       
        foreach ($buttons as $key => $button){
            
            if($button['url'][0] == 'edit' || $button['url'][0] == 'view'){
                $buttonUrl = $button['url'];
                $queryString = $button['url'][1];

                $custom_module_id = $entity->custom_module_id;
                $button['url']['?']['custom_module_id'] = $custom_module_id;
                $buttons[$key] = $button;
            }
        }
        return $buttons;
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-8434 starts
        $selectedModuels = ['Student', 'Student > Registrations'];
        $CustomModulesTable = TableRegistry::get('CustomField.CustomModules');
        $module = $CustomModulesTable
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->where([$CustomModulesTable->aliasField('code IN') => $selectedModuels])
                    ->toArray(); 
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['options'] = $module;
                $attr['onChangeReload'] = 'customModule';
            } else {
                if(!is_null($request->getData('StudentCustomFilters')['custom_module_id'])){
                    $custom_module_id = $request->getData('StudentCustomFilters')['custom_module_id'];
                }
                
                $attr['value'] = $custom_module_id;
                $attr['options'] = $module;
                $attr['attr']['value'] = $module->name;
                $attr['onChangeReload'] = 'customModule';
            }
        }
        $attr['type'] = 'select';
        //POCOR-8434 ends
        return $attr;
    }

    public function addEditOnCustomModule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
       
        $request = $request->withQueryParams($request->getQueryParams());
        unset($request->getQueryParams()['period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('custom_module_id', $request->getData()[$this->getAlias()])) {
                    $customModuleId = $request->getData()[$this->getAlias()]['custom_module_id'];
                    $this->request = $request->withQueryParams(['custom_module_id' => $customModuleId]);

                }
            }
        }
    }

    public function onUpdateFieldStudentCustomFormId(Event $event, array $attr, $action, $request) {
        if(!is_null($request->getData('StudentCustomFilters')['custom_module_id'])){
            $custom_module_id = $request->getData('StudentCustomFilters')['custom_module_id'];
            $StudentCustomFormsTable = TableRegistry::get('StudentCustomField.StudentCustomForms');
            $module = $StudentCustomFormsTable
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->where([$StudentCustomFormsTable->aliasField('custom_module_id') => $custom_module_id])
                        ->toArray();
            if(empty($module)){
                $attr['empty'] = 'Select'; 
                return $attr;
            }
        }else{
            if ($action == 'add' || $action == 'edit') {
                if ($action == 'add') {
                    $module = [];
                } else {
                    if(!is_null($request->getQuery('custom_module_id'))){
                        $custom_module_id = $request->getQuery('custom_module_id');
                        $StudentCustomFormsTable = TableRegistry::get('StudentCustomField.StudentCustomForms');
                        $module = $StudentCustomFormsTable
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->where([$StudentCustomFormsTable->aliasField('custom_module_id') => $custom_module_id])
                                    ->toArray();
                    }
                    $attr['value'] = $custom_module_id;
                    $attr['options'] = $module;
                    $attr['attr']['value'] = $module->name;
                }
            }
        }
        $attr['type'] = 'select';
        $attr['options'] = $module;
        return $attr;
	}

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action)
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriods->getYearList();
				
        $attr['type'] = 'select';
        $attr['placeholder'] = __('Select Academic Periods');
        $attr['attr']['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
        return $attr;
    }

    public
    function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;

        if ($action == 'add' || $action == 'edit') {
            $academic_period_id = $request->getData('StudentCustomFilters')['academic_period_id'];
            
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($academic_period_id) ? $academic_period_id : $AcademicPeriod->getCurrent();
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                        ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                        ->find('availableProgrammes')
                        ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                        ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                        ->toArray();
            } else {
                $programmeId = $this->request->getData('StudentCustomFilters.education_programme_id');
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                    ->toArray();

                $attr['value'] = $programmeId;
            }
            $attr['options'] = $programmeOptions;
        }
        return $attr;
    }
}
