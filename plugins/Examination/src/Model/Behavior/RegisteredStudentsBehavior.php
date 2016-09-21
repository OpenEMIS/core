<?php 
namespace Examination\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\Behavior;
use Cake\Network\Request;
use Cake\Event\Event;

class RegisteredStudentsBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        $events['ControllerAction.Model.edit.beforeSave'] = 'editBeforeSave';
        $events['ControllerAction.Model.edit.afterSave'] = 'editAfterSave';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        // hide add button
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('add', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['add']);   
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End

        $model = $this->_table;
        $model->field('openemis_no', ['sort' => true]);
        $model->field('student_id', [
            'type' => 'select',
            'sort' => ['field' => 'Users.first_name']
        ]);
        $model->field('institution_id', ['type' => 'select']);
        $model->field('academic_period_id', ['visible' => false]);
        $model->field('examination_id', ['visible' => false]);
        $model->field('examination_centre_id', ['type' => 'select']);
        $model->field('education_subject_id', ['visible' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $model = $this->_table;
        $where = [];

        // Academic Period
        $academicPeriodOptions = $model->AcademicPeriods->getYearList();
        $selectedAcademicPeriod = !is_null($model->request->query('academic_period_id')) ? $model->request->query('academic_period_id') : $model->AcademicPeriods->getCurrent();
        $model->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$model->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        // End

        // Examination
        $examinationOptions = $this->getExaminationOptions($selectedAcademicPeriod);
        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($model->request->query('examination_id')) ? $model->request->query('examination_id') : -1;
        $model->controller->set(compact('examinationOptions', 'selectedExamination'));
        $where[$model->aliasField('examination_id')] = $selectedExamination;
        // End

        // Education Subject
        $subjectOptions = $this->getExaminationItemOptions($selectedExamination);
        $subjectOptions = ['-1' => '-- '.__('Select Subject').' --'] + $subjectOptions;
        $selectedSubject = !is_null($model->request->query('education_subject_id')) ? $model->request->query('education_subject_id') : -1;
        $model->controller->set(compact('subjectOptions', 'selectedSubject'));
        $where[$model->aliasField('education_subject_id')] = $selectedSubject;
        // End

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $query
            ->where($where)
            ->order([
                $model->Institutions->aliasField('name') => 'asc',
                $model->Examinations->aliasField('name') => 'asc',
                $model->ExaminationCentres->aliasField('name') => 'asc',
                $model->EducationSubjects->aliasField('name') => 'asc'
            ]);
    }

    public function indexAfterAction(Event $event, ResultSet $resultSet, ArrayObject $extra) {
        $model = $this->_table;
        $session = $model->request->session();

        $sessionKey = $model->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $model->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query
            ->matching('AcademicPeriods')
            ->matching('Examinations')
            ->matching('Users')
            ->matching('Institutions');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
        $this->setupFields($entity, $extra);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra) {
        $model = $this->_table;

        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.warning';
        $session->write($sessionKey, $model->aliasField('restrictAdd'));

        $url = $model->url('index');
        $event->stopPropagation();
        return $model->controller->redirect($url);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra) {
        $process = function ($model, $entity) use ($requestData) {
            $studentId = $entity->student_id;
            $institutionId = $entity->institution_id;
            $academicPeriodId = $entity->academic_period_id;
            $examinationId = $entity->examination_id;

            $model->deleteAll([
                'student_id' => $studentId,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'examination_id' => $examinationId
            ]);

            if (array_key_exists($model->alias(), $requestData) && array_key_exists('education_subjects', $requestData[$model->alias()])) {
                $newEntities = [];
                foreach ($requestData[$model->alias()]['education_subjects'] as $key => $obj) {
                    $subjectId = $obj['education_subject_id'];
                    $examinationCentreId = $obj['examination_centre_id'];
                    $data = [
                        'student_id' => $studentId,
                        'institution_id' => $institutionId,
                        'academic_period_id' => $academicPeriodId,
                        'examination_id' => $examinationId,
                        'education_subject_id' => $subjectId
                    ];

                    if (!empty($examinationCentreId)) {
                        $data['examination_centre_id'] = $examinationCentreId;
                        $newEntities[] = $model->newEntity($data);
                    }
                }

                return $model->saveMany($newEntities);
            }
        };

        return $process;
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
        $model = $this->_table;
        $url = $model->url('index', false);
        $url['academic_period_id'] = $entity->academic_period_id;
        $url['examination_id'] = $entity->examination_id;

        $event->stopPropagation();
        return $model->controller->redirect($url);
    }

    public function editAfterAction(Event $event, Entity $entity, arrayObject $extra) {
        $this->setupFields($entity, $extra);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity) {
        $value = '';
        if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->openemis_no;
        } else if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }

        return $value;
    }

    public function onGetCustomSubjectsElement(Event $event, $action, $entity, $attr, $options=[]) {
        $model = $this->_table;

        if ($action == 'view') {
            $tableHeaders = [__('Name'), __('Code'), __('Examination Centre')];
            $tableCells = [];

            $examinationStudents = $model->find()
                ->matching('EducationSubjects')
                ->matching('ExaminationCentres')
                ->where([
                    $model->aliasField('academic_period_id') => $entity->academic_period_id,
                    $model->aliasField('examination_id') => $entity->examination_id,
                    $model->aliasField('student_id') => $entity->student_id
                ])
                ->toArray();

            foreach ($examinationStudents as $key => $obj) {
                $rowData = [];
                $rowData[] = $obj->_matchingData['EducationSubjects']->name;
                $rowData[] = $obj->_matchingData['EducationSubjects']->code;
                $rowData[] = $obj->_matchingData['ExaminationCentres']->name;
                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;

            $tableHeaders = [__('Name'), __('Code'), __('Examination Centre')];
            $tableCells = [];
            $cellCount = 0;

            $form->unlockField($attr['model'] . '.education_subjects');
            $subjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $arraySubjects = $subjectStudents->find()
                ->select([
                    'education_subject_id' => 'EducationSubjects.id',
                    'code' => 'EducationSubjects.code',
                    'name' => 'EducationSubjects.name',
                    'examination_centre_id' => $model->aliasField('examination_centre_id')
                ])
                ->leftJoin(
                    [$model->alias() => $model->table()],
                    [
                        $model->aliasField('student_id = ') . $subjectStudents->aliasField('student_id'),
                        $model->aliasField('institution_id = ') . $subjectStudents->aliasField('institution_id'),
                        $model->aliasField('academic_period_id = ') . $subjectStudents->aliasField('academic_period_id'),
                        $model->aliasField('education_subject_id = ') . $subjectStudents->aliasField('education_subject_id')
                    ]
                )
                ->matching('EducationSubjects')
                ->where([
                    $subjectStudents->aliasField('student_id') => $entity->student_id,
                    $subjectStudents->aliasField('institution_id') => $entity->institution_id,
                    $subjectStudents->aliasField('academic_period_id') => $entity->academic_period_id
                ])
                ->group([$subjectStudents->aliasField('education_subject_id')])
                ->order(['EducationSubjects.order'])
                ->toArray();

            foreach ($arraySubjects as $key => $obj) {
                $fieldPrefix = $attr['model'] . '.education_subjects.' . $cellCount++;

                $subjectId = $obj->education_subject_id;
                $examinationCentreId = $obj->examination_centre_id;
                $selectedAcademicPeriod = $entity->academic_period_id;
                $selectedExamination = $entity->examination_id;
                $ExaminationCentres = TableRegistry::get('Examination.ExaminationCentres');
                $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');

                $examinationCentreOptions = $ExaminationCentres->find('list')
                    ->matching('ExaminationCentreSubjects', function ($q) use ($subjectId) {
                        return $q->where(['education_subject_id' => $subjectId]);
                    })
                    ->where([
                        $ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $ExaminationCentres->aliasField('examination_id') => $selectedExamination
                    ])
                    ->toArray();

                if (empty($examinationCentreOptions)) {
                    $examinationCentreOptions = ['' => $model->getMessage('general.select.noOptions')];
                } else {
                    $examinationCentreOptions = ['' => '-- '.__('Select').' --'] + $examinationCentreOptions;
                }

                $cellData = "";
                $cellData .= $form->input($fieldPrefix.".examination_centre_id", ['label' => false, 'type' => 'select', 'options' => $examinationCentreOptions, 'value' => $examinationCentreId]);
                $cellData .= $form->hidden($fieldPrefix.".education_subject_id", ['value' => $subjectId]);

                $rowData = [];
                $rowData[] = $obj->name;
                $rowData[] = $obj->code;
                $rowData[] = $cellData;
                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Examination.subjects', ['attr' => $attr]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->_matchingData['AcademicPeriods']->name;
        }

        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->examination_id;
            $attr['attr']['value'] = $entity->_matchingData['Examinations']->name;
        }

        return $attr;
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $openemisNo = $entity->_matchingData['Users']->openemis_no;
            $attr['type'] = 'readonly';
            $attr['value'] = $openemisNo;
            $attr['attr']['value'] = $openemisNo;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->student_id;
            $attr['attr']['value'] = $entity->_matchingData['Users']->name;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->_matchingData['Institutions']->name;
        }

        return $attr;
    }

    public function getExaminationOptions($selectedAcademicPeriod) {
        $model = $this->_table;
        $examinationOptions = $model->Examinations
            ->find('list')
            ->where([$model->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }

    public function getExaminationItemOptions($selectedExamination) {
        $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
        $subjectOptions = $ExaminationItems
            ->find('list', ['keyField' => 'education_subject.id', 'valueField' => 'education_subject.name'])
            ->contain(['EducationSubjects'])
            ->where([$ExaminationItems->aliasField('examination_id') => $selectedExamination])
            ->toArray();

        return $subjectOptions;
    }

    public function setupFields(Entity $entity, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('examination_centre_id', ['visible' => false]);
        $model->field('education_subject_id', ['visible' => false]);
        $model->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('examination_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('openemis_no', ['entity' => $entity]);
        $model->field('student_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('institution_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('subjects', ['type' => 'custom_subjects']);

        $model->setFieldOrder(['academic_period_id', 'examination_id', 'openemis_no', 'student_id', 'institution_id', 'subjects']);
    }
}
