<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StaffClassesTable extends ControllerActionTable
{
    use MessagesTrait;

    private $InstitutionClassStudents;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'saveStrategy' => 'replace']);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id',
        ]);

        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);

        /*
            note that in DirectoriesController
            if ($model instanceof \Staff\Model\Table\StaffClassesTable) {
            $this->toggle('add', false);
         */
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    // Academic Period	Institution	Grade	Class	Male Students	Female Students
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['class_number']['visible'] = false;
        $this->fields['institution_shift_id']['visible'] = false;
        $this->fields['capacity']['visible'] = false;

        $this->field('total_students', []);

        $this->setFieldOrder([
            'academic_period_id',
            'institution_id',
            'name',
            'total_male_students',
            'total_female_students',
            'total_students'
        ]);

        // Start POCOR-5188
		if($this->request->params['controller'] == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Classes','Staff - Career');       
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
		}elseif($this->request->params['controller'] == 'Directories'){ 
			$is_manual_exist = $this->getManualUrl('Directory','Classes','Staff - Career');       
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

		}
		// End POCOR-5188

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // POCOR-5914
        $staffId = $this->Session->read('Staff.Staff.id');
        if (!empty($staffId)) {
            $staffId = $this->Session->read('Staff.Staff.id');
        } else {
            $staffId =$this->Session->read('Auth.User.id');
        }
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $classData = $InstitutionClassesSecondaryStaff->find()
                    ->select([$InstitutionClassesSecondaryStaff->aliasField('institution_class_id')])
                    ->where([$InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $staffId])->toArray();
        
        $classIds = [];
        
        if (!empty($classData)) {
            foreach ($classData as $key => $value) {
                $classIds[] = $value->institution_class_id;
            }
        }
        $where = [];
        if (!empty($classIds)) {
          $where = [
                $InstitutionClassesSecondaryStaff->aliasField('institution_class_id IN') => $classIds,
                $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $staffId
            ];
        } else {
            $where = [$InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $staffId];
        }
        // POCOR-5914
        $query->contain([
            'AcademicPeriods',
            'Institutions'
        ])
        // POCOR-5914
        ->leftJoin([$InstitutionClassesSecondaryStaff->alias() => $InstitutionClassesSecondaryStaff->table()], [
            $InstitutionClassesSecondaryStaff->aliasField('institution_class_id = ') . $this->aliasField('id')
        ])
        ->orWhere($where);
        // POCOR-5914
    }

   

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        if (!isset($this->InstitutionClassStudents)) {
            $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        }
        $count = $this->InstitutionClassStudents->getMaleCountByClass($entity->id) + $this->InstitutionClassStudents->getFemaleCountByClass($entity->id);
        return $count.' ';
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->institution->id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'view',
                $this->paramsEncode(['id' => $entity->id]),
                'institution_id' => $institutionId,
            ];
            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Classes');
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = TableRegistry::get('Institution.Institutions')->get($institutionId)->name;

        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $academicPeriodOptions = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getYearList();
        $selectedAcademicPeriod = '';
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod, [
            'message' => '{{label}} - ' . $this->getMessage('StaffClasses.notActiveHomeroomTeacher'),
            'callable' => function ($id) use ($InstitutionStaff, $staffId, $institutionId) {
                $allRelevantStaffRecords = $InstitutionStaff
                    ->find()
                    ->find('staffRecords',
                        [
                            'academicPeriodId' => $id,
                            'staffId' => $staffId,
                            'institutionId' => $institutionId,
                            'isHomeroom' => 1
                        ]
                    );
                return ($allRelevantStaffRecords->count() > 0);
            },
            'selectOption' => false
        ]);

        $this->fields = [];
        $this->field('institution', ['type' => 'readonly', 'attr' => ['value' => $institutionName]]);
        $this->field('institution_id', ['type' => 'hidden', 'attr' => ['value' => $institutionId]]);
        $this->field('staff_id', ['type' => 'hidden', 'attr' => ['value' => $staffId]]);
        $this->field('academic_period_id', ['options' => $academicPeriodOptions, 'onChangeReload' => 'changeAcademicPeriodId']);

        $classOptions = $this->getClassOptions();

        $this->field('classes', [
            'label' => __('Classes'),
            'type' => 'element',
            'element' => 'Institution.Classes/classes',
            'data' => [
                'classes' => $classOptions
            ],
        ]);
        $extra['classOptions'] = $classOptions;
    }

    private function getClassOptions()
    {
        $classOptions = [];
        if (array_key_exists($this->alias(), $this->request->data)
             && array_key_exists('academic_period_id', $this->request->data[$this->alias()])
             && !empty($this->request->data[$this->alias()]['academic_period_id'])) {
            $classOptions = $this->find()
                ->contain(['Users' => function ($q) {
                        return $q->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name']);
                }
                ])
                ->where([
                    $this->aliasField('institution_id') => $this->request->data[$this->alias()]['institution_id'],
                    $this->aliasField('academic_period_id') => $this->request->data[$this->alias()]['academic_period_id']
                ])
                ->toArray()
                ;
        }

        return $classOptions;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $classOptions = $this->getClassOptions();
        // this 'save' does not redirect, need to re-extract the $classOptions after saving is done
        $this->fields['classes']['data']['classes'] = $classOptions;
        $extra['classOptions'] = $classOptions;

        //Webhook Feature class update -- start POCOR-6995

        $classIds = [];
        foreach($entity['Classes'] as $keys=>$val){
           if($val['class_id'] != 0){
                $classIds[] = $val['class_id'];
           }
        }
        $bodyData = $this->find('all',
                    [ 'contain' => [
                        'Institutions',
                        'EducationGrades',
                        'Staff',
                        'AcademicPeriods',
                        'InstitutionShifts',
                        'InstitutionShifts.ShiftOptions',
                        'ClassesSecondaryStaff.SecondaryStaff',
                        'Students',
                        'Students.Genders'
                    ],
                    ])->where([
                        $this->aliasField('id IN') => $classIds
                    ]);
        $grades = $gradeId = $secondaryTeachers = $students = [];
        $dataVal = [];
        if (!empty($bodyData)) {
            foreach ($bodyData as $key => $value) {
                $dataVal[$key]['institutions_id'] = $value->institution->id;
                $dataVal[$key]['institutions_name'] = $value->institution->name;
                $dataVal[$key]['institutions_code'] = $value->institution->code;
                $dataVal[$key]['institutions_classes_name'] = $value->name;
                $dataVal[$key]['institutions_classes_id'] = $value->id;
                $dataVal[$key]['shift_options_name'] = $value->institution_shift->shift_option->name;
                $dataVal[$key]['academic_periods_name'] = $value->academic_period->name;
                $dataVal[$key]['institutions_classes_capacity'] = $value->capacity;
                $dataVal[$key]['institution_classes_staff_openemis_no'] = $value->staff->openemis_no; // for home room teacher

                if(!empty($value->education_grades)) {
                    foreach ($value->education_grades as $i => $gradeOptions) {
                        $dataVal[$key]['Grades'][$i]['education_grades_name'] = $gradeOptions->name;
                        $dataVal[$key]['Grades'][$i]['education_grades_id'] = $gradeOptions->id;
                    }
                }else{
                    $dataVal[$key]['Grades']['education_grades_name'] = NULL;
                    $dataVal[$key]['Grades']['education_grades_name'] = NULL;
                }

                if(!empty($value->classes_secondary_staff)) {
                    foreach ($value->classes_secondary_staff as $j => $secondaryStaffs) {
                       $dataVal[$key]['secondaryTeachers'][$j]['institution_classes_secondary_staff_openemis_no'] = $secondaryStaffs->secondary_staff->openemis_no;
                    }

                }else{
                   $dataVal[$key]['secondaryTeachers']['institution_classes_secondary_staff_openemis_no'] = NULL;
                }
                $maleStudents = 0;
                $femaleStudents = 0;
                if(!empty($value->students)) {
                    foreach ($value->students as $k => $studentsData) {
                        $dataVal[$key]['students'][$k]['institution_class_students_openemis_no'] = $studentsData->openemis_no;
                        if($studentsData->gender->code == 'M') {
                            $maleStudents = $maleStudents + 1;
                            $dataVal[$key]['maleStudents']['institution_classes_total_male_students'] = $maleStudents;
                        }
                        if($studentsData->gender->code == 'F') {
                            $femaleStudents = $femaleStudents + 1;
                            $dataVal[$key]['femaleStudents']['institution_classes_total_female_studentss'] = $femaleStudents;
                        }

                    }
                    $totalStudent = $maleStudents + $femaleStudents ;
                    $dataVal[$key]['total_students'] = $totalStudent;  
                }else{
                    $dataVal[$key]['total_students'] = NULL;
                    $dataVal[$key]['students']['institution_class_students_openemis_no'] = NULL;
                    $dataVal[$key]['maleStudents']['institution_classes_total_male_students'] = NULL;
                    $dataVal[$key]['femaleStudents']['institution_classes_total_female_studentss'] = NULL;
                }
                

            }
        }

        $body = array();
        $body = [
            'institutions_classes' => !empty($dataVal) ? $dataVal : NULL,
        ];
        $Webhooks = TableRegistry::get('Webhook.Webhooks');
        $Webhooks->triggerShell('class_update', ['username' => ''], $body);
        // end POCOR-6995     
    }


    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $extra['redirect'] = false;
        $classOptions = (array_key_exists('classOptions', $extra))? $extra['classOptions']: [];

        $process = function ($model, $entity) use ($requestData, $classOptions) {
            if (array_key_exists('Classes', $requestData)) {
                foreach ($requestData['Classes'] as $key => $value) {
                    $selectedClasses[] = $value['class_id'];
                }
            } else {
                $selectedClasses = [];
            }

            $staffId = $entity->staff_id;
            foreach ($classOptions as $key => $value) {
                $staffWasIn = false;
                $occupiedByOtherStaff = false;
                if ($value->staff_id == $staffId) {
                    $staffWasIn = true;
                } else {
                    if ($value->has('user')) {
                        $occupiedByOtherStaff = true;
                    }
                }

                // adding homeroom teacher
                if (!$staffWasIn && !$occupiedByOtherStaff) {
                    if (in_array($value->id, $selectedClasses)) {
                        $value->staff_id = $staffId;
                        $model->save($value);
                    }
                }

                // removing homeroom teacher
                if ($staffWasIn) {
                    if (!in_array($value->id, $selectedClasses)) {
                        $value->staff_id = 0;
                        $model->save($value);
                    }
                }
            }
            // not using the regular validation methods, cleaning entity to obtain a success message
            $entity->clean();
            return true;
        };

        return $process;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            if (array_key_exists('classes', $this->fields) && empty($this->fields['classes']['data']['classes'])) {
                // if no options data, do not allow them to save
                $buttonsArray = $buttons->getArrayCopy();
                $indexesToRemove = [];
                foreach ($buttonsArray as $key => $value) {
                    if (array_key_exists('attr', $value)) {
                        if (array_key_exists('value', $value['attr'])) {
                            if ($value['attr']['value'] == 'save') {
                                // save button identification
                                $indexesToRemove[] = $key;
                            }
                        }
                    }
                }
                foreach ($indexesToRemove as $key => $value) {
                    // save button removal
                    unset($buttonsArray[$value]);
                }
                $buttons->exchangeArray($buttonsArray);
            }
        }
    }
}
