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

class StaffSubjectsTable extends ControllerActionTable {
    use MessagesTrait;

    public function initialize(array $config): void {
        $this->setTable('institution_subject_staff');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Subjects' =>['institution_subject_id','institution_id']
            ]
        ]);
        $this->addBehavior('Staff.StaffTab');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        //echo "<pre>"; print_r($extra['query']->toArray());die;
        $this->field('academic_period', []);
        //start:POCOR-5274
        $this->field('institution_class',['sort'  => ['field' =>'InstitutionClasses.name']]);
        $this->field('institution_subject_id', [ 'sort' => ['field' => 'InstitutionSubjects.name']]);
        //end:POCOR-5274
        $this->field('education_subject', []);
        $this->field('male_students', []);
        $this->field('female_students', []);
        $this->field('staff_id', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period',
            'institution_id',
            'institution_class',
            'institution_subject_id',
            'education_subject',
            'male_students',
            'female_students'
        ]);

        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Subjects','Staff - Career');
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
			$is_manual_exist = $this->getManualUrl('Directory','Subjects','Staff - Career');
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $data = $query->toArray() ;
        $institutionId = $data[0]['institution_id'];
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $AcademicPeriods->getCurrent();
        $query->contain([ //POCOR-9621[START]
            'InstitutionSubjects' => [
                'EducationSubjects',
            ],
        ]);
        //start:POCOR-5274
        $query->find('withClass', ['institution_id' => $institutionId, 'period_id' => $academicPeriodId]);

        $sortList = ['InstitutionSubjects.name','start_date','end_date','InstitutionClasses.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        //end:POCOR-5274

        $extra['options']['sortWhitelist'] = $sortList;
        // Academic Periods
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriods->getCurrent();
        //start:POCOR-5274
        $academicPeriodId = 0;

        $academicPeriodOptions = $AcademicPeriods->getYearList();
        $academicPeriodOptions += ['0'=>'All Acedemic Period'];
        //end:POCOR-5274
        if(!empty($this->request->getQuery('academic_period_id'))){
            $academicPeriodId = $this->request->getQuery('academic_period_id');
        }
        //start:POCOR-5274
        if($academicPeriodId == 0){
            $query->toArray();
        }else{
            $query->where(['InstitutionSubjects.academic_period_id' => $academicPeriodId]);
        }
        //end:POCOR-5274
        //POCOR-9621[START]
        // Main table has no string columns, so SearchBehavior never adds conditions; search Name + Education Subject via joins.
        $search = isset($extra['config']['search']) ? trim((string) $extra['config']['search']) : '';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $searchOr = [
                $InstitutionSubjects->aliasField('name') . ' LIKE' => $like,
                $EducationSubjects->aliasField('name') . ' LIKE' => $like,
            ];
            if ($extra->offsetExists('OR')) {
                $searchOr = array_merge((array) $extra['OR'], $searchOr);
            }
            $extra['OR'] = $searchOr;
        }
        //POCOR-9621[END]
        $this->controller->set(compact('academicPeriodOptions','academicPeriodId'));


    }

    //start:POCOR-5274
    public function findWithClass(Query $query, array $options)
    {
        $queryData = $query->toArray();
        $staff_id = $queryData[0]['staff_id'];
        if($staff_id == NULL){
            $staff_id  = 1;
        }

        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

        return $query
            ->select([$Classes->aliasField('name')])
            ->leftJoin(
                [$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()],
                [
                    $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $this->aliasField('institution_subject_id')
                ]
            )
            ->leftJoin(
                [$Classes->getAlias() => $Classes->getTable()],
                [
                    $Classes->aliasField('id = ') . $InstitutionClassSubjects->aliasField('institution_class_id')
                ]
            )
            ->where([$this->aliasField('staff_id') => $staff_id])
            ->group([$InstitutionClassSubjects->aliasField('institution_subject_id')]);//POCOR-6710
    }
    //end:POCOR-5274

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {

        if ($this->action == 'index') {
            $queryString = $this->getQueryString();
            $encodedQueryString = $this->paramsEncode($queryString);

            $indexElements[] = ['name' => 'Staff.Staff/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 0];
            $extra['elements'] = array_merge($extra['elements'], $indexElements);
        }

    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $institutionId = $entity->institution_subject->institution_id;
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                0 => 'view',
                //1 => $encodedQueryString,//here we got staff_id, user_id and institution_id
                $this->paramsEncode(['id' => $entity->institution_subject->id, 'institution_id' => $institutionId]),

            ];
            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra) {
        $options = ['type' => 'staff'];
        $tabElements = $this->getCareerTabElements($options);
        $controllerName = $this->controller->getName();
        $selectedAction = 'Subjects';
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);

    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        $staffId = $this->getStaffID();
        $institutionId = $this->getInstitutionID();
        $institutionName = TableRegistry::getTableLocator()->get('Institution.Institutions')->get($institutionId)->name;

        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $academicPeriodOptions = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getYearList();
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

        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        if (
            array_key_exists($this->getAlias(), $this->request->getData())
             && array_key_exists('academic_period_id', $this->request->getData()[$this->getAlias()])
             && !empty($this->request->getData()[$this->getAlias()]['academic_period_id']))
        {
            $classOptions = $InstitutionClasses->find('list')
                ->where([
                    $InstitutionClasses->aliasField('institution_id') => $this->request->getData()[$this->getAlias()]['institution_id'],
                    $InstitutionClasses->aliasField('academic_period_id') => $this->request->getData()[$this->getAlias()]['academic_period_id']
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

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra) {
        $session = $this->request->getSession();
        $staffId = $this->getStaffID();
        $subjectOptions = $this->getSubjectOptions();
        // this 'save' does not redirect, need to re-extract the $subjectOptions after saving is done
        $this->fields['subjects']['data']['subjects'] = $subjectOptions;
        $extra['subjectOptions'] = $subjectOptions;
        return $this->controller->redirect($this->url('index'));
    }

    private function getSubjectOptions() {
        $subjectOptions = [];

        if (
            array_key_exists($this->getAlias(), $this->request->getData())
             && array_key_exists('institution_class_id', $this->request->getData()[$this->getAlias()])
             && !empty($this->request->getData()[$this->getAlias()]['institution_class_id']))
        {
            //institution_subject_staff

            $subjectOptions = $this->InstitutionSubjects->find()
                ->matching('Classes', function ($q) {
                    return $q->where(['Classes.id' => $this->request->getData()[$this->getAlias()]['institution_class_id']]);
                })
                ->contain([
                    'Teachers' => function ($q) {
                        return $q->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name']);
                    }
                ])
                ->where([
                    $this->InstitutionSubjects->aliasField('institution_id') => $this->request->getData()[$this->getAlias()]['institution_id'],
                    $this->InstitutionSubjects->aliasField('academic_period_id') => $this->request->getData()[$this->getAlias()]['academic_period_id']
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


    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $extra['redirect'] = false;
        $subjectOptions = (isset($extra['subjectOptions']))? $extra['subjectOptions']: [];
        $staffId = (isset($extra['staffId']))? $extra['staffId']: null;
        $process = function ($model, $entity) use ($requestData, $subjectOptions, $staffId) {
            if (empty($staffId)) return false;
            $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
            $result = false;
            if (isset($requestData['Subjects'])) {
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
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_class':
                return __('Class');
            case 'institution_subject_id':
                return __('Name');
            case 'academic_period':
                return __('Academic Period');
            case 'institution_id':
                return __('Institution');
            case 'education_subject':
                return __('Education Subject');
            case 'male_students':
                return __('Male Students');
            case 'female_students':
                return __('Female Students');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            if (array_key_exists('subjects', $this->fields) && empty($this->fields['subjects']['data']['subjects'])) {
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

    public function addOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->getAlias(), $dataArray) && array_key_exists('institution_class_id', $dataArray[$this->getAlias()]) ) {
            unset($dataArray[$this->getAlias()]['institution_class_id']);
        }

        $data->exchangeArray($dataArray);
    }
}
