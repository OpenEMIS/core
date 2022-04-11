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

class StaffSubjectsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_subject_staff');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        /*
            note that in DirectoriesController
            if ($model instanceof \Staff\Model\Table\StaffSubjectsTable) {
            $this->toggle('add', false);
         */
		$this->toggle('edit', false);
		$this->toggle('remove', false);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra) {       
        //echo "<pre>"; print_r($extra['query']->toArray());die;
		$this->field('academic_period', []);
		//start:POCOR-5274
        $this->field('institution_class',['sort'  => ['field' =>'InstitutionClasses.name']]);        
		$this->field('institution_subject_id', [ 'sort' => ['field' => 'InstitutionSubjects.name']]);
        //end:POCOR-5274
        $this->field('education_subject', []);
		$this->field('male_students', []);
		$this->field('female_students', []);

		$this->setFieldOrder([
			'academic_period',
			'institution_id',
			'institution_class',
			'institution_subject_id',
			'education_subject',
			'male_students',
			'female_students'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        //echo "<pre>"; print_r($query->toArray());die;
        $query->contain([
			'InstitutionSubjects'
		]);
        //start:POCOR-5274
        $query->find('withClass', ['institution_id' => 6, 'period_id' => 30]);
        
        $sortList = ['InstitutionSubjects.name','start_date','end_date','InstitutionClasses.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        //end:POCOR-5274       

        $extra['options']['sortWhitelist'] = $sortList;
                // Academic Periods
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriods->getCurrent();
                //start:POCOR-5274
                $academicPeriodId = 0;
        
                $academicPeriodOptions = $AcademicPeriods->getYearList();
                $academicPeriodOptions += ['0'=>'All Acedemic Period'];
                //end:POCOR-5274
                if(!empty($this->request->query('academic_period_id'))){
                    $academicPeriodId = $this->request->query('academic_period_id');                     
                }    
                //start:POCOR-5274
                if($academicPeriodId == 0){
                    $query->toArray();
                }else{
                    $query->where(['InstitutionSubjects.academic_period_id' => $academicPeriodId]);
                }
                //end:POCOR-5274
                $this->controller->set(compact('academicPeriodOptions','academicPeriodId'));
               
                
	}
//start:POCOR-5274
    public function findWithClass(Query $query, array $options)
    {
        $queryData = $query->toArray();
        $staff_id = $queryData[0]['staff_id']; 

        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');

        return $query
            ->select([$Classes->aliasField('name')])
            ->leftJoin(
                [$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()],
                [
                    $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $this->aliasField('institution_subject_id')
                ]
            )
            ->leftJoin(
                [$Classes->alias() => $Classes->table()],
                [
                    $Classes->aliasField('id = ') . $InstitutionClassSubjects->aliasField('institution_class_id')
                ]
            )
            ->where([$this->aliasField('staff_id') => $staff_id]);
    }
//end:POCOR-5274    
        public function afterAction(Event $event, ArrayObject $extra)
        {
            
            if ($this->action == 'index') {
                
                $indexElements[] = ['name' => 'Staff.Staff/controls', 'data' => [], 'options' => [], 'order' => 0];
                $extra['elements'] = array_merge($extra['elements'], $indexElements);
            }
           
        }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_subject->institution_id;
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'Subjects',
				'view',
                $this->paramsEncode(['id' => $entity->institution_subject->id]),
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra) {
		$options = ['type' => 'staff'];
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Subjects');

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
            'message' => '{{label}} - ' . $this->getMessage('StaffSubjects.notActiveTeachingStaff'),
            'callable' => function($id) use ($InstitutionStaff, $staffId, $institutionId) {
                $allRelevantStaffRecords = $InstitutionStaff
                    ->find()
                    ->find('staffRecords',
                        [
                            'academicPeriodId' => $id,
                            'staffId' => $staffId,
                            'institutionId' => $institutionId,
                            'positionType' => 1
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
        $this->field('academic_period_id', ['options' => $academicPeriodOptions, 'onChangeReload' => 'changeAcademicPeriodId', 'attr' => ['required' => true]]);

        $classOptions = [];

        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        if (
            array_key_exists($this->alias(), $this->request->data)
             && array_key_exists('academic_period_id', $this->request->data[$this->alias()])
             && !empty($this->request->data[$this->alias()]['academic_period_id']))
        {
            $classOptions = $InstitutionClasses->find('list')
                ->where([
                    $InstitutionClasses->aliasField('institution_id') => $this->request->data[$this->alias()]['institution_id'],
                    $InstitutionClasses->aliasField('academic_period_id') => $this->request->data[$this->alias()]['academic_period_id']
                ])
                ->toArray()
                ;
        }

        $this->field('institution_class_id', ['options' => $classOptions, 'onChangeReload' => 'changeInstitutionClassId', 'attr' => ['required' => true]]);


        $subjectOptions = $this->getSubjectOptions();

        $this->field('subjects', [
            'type' => 'element',
            'element' => 'Institution.Classes/subjects',
            'data' => [
                'subjects' => $subjectOptions,
                'staffId' => $staffId
            ],
        ]);

        $extra['subjectOptions'] = $subjectOptions;
        $extra['staffId'] = $staffId;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra) {
        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
        $subjectOptions = $this->getSubjectOptions();
        // this 'save' does not redirect, need to re-extract the $subjectOptions after saving is done
        $this->fields['subjects']['data']['subjects'] = $subjectOptions;
        $extra['subjectOptions'] = $subjectOptions;
        return $this->controller->redirect($this->url('index'));
    }

    private function getSubjectOptions() {
        $subjectOptions = [];
        
        if (
            array_key_exists($this->alias(), $this->request->data)
             && array_key_exists('institution_class_id', $this->request->data[$this->alias()])
             && !empty($this->request->data[$this->alias()]['institution_class_id']))
        {
            //institution_subject_staff

            $subjectOptions = $this->InstitutionSubjects->find()
                ->matching('Classes', function ($q) {
                    return $q->where(['Classes.id' => $this->request->data[$this->alias()]['institution_class_id']]);
                })
                ->contain([
                    'Teachers' => function ($q) {
                        return $q->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name']);
                    }
                ])
                ->where([
                    $this->InstitutionSubjects->aliasField('institution_id') => $this->request->data[$this->alias()]['institution_id'],
                    $this->InstitutionSubjects->aliasField('academic_period_id') => $this->request->data[$this->alias()]['academic_period_id']
                ])
                ->order([
                        $this->InstitutionSubjects->aliasField('name')
                    ])
                ->toArray();
                
            // data massage for teacher names
            foreach ($subjectOptions as $key => $value) {
                $tempTeacherArray = [];
                
                if ($value->has('teachers')) {
                    foreach ($value->teachers as $tkey => $tvalue) {
                        $tempTeacherArray[$tvalue->id] = $tvalue->name;
                    }
                }
                $subjectOptions[$key]->teachers = $tempTeacherArray;
            }
        }

        return $subjectOptions;
    }


    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $extra['redirect'] = false;
        $subjectOptions = (array_key_exists('subjectOptions', $extra))? $extra['subjectOptions']: [];
        $staffId = (array_key_exists('staffId', $extra))? $extra['staffId']: null;
        $process = function ($model, $entity) use ($requestData, $subjectOptions, $staffId) {
            if (empty($staffId)) return false;
            $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
            $result = false;
            if (array_key_exists('Subjects', $requestData)) {
                foreach ($requestData['Subjects'] as $key => $value) {
                    $selectedSubjects[] = $value['subject_id'];
                }
            } else {
                $selectedSubjects = [];
            }

            foreach ($subjectOptions as $key => $value) {
                $staffWasIn = false;
                if (in_array($staffId, array_keys($value->teachers))) {
                    $staffWasIn = true;
                }

                if (in_array($value->id, $selectedSubjects)) {
                    if (!$staffWasIn) {
                        $InstitutionSubjectStaff->addStaffToSubject($staffId, $value->id, $entity->institution_id);
                    }
                } else {
                    if ($staffWasIn) {
                        $InstitutionSubjectStaff->removeStaffFromSubject($staffId, $value->id);
                    }
                }
            }

            // not using the regular validation methods, cleaning entity to obtain a success message
            $entity->clean();
            return true;
        };
        return $process;
    }
    //start:POCOR-5274
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_class':
                return __('Class');
            case 'institution_subject_id':
                return __('Name');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //end:POCOR-5274

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            if (array_key_exists('subjects', $this->fields) && empty($this->fields['subjects']['data']['subjects'])) {
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

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->alias(), $dataArray) && array_key_exists('institution_class_id', $dataArray[$this->alias()]) ) {
            unset($dataArray[$this->alias()]['institution_class_id']);
        }

        $data->exchangeArray($dataArray);
    }
}
