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
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

class RegisteredStudentsBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);

        $model = $this->_table;
        $model->toggle('edit', false); // temporary not allow edit
        $model->toggle('remove', false);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.edit.beforeSave'] = 'editBeforeSave';
        $events['ControllerAction.Model.edit.afterSave'] = 'editAfterSave';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        $events['ControllerAction.Model.unregister'] = 'unregister';
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    public function unregister(Event $event, ArrayObject $extra) {
        $model = $this->_table;
        $request = $model->request;
        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];

        // back button
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $model->url('view');
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End

        $primaryKey = $model->getPrimaryKey();
        $idKey = $model->aliasField($primaryKey);
        $id = $model->paramsPass(0);
        $entity = false;

        if ($model->exists([$idKey => $id])) {
            $query = $model->find()->where([$idKey => $id]);

            $query
                ->contain(['Users.SpecialNeeds.SpecialNeedTypes'])
                ->matching('AcademicPeriods')
                ->matching('Examinations')
                ->matching('Institutions');

            $entity = $query->first();
        }

        if ($entity) {
            if ($request->is(['get'])) {
                // get
                $model->Alert->info('general.reconfirm', ['reset' => true]);
            } else if ($request->is(['post', 'put'])) {
                $requestData = $request->data;

                $studentId = $entity->student_id;
                $educationGradeId = $entity->education_grade_id;
                $academicPeriodId = $entity->academic_period_id;
                $examinationId = $entity->examination_id;

                $result = $model->deleteAll([
                    'student_id' => $studentId,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId,
                    'examination_id' => $examinationId
                ]);

                if ($result) {
                    $model->Alert->success('general.delete.success', ['reset' => 'override']);
                } else {
                    $model->Alert->error('general.delete.failed', ['reset' => 'override']);
                }

                $event->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }

            $model->controller->set('data', $entity);
        }

        $this->setupFields($entity, $extra);

        if (!$entity) {
            $event->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }

        return $entity;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons) {
        $model = $this->_table;
        switch ($model->action) {
            case 'unregister':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Unregister');
                $buttons[1]['url'] = $model->url('view');
                break;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('openemis_no', ['sort' => true]);
        $model->field('student_id', [
            'type' => 'select',
            'sort' => ['field' => 'Users.first_name']
        ]);
        $model->field('education_grade_id', ['visible' => false]);
        $model->field('academic_period_id', ['visible' => false]);
        $model->field('examination_id', ['visible' => false]);
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

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $query
            ->where($where)
            ->group([
                $model->aliasField('student_id'),
                $model->aliasField('academic_period_id'),
                $model->aliasField('examination_id')
            ])
            ->order([$model->Institutions->aliasField('name') => 'asc']);
    }

    public function indexAfterAction(Event $event, ResultSet $resultSet, ArrayObject $extra) {
        $model = $this->_table;
        $session = $model->request->session();

        $successSessionKey = $model->registryAlias() . '.success';
        $errorSessionKey = $model->registryAlias() . '.error';
        $warningSessionKey = $model->registryAlias() . '.warning';

        if ($session->check($successSessionKey)) {
            $successKey = $session->read($successSessionKey);
            $model->Alert->success($successKey);
        } else if ($session->check($errorSessionKey)) {
            $errorKey = $session->read($errorSessionKey);
            $model->Alert->error($errorKey);
        } else if ($session->check($warningSessionKey)) {
            $warningKey = $session->read($warningSessionKey);
            $model->Alert->warning($warningKey);
        }

        $session->delete($successSessionKey);
        $session->delete($errorSessionKey);
        $session->delete($warningSessionKey);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query
            ->contain(['Users.SpecialNeeds.SpecialNeedTypes'])
            ->matching('AcademicPeriods')
            ->matching('Examinations')
            ->matching('Institutions');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
        $model = $this->_table;

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        // unregister button
        $url = $model->url('view');
        $url[0] = 'unregister';

        $unregisterButton = $toolbarButtonsArray['back'];
        $unregisterButton['label'] = '<i class="fa fa-undo"></i>';
        $unregisterButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
        $unregisterButton['attr']['title'] = __('Unregister');
        $unregisterButton['url'] = $url;

        $toolbarButtonsArray['unregister'] = $unregisterButton;
        // End

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupFields($entity, $extra);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra) {
        $process = function ($model, $entity) use ($requestData) {
            $conn = ConnectionManager::get('default');
            $conn->begin();

            $studentId = $entity->student_id;
            $institutionId = $entity->institution_id;
            $educationGradeId = $entity->education_grade_id;
            $academicPeriodId = $entity->academic_period_id;
            $examinationId = $entity->examination_id;

            $model->deleteAll([
                'student_id' => $studentId,
                'education_grade_id' => $educationGradeId,
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
                        'education_grade_id' => $educationGradeId,
                        'academic_period_id' => $academicPeriodId,
                        'examination_id' => $examinationId,
                        'education_subject_id' => $subjectId
                    ];

                    if (!empty($examinationCentreId)) {
                        $data['examination_centre_id'] = $examinationCentreId;
                        $newEntities[] = $model->newEntity($data);
                    }
                }

                $result = $model->saveMany($newEntities);
                if ($result) {
                    $conn->commit();
                } else {
                    $conn->rollback();
                    Log::write('debug', $newEntities->errors());
                }

                return $result;
            }
        };

        return $process;
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
        $model = $this->_table;

        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.success';
        $session->write($sessionKey, 'general.edit.success');

        $url = $model->url('index', 'QUERY');
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
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        } else if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->openemis_no;
        }

        return $value;
    }

    public function onGetSpecialNeeds(Event $event, Entity $entity) {
        $specialNeeds = $this->extractSpecialNeeds($entity);

        return implode(", ", $specialNeeds);
    }

    public function onGetCustomSubjectsElement(Event $event, $action, $entity, $attr, $options=[]) {
        $model = $this->_table;

        $action = $model->action == 'unregister' ? 'view' : $action;
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
                ->order(['EducationSubjects.order'])
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
            $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
            $arraySubjects = $ExaminationItems->find()
                ->select([
                    'education_subject_id' => 'EducationSubjects.id',
                    'code' => 'EducationSubjects.code',
                    'name' => 'EducationSubjects.name',
                    'examination_centre_id' => $model->aliasField('examination_centre_id')
                ])
                ->leftJoin(
                    [$model->alias() => $model->table()],
                    [
                        $model->aliasField('education_subject_id = ') . $ExaminationItems->aliasField('education_subject_id'),
                        $model->aliasField('examination_id') => $entity->examination_id
                    ]
                )
                ->matching('EducationSubjects')
                ->where([$ExaminationItems->aliasField('examination_id') => $entity->examination_id])
                ->group([$ExaminationItems->aliasField('education_subject_id')])
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
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->_matchingData['AcademicPeriods']->name;
        }

        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->examination_id;
            $attr['attr']['value'] = $entity->_matchingData['Examinations']->name;
        }

        return $attr;
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $openemisNo = $entity->user->openemis_no;
            $attr['type'] = 'readonly';
            $attr['value'] = $openemisNo;
            $attr['attr']['value'] = $openemisNo;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->student_id;
            $attr['attr']['value'] = $entity->user->name;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->_matchingData['Institutions']->name;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, Request $request) {
        if ($action == 'edit' || $action == 'unregister') {
            $entity = $attr['entity'];

            $specialNeeds = $this->extractSpecialNeeds($entity);
            $value = implode(", ", $specialNeeds);

            $attr['type'] = 'readonly';
            $attr['value'] = $value;
            $attr['attr']['value'] = $value;
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

    public function setupFields(Entity $entity, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('examination_centre_id', ['visible' => false]);
        $model->field('education_grade_id', ['visible' => false]);
        $model->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('examination_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('openemis_no', ['entity' => $entity]);
        $model->field('student_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('institution_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('special_needs', ['type' => 'string', 'entity' => $entity]);
        // temporary hide subjects
        // $model->field('subjects', ['type' => 'custom_subjects']);

        $model->setFieldOrder(['academic_period_id', 'examination_id', 'openemis_no', 'student_id', 'institution_id', 'subjects']);
    }

    public function extractSpecialNeeds(Entity $entity) {
        $specialNeeds = [];
        if ($entity->has('user')) {
            foreach ($entity->user->special_needs as $key => $obj) {
                $specialNeeds[] = $obj->special_need_type->name;
            }
        }

        return $specialNeeds;
    }
}
