<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
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

    public function initialize(array $config): void
    {
        $this->setTable('institution_classes');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Classes' =>
                ['id', 'institution_id']
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
    }
    public function beforeAction(EventInterface $event)
    {
        $this->field('institution_unit_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_course_id', ['visible' => false]);//POCOR-6863
    }

    // Academic Period	Institution	Grade	Class	Male Students	Female Students
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['class_number']['visible'] = false;
        $this->fields['institution_shift_id']['visible'] = false;
        $this->fields['capacity']['visible'] = false;

        $this->field('total_students', []);
        $this->field('staff_id', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period_id',
            'institution_id',
            'name',
            'total_male_students',
            'total_female_students',
            'total_students'
        ]);

        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Staff'){
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
		}elseif($this->request->getParam('controller') == 'Directories'){
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // POCOR-5914
        $staffId = $this->getStaffID();
        if (!empty($staffId)) {
            $staffId = $this->getStaffID();
        } else {
            $staffId =$this->Session->read('Auth.User.id');
        }
        $InstitutionClassesSecondaryStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionClassesSecondaryStaff');
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
        ->leftJoin([$InstitutionClassesSecondaryStaff->getAlias() => $InstitutionClassesSecondaryStaff->getTable()], [
            $InstitutionClassesSecondaryStaff->aliasField('institution_class_id = ') . $this->aliasField('id')
        ]);
        // ->orWhere($where); // POCOR-7485
        // POCOR-5914
    }



    public function onGetTotalStudents(EventInterface $event, Entity $entity)
    {
        if (!isset($this->InstitutionClassStudents)) {
            $this->InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        }
        $count = $this->InstitutionClassStudents->getMaleCountByClass($entity->id) + $this->InstitutionClassStudents->getFemaleCountByClass($entity->id);
        return $count.' ';
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
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

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->getCareerTabElements($options);
        $controllerName = $this->controller->getName();
        $selectedAction = 'Classes';
        // if($controllerName == 'Directories') {
        //     $selectedAction = 'StaffClasses';
        // }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $session = $this->request->getSession();
        $staffId = $this->getStaffID();
        $institutionId = $this->getInstitutionID();
        $institutionName = TableRegistry::getTableLocator()->get('Institution.Institutions')->get($institutionId)->name;

        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $academicPeriodOptions = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getYearList();
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
                'classes' => $classOptions,
                'encodedQueryString' => $encodedQueryString,
            ],
        ]);
        $extra['classOptions'] = $classOptions;
    }

    private function getClassOptions()
    {
        $classOptions = [];
        if (array_key_exists($this->getAlias(), $this->request->getData())
             && array_key_exists('academic_period_id', $this->request->getData()[$this->getAlias()])
             && !empty($this->request->getData()[$this->getAlias()]['academic_period_id'])) {
            $classOptions = $this->find()
                ->contain(['Users' => function ($q) {
                        return $q->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name']);
                }
                ])
                ->where([
                    $this->aliasField('institution_id') => $this->request->getData()[$this->getAlias()]['institution_id'],
                    $this->aliasField('academic_period_id') => $this->request->getData()[$this->getAlias()]['academic_period_id']
                ])
                ->toArray()
                ;
        }

        return $classOptions;
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $classOptions = $this->getClassOptions();
        // this 'save' does not redirect, need to re-extract the $classOptions after saving is done
        $this->fields['classes']['data']['classes'] = $classOptions;
        $extra['classOptions'] = $classOptions;

        // POCOR-9403 webhook call moved to institutionclass

    }


    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $extra['redirect'] = false;
        $classOptions = (isset($extra['classOptions']))? $extra['classOptions']: [];

        $process = function ($model, $entity) use ($requestData, $classOptions) {
            if (isset($requestData['Classes'])) {
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else if ($field == 'academic_period_id') {
            return  __('Academic Period');
        } else if ($field == 'institution_id') {
            return  __('Institution');
        } else if ($field == 'total_students') {
            return  __('Total Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            if (array_key_exists('classes', $this->fields) && empty($this->fields['classes']['data']['classes'])) {
                // if no options data, do not allow them to save
                $buttonsArray = $buttons->getArrayCopy();
                $indexesToRemove = [];
                foreach ($buttonsArray as $key => $value) {
                    if (isset($value['attr'])) {
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
