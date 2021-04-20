<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Traits\MessagesTrait;

use App\Model\Table\ControllerActionTable;

class TransitionTable extends ControllerActionTable
{
    use MessagesTrait;

	public function initialize(array $config)
	{
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->EducationProgrammes      = TableRegistry::get('Education.EducationProgrammes');
        $this->EducationGrades          = TableRegistry::get('Education.EducationGrades');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->notEmpty('education_programme_id');

        return $validator;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->code_name;
    }

    public function onGetEducationProgrammeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->education_programme->cycle_programme_name;
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['student_id']['visible'] = 'false';
        $this->fields['start_year']['visible'] = 'false';
        $this->fields['end_year']['visible'] = 'false';
        $this->fields['photo_content']['visible'] = 'false';
        $this->fields['openemis_no']['visible'] = 'false';
        $this->fields['institution_id']['type'] = 'integer';
        $this->fields['academic_period_id']['sort'] = ['field' => 'AcademicPeriods.name'];

        $this->setFieldOrder([
            'academic_period_id', 'institution_id', 'education_grade_id', 'start_date', 'end_date', 'student_status_id'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        if ($this->controller->name == 'Profiles') {
            $sId = $session->read('Student.Students.id');
            if (!empty($sId)) {
                $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
            } else {
                $studentId = $session->read('Auth.User.id');
            }
        } else {
                $studentId = $session->read('Student.Students.id');
        }
        
        // end POCOR-1893
        $sortList = ['AcademicPeriods.name'];
        
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $query->where([$this->aliasField('student_id') => $studentId]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {   
        $this->field('photo_content', ['type' => 'image', 'before' => 'openemis_no']);
        $this->field('openemis_no',['before' => 'student_id']);
        $this->field('student_status_id',['after' => 'student_id']);
        $this->field('start_year', ['visible' => 'false']);
        $this->field('end_year', ['visible' => 'false']);
        $this->setupTabElements();
        //POCOR-5671 
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
 
        $extraButtons = [
            'process' => [
                'Institution' => ['Institution', 'Institutions'],
                'action' => 'StudentTransition',
                'icon' => '<i class="kd-process"></i>',
                'title' => __('Transition')
            ]
        ];

        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'edit', $this->paramsEncode(['id' => $entity->id]),
                'institution_id' => $entity->institution->id]
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
        //POCOR-5671
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {   
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $AcademicPeriods = $attr['entity']['academic_period']->id;
        $getCycle = $EducationCycles ->find()
                    ->select([$EducationCycles->aliasField('id')])
                    ->leftJoin([$EducationLevels->alias() => $EducationLevels->table()], [
                                $EducationLevels->aliasField('id = ') . $EducationCycles->aliasField('education_level_id')
                    ])
                    ->leftJoin([$EducationSystems->alias() => $EducationSystems->table()], [
                                $EducationSystems->aliasField('id = ') . $EducationLevels->aliasField('education_system_id')
                    ])
                    ->where([$EducationSystems->aliasField('academic_period_id') => $AcademicPeriods]);
        $cycleId = [];
        if (!empty($getCycle)) {
            foreach ($getCycle as $key => $value) {
                $cycleId[] = $value->id;
            }
        }
        $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->contain(['EducationCycles'])
                //->where([$EducationProgrammes->aliasField('education_cycle_id IN') => $cycleId])
                ->toArray();

        if ($action == 'edit') {
            $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
            $attr['type'] = 'select';
            $attr['options'] = $programmeOptions;
            $attr['default'] = $programmeId;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';

        }
        
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['programme']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $selectedProgramme = $EducationProgrammes
                             ->find()
                             ->where([$EducationProgrammes->aliasField('id') => $programmeId])->first()->id;
        if (!empty($request['data'])) {//die("if");
            $programmeId = $request['data']['Transition']['education_programme_id'];
            $gradeOptions = $EducationGrades
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->contain(['EducationProgrammes'])
                        ->where([$EducationGrades->aliasField('education_programme_id') => $programmeId])
                        ->toArray();
        } else {//die("else");
            $programmeId = $attr['entity']['education_grade']->education_programme_id;
            $gradeOptions = $EducationGrades
                        ->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
                        ->contain(['EducationProgrammes'])
                        ->where([$EducationGrades->aliasField('education_programme_id') => $programmeId])
                        ->toArray();
        }
        if ($action == 'edit') {
            $gradeId = $this->EducationGrades->get($attr['entity']->education_grade_id);
            $attr['type'] = 'select';
            $attr['options'] = $gradeOptions;
            $attr['default'] = $gradeId;
            $attr['onChangeReload'] = 'changeEducationGradeId';
        }
        
        return $attr;
    }

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['grade']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $selectedGrade = $request->data[$this->alias()]['education_grade_id'];
                    $request->query['grade'] = $selectedGrade;
                }
            }
        }
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'EducationGrades.EducationProgrammes']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('start_year', ['visible' => 'false']);
        $this->field('end_year', ['visible' => 'false']);

        // Start PHPOE-1897
        $statuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $statuses['CURRENT']) {
            $event->stopPropagation();
            $urlParams = $this->url('view');
            return $this->controller->redirect($urlParams);
        // End PHPOE-1897
        } else {
            $this->field('student_id', [
                'type' => 'readonly',
                'order' => 10,
                'attr' => ['value' => $entity->user->name_with_id]
            ]);
            //$this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
            $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
            $this->field('education_programme_id', ['type' => 'select', 'entity' => $entity]);
            $this->field('education_grade_id', ['type' => 'select', 'entity' => $entity, 'empty' => true]);
            $this->field('student_status_id', ['type' => 'hidden', 'attr' => ['value' => $entity->student_status->name]]);
            $this->field('start_date');
            $this->field('end_date');
        }

        // hide list button
        $btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        
        $extraButtons = [
            'list' => [
                'Institution' => ['Institutions', 'Institutions', 'StudentTransition'],
                'action' => 'StudentTransition',
                'icon' => '<i class="fa kd-lists"></i>',
                'title' => __('List')
            ]
        ];
        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'hidden',
                    'attr' => $btnAttr,
                    'url' => [0 => 'index'] 
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        if (!$entity->isNew()) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

            $AcademicPeriodsId = $requestData['Transition']['academic_period_id'];
            $InstitutionId = $requestData['Transition']['institution_id'];
            $EducationProgrammeId = $requestData['Transition']['education_programme_id'];
            $EducationGradeId = $requestData['Transition']['education_grade_id'];
            $StudentId = $requestData['Transition']['student_id'];
            $startDate =  date("Y-m-d", strtotime($requestData['Transition']['start_date']));
            $endDate = date("Y-m-d", strtotime($requestData['Transition']['end_date']));
            //echo "<pre>";print_r($EducationGradeId);die("Shiva");
            $previousYearId = $AcademicPeriod->find()->where(['id' => $AcademicPeriodsId-1])->first()->id;
            //set student status "Transferred"                    
            $transferStatus = $InstitutionStudents->find()
                            ->select([
                                $InstitutionStudents->aliasField('id'),
                                $EducationGrades->aliasField('id'),
                                $EducationProgrammes->aliasField('id'),
                                $EducationProgrammes->aliasField('order')
                            ])
                            ->leftJoin([$EducationGrades->alias() => $EducationGrades->table()], [
                                $EducationGrades->aliasField('id = ') . $InstitutionStudents->aliasField('education_grade_id')
                            ])
                            ->leftJoin([$EducationProgrammes->alias() => $EducationProgrammes->table()], [
                                $EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')
                            ])
                            ->where([
                                $InstitutionStudents->aliasField('student_id') => $StudentId, 
                                $InstitutionStudents->aliasField('academic_period_id') => $previousYearId, 
                                $InstitutionStudents->aliasField('institution_id') => $InstitutionId
                            ])
                            ->first();
            $currentOrder = $EducationProgrammes->find()->where([$EducationProgrammes->aliasField('id') => $EducationProgrammeId])->first()->order;
            //Transferred
            if(!empty($transferStatus)) {
                $previousEducationGrades = $transferStatus['EducationGrades']['id'];
                $previousEducationProgrammes = $transferStatus['EducationProgrammes']['id'];
                $previousOrder = $transferStatus['EducationProgrammes']['order'];
                if ($previousEducationGrades != $EducationGradeId && $previousEducationProgrammes == $EducationProgrammeId) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 3])
                                ->where([
                                    'institution_id' => $InstitutionId,
                                    'student_id' => $StudentId,
                                    'academic_period_id' => $previousYearId,
                                    'id' => $transferStatus->id
                                ])->execute();
                } elseif ($previousEducationGrades == $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 3])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } /*elseif ($previousEducationGrades != $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 3, 'start_date' => $startDate, 'end_date' => date('Y-m-d')])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                }*/ 
                //Repeated
                elseif ($previousEducationGrades == $EducationGradeId && $previousEducationProgrammes == $EducationProgrammeId && $previousOrder > $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 8])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } elseif ($previousEducationGrades == $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId && $previousOrder > $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 8])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } elseif ($previousEducationGrades != $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId && $previousOrder > $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 8])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } 
                //Promoted
                elseif ($previousEducationGrades == $EducationGradeId && $previousEducationProgrammes == $EducationProgrammeId && $previousOrder < $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 7])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } elseif ($previousEducationGrades == $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId && $previousOrder < $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 7])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                } elseif ($previousEducationGrades != $EducationGradeId && $previousEducationProgrammes != $EducationProgrammeId && $previousOrder < $currentOrder) {
                        $query = $InstitutionStudents->query();
                        $query->update()->set(['student_status_id' => 7])
                            ->where([
                                'institution_id' => $InstitutionId,
                                'student_id' => $StudentId,
                                'academic_period_id' => $previousYearId,
                                'id' => $transferStatus->id
                            ])->execute();
                }
            }
            //echo "<pre>";print_r($this->validator);die();
        }
    }
}
