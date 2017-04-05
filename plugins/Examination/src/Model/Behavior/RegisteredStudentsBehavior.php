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
use Cake\I18n\Time;

class RegisteredStudentsBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
        $model = $this->_table;

        $model->addBehavior('User.AdvancedNameSearch');
        $model->toggle('edit', false);
        $model->toggle('remove', false);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
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
        $events['ControllerAction.Model.onUpdateFieldAcademicPeriodId'] = 'onUpdateFieldAcademicPeriodId';
        $events['ControllerAction.Model.onUpdateFieldExaminationId'] = 'onUpdateFieldExaminationId';
        $events['ControllerAction.Model.onUpdateFieldOpenemisNo'] = 'onUpdateFieldOpenemisNo';
        $events['ControllerAction.Model.onUpdateFieldStudentId'] = 'onUpdateFieldStudentId';
        $events['ControllerAction.Model.onUpdateFieldDateOfBirth'] = 'onUpdateFieldDateOfBirth';
        $events['ControllerAction.Model.onUpdateFieldGenderId'] = 'onUpdateFieldGenderId';
        $events['ControllerAction.Model.onUpdateFieldInstitutionId'] = 'onUpdateFieldInstitutionId';
        $events['ControllerAction.Model.onUpdateFieldSpecialNeeds'] = 'onUpdateFieldSpecialNeeds';
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
        $ids = $model->paramsDecode($model->paramsPass(0));
        $idKey = $model->getIdKeys($model, $ids);
        $entity = false;

        if ($model->exists($idKey)) {
            $query = $model->find()->where($idKey);

            $query
                ->contain(['Users.SpecialNeeds.SpecialNeedTypes', 'Users.Genders', 'Institutions'], true)
                ->matching('AcademicPeriods')
                ->matching('Examinations');

            $entity = $query->first();
        }

        if ($entity) {
            if ($request->is(['get'])) {
                // get
                $model->Alert->info('general.reconfirm', ['reset' => true]);
            } else if ($request->is(['post', 'put'])) {
                $requestData = $request->data;

                $studentId = $entity->student_id;
                $academicPeriodId = $entity->academic_period_id;
                $examinationId = $entity->examination_id;
                $examinationCentreId = $entity->examination_centre_id;

                $result = $model->deleteAll([
                    'student_id' => $studentId,
                    'examination_id' => $examinationId,
                    'examination_centre_id' => $examinationCentreId
                ]);

                if ($result) {
                    // event to delete all associated records for student
                    $listeners[] = TableRegistry::get('Examination.ExaminationCentreStudents');
                    $model->dispatchEventToModels('Model.Examinations.afterUnregister', [$studentId, $academicPeriodId, $examinationId, $examinationCentreId], $this, $listeners);

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
        // sort attr is required by sortWhitelist
        $model->field('openemis_no', [
            'sort' => ['field' => 'Users.openemis_no']
        ]);
        $model->field('student_id', [
            'type' => 'integer',
            'sort' => ['field' => 'Users.first_name']
        ]);
        $model->field('date_of_birth', ['type' => 'date']);
        $model->field('gender_id');
        $model->field('academic_period_id', ['visible' => false]);
        $model->field('examination_id', ['visible' => false]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $model = $this->_table;
        $session = $this->_table->Session;
        $where = [];

        // Academic Period
        $academicPeriodOptions = $model->AcademicPeriods->getYearList();
        $selectedAcademicPeriod = !is_null($model->request->query('academic_period_id')) ? $model->request->query('academic_period_id') : $model->AcademicPeriods->getCurrent();
        $model->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$model->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['selectedAcademicPeriod'] = $selectedAcademicPeriod;
        // End

        // Examination
        $institutionId = $session->read('Institution.Institutions.id');
        $examinationOptions = $this->getExaminationOptions($selectedAcademicPeriod, $institutionId);
        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($model->request->query('examination_id')) ? $model->request->query('examination_id') : -1;
        $model->controller->set(compact('examinationOptions', 'selectedExamination'));
        $where[$model->aliasField('examination_id')] = $selectedExamination;
        $extra['selectedExamination'] = $selectedExamination;
        // End

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $sortList = ['Users.openemis_no', 'Users.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $search = $model->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $model->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }

        $query
            ->select([
                $model->aliasField('id'),
                $model->aliasField('student_id'),
                $model->aliasField('academic_period_id'),
                $model->aliasField('examination_id'),
                $model->aliasField('registration_number'),
                $model->aliasField('examination_centre_id'),
                $model->Users->aliasField('openemis_no'),
                $model->Users->aliasField('first_name'),
                $model->Users->aliasField('middle_name'),
                $model->Users->aliasField('third_name'),
                $model->Users->aliasField('last_name'),
                $model->Users->aliasField('preferred_name'),
                $model->Users->aliasField('date_of_birth'),
                $model->Users->aliasField('identity_number'),
                $model->Users->Genders->aliasField('name'),
                $model->Institutions->aliasField('code'),
                $model->Institutions->aliasField('name')
            ])
            ->contain(['AcademicPeriods', 'Examinations', 'Institutions', 'Users.Genders'], true)
            ->where($where)
            ->group([
                $model->aliasField('student_id'),
                $model->aliasField('academic_period_id'),
                $model->aliasField('examination_id')
            ]);
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'student_id';
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
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
        $model = $this->_table;

        $query
            ->contain(['Users.SpecialNeeds.SpecialNeedTypes', 'Users.Genders', 'Institutions'])
            ->matching('AcademicPeriods')
            ->matching('Examinations');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
        $model = $this->_table;
        $todayDate = Time::now();

        if ($entity->has('examination')) {
            $registrationStartDate = $entity->examination->registration_start_date;
            $registrationEndDate = $entity->examination->registration_end_date;
        } else if ($entity->has('_matchingData')) {
            $registrationStartDate = $entity->_matchingData['Examinations']->registration_start_date;
            $registrationEndDate = $entity->_matchingData['Examinations']->registration_end_date;
        }

        if ($todayDate >= $registrationStartDate && $todayDate <= $registrationEndDate) {
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
        }


        $this->setupFields($entity, $extra);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        } else if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->openemis_no;
        }

        return $value;
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name;
        } else {
            $value = $entity->_matchingData['Users']->name;
        }
        return $value;
    }

    public function onGetDateOfBirth(Event $event, Entity $entity) {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->date_of_birth;
        }

        return $value;
    }

    public function onGetGenderId(Event $event, Entity $entity) {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->gender->name;
        }

        return $value;
    }

    public function onGetInstitutionId(Event $event, Entity $entity) {
        $value = '';
        if ($entity->has('institution')) {
            $value = $entity->institution->code_name;
        } else {
            $value = '';
        }

        return $value;
    }

    public function onGetSpecialNeeds(Event $event, Entity $entity) {
        $specialNeeds = $this->extractSpecialNeeds($entity);

        return implode(", ", $specialNeeds);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->_matchingData['AcademicPeriods']->name;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->examination_id;
            $attr['attr']['value'] = $entity->_matchingData['Examinations']->name;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $openemisNo = $entity->user->openemis_no;
            $attr['type'] = 'readonly';
            $attr['value'] = $openemisNo;
            $attr['attr']['value'] = $openemisNo;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->student_id;
            $attr['attr']['value'] = $entity->user->name;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBirth(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];
            $dateOfBirth = $entity->user->date_of_birth;

            $attr['type'] = 'readonly';
            $attr['value'] =  date('d-m-Y', strtotime($dateOfBirth));
            $attr['attr']['value'] = $dateOfBirth->format('d-m-Y');
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldGenderId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->user->gender_id;
            $attr['attr']['value'] = $entity->user->gender->name;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            if ($entity->has('institution')) {
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            } else {
                $attr['value'] = 0;
                $attr['attr']['value'] = '';
            }

            $attr['type'] = 'readonly';
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $specialNeeds = $this->extractSpecialNeeds($entity);
            $value = implode(", ", $specialNeeds);

            $attr['type'] = 'readonly';
            $attr['value'] = $value;
            $attr['attr']['value'] = $value;
            $event->stopPropagation();
        }

        return $attr;
    }

    public function onUpdateFieldRegistrationNumber(Event $event, array $attr, $action, Request $request) {
        if ($action == 'unregister') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->registration_number;
            $attr['attr']['value'] = $entity->registration_number;
            $event->stopPropagation();
        }

        return $attr;
    }

    private function getExaminationOptions($selectedAcademicPeriod, $institutionId = null)
    {
        $model = $this->_table;
        $examinationQuery = $model->Examinations
            ->find('list')
            ->where([$model->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod]);

        // in institutions, only show examinations for grades available in the institution
        if ($model->alias() == 'InstitutionExaminationStudents' && !is_null($institutionId)) {
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $availableGrades = $InstitutionGrades
                ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
                ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
                ->toArray();
            $examinationQuery->where([$model->Examinations->aliasField('education_grade_id IN ') => $availableGrades]);
        }

        $examinationOptions = $examinationQuery->toArray();
        return $examinationOptions;
    }

    public function setupFields(Entity $entity, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('examination_centre_id', ['visible' => false]);
        $model->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('examination_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('openemis_no', ['entity' => $entity]);
        $model->field('student_id', ['type' => 'integer', 'entity' => $entity]);
        $model->field('date_of_birth', ['type' => 'date', 'entity' => $entity]);
        $model->field('gender_id', ['entity' => $entity]);
        $model->field('institution_id', ['type' => 'select', 'entity' => $entity]);
        $model->field('special_needs', ['type' => 'string', 'entity' => $entity]);
        $model->field('registration_number', ['type' => 'string', 'entity' => $entity]);

        $model->setFieldOrder(['academic_period_id', 'examination_id', 'openemis_no', 'student_id', 'date_of_birth', 'gender_id', 'institution_id', 'special_needs', 'registration_number']);
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
