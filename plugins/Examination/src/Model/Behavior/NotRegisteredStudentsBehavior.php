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
use Cake\Log\Log;

class NotRegisteredStudentsBehavior extends Behavior {

    private $identityType;

    public function initialize(array $config) {
		parent::initialize($config);

        $model = $this->_table;
        $model->toggle('add', false);
        $model->toggle('edit', false);
        $model->toggle('remove', false);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.onGetFieldLabel'] = ['callable' => 'onGetFieldLabel', 'priority' => 20];
        return $events;
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
        $model->field('student_status_id', ['visible' => false]);
        $model->field('education_grade_id', ['visible' => false]);
        $model->field('academic_period_id', ['visible' => false]);
        $model->field('start_date', ['visible' => false]);
        $model->field('start_year', ['visible' => false]);
        $model->field('end_date', ['visible' => false]);
        $model->field('end_year', ['visible' => false]);
        $model->field('previous_institution_student_id', ['visible' => false]);

        $model->field('nationality');
        $model->field('identity_type');
        $model->field('identity_number');
        $model->field('repeated');
        $model->setFieldOrder(['openemis_no', 'student_id', 'date_of_birth', 'nationality', 'identity_type', 'identity_number', 'gender_id', 'repeated', 'institution_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $model = $this->_table;
        $select = [
            $model->aliasField('id'),
            $model->aliasField('student_id'),
            $model->aliasField('academic_period_id'),
            $model->aliasField('education_grade_id'),
            $model->Users->aliasField('openemis_no'),
            $model->Users->aliasField('first_name'),
            $model->Users->aliasField('middle_name'),
            $model->Users->aliasField('third_name'),
            $model->Users->aliasField('last_name'),
            $model->Users->aliasField('preferred_name'),
            $model->Users->aliasField('date_of_birth'),
            $model->Users->aliasField('identity_number'),
            $model->Users->Genders->aliasField('name'),
            $model->Users->IdentityTypes->aliasField('name'),
            $model->Users->MainNationalities->aliasField('name'),
            $model->Institutions->aliasField('code'),
            $model->Institutions->aliasField('name')
        ];
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
        if ($selectedExamination == -1) {
            $where[$model->aliasField('student_id')] = '-1';
        } else {
            $Examinations = TableRegistry::get('Examination.Examinations');
            $ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
            $examination = $Examinations->find()->where([$Examinations->aliasField('id') => $selectedExamination])->first();

            $where[$model->aliasField('education_grade_id')] = $examination->education_grade_id;
            $where[] = $ExaminationCentreStudents->aliasField('id IS NULL');
            $query
                ->leftJoin(
                    [$ExaminationCentreStudents->alias() => $ExaminationCentreStudents->table()],
                    [
                        $ExaminationCentreStudents->aliasField('student_id = ') . $model->aliasField('student_id'),
                        $ExaminationCentreStudents->aliasField('academic_period_id = ') . $model->aliasField('academic_period_id'),
                        $ExaminationCentreStudents->aliasField('examination_id') => $selectedExamination
                    ]
                );
        }
        // End

        $extra['auto_order'] = false;
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $sortList = ['Users.openemis_no', 'Users.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $search = $model->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $model->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }

        $currentStatus = $model->StudentStatuses->getIdByCode('CURRENT');
        $where[$model->aliasField('student_status_id')] = $currentStatus;

        $query
            ->select($select)
            ->contain(['AcademicPeriods', 'Institutions', 'Users.Genders', 'Users.MainNationalities', 'Users.IdentityTypes'], true)
            ->where($where)
            ->group([
                $model->aliasField('student_id'),
                $model->aliasField('academic_period_id'),
            ])
            ->order([$model->Institutions->aliasField('name') => 'asc']);
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'student_id';
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query
            ->contain([
                'Users.SpecialNeeds.SpecialNeedsTypes', 
                'Users.Genders', 
                'Users.Nationalities.NationalitiesLookUp', 
                'Users.IdentityTypes'
            ])
            ->matching('AcademicPeriods')
            ->matching('EducationGrades')
            ->matching('Institutions');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
        $this->setupFields($entity, $extra);

        if ($entity->user->has('identity_type') && !empty($entity->user->identity_type)) {
            $this->identityType = $entity->user->identity_type->name;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) 
    {
        if ($this->_table->action == 'view') {
            if ($field == 'identity_number') {
                if ($this->identityType) {
                    return __($this->identityType);
                } else {
                    return __(TableRegistry::get('FieldOption.IdentityTypes')->find()->find('DefaultIdentityType')->first()->name);
                }
            } 
        }
    }

    public function onGetRepeated(Event $event, Entity $entity)
    {
        return $this->_table->ExaminationCentreStudents->onGetRepeated($event, $entity);
    }

    public function onGetNationality(Event $event, Entity $entity)
    {   
        if ($this->_table->action == 'index') {
            if (!empty($entity)) {
                if ($entity->user->has('main_nationality') && !empty($entity->user->main_nationality)) {
                    return $entity->user->main_nationality->name;
                }
            }
        }
    }

    public function onGetIdentityType(Event $event, Entity $entity)
    {   
        if ($this->_table->action == 'index') {
            if (!empty($entity)) {
                if ($entity->user->has('identity_type') && !empty($entity->user->identity_type)) {
                    return $entity->user->identity_type->name;
                }
            }
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        if (!empty($entity)) {
            if ($entity->user->has('identity_number') && !empty($entity->user->identity_number)) {
                return $entity->user->identity_number;
            }
        }
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

    public function onGetExaminationId(Event $event, Entity $entity) {
        $value = '';
        $model = $this->_table;
        $examinationId = $model->request->query('examination_id');

        if (!is_null($examinationId)) {
            $Examinations = TableRegistry::get('Examination.Examinations');
            $examination = $Examinations->find()->where([$Examinations->aliasField('id') => $examinationId])->first();
            $value = $examination->name;
        }

        return $value;
    }

    public function onGetInstitutionId(Event $event, Entity $entity) {
        return $entity->institution->code_name;
    }

    public function onGetContactPerson(Event $event, Entity $entity) {
        return $entity->institution->contact_person;
    }

    public function onGetTelephone(Event $event, Entity $entity) {
        return $entity->institution->telephone;
    }

    public function onGetFax(Event $event, Entity $entity) {
        return $entity->institution->fax;
    }

    public function onGetEmail(Event $event, Entity $entity) {
        return $entity->institution->email;
    }

    public function onGetSpecialNeeds(Event $event, Entity $entity) {
        $specialNeeds = $this->extractSpecialNeeds($entity);

        return implode(", ", $specialNeeds);
    }

    public function getExaminationOptions($selectedAcademicPeriod) {
        $Examinations = TableRegistry::get('Examination.Examinations');
        $examinationOptions = $Examinations
            ->find('list')
            ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }

    public function setupFields(Entity $entity, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('student_status_id', ['visible' => false]);
        $model->field('education_grade_id', ['visible' => false]);
        $model->field('start_date', ['visible' => false]);
        $model->field('start_year', ['visible' => false]);
        $model->field('end_date', ['visible' => false]);
        $model->field('end_year', ['visible' => false]);
        $model->field('end_year', ['visible' => false]);
        $model->field('examination_id');
        $model->field('openemis_no', ['entity' => $entity]);
        $model->field('date_of_birth', ['type' => 'date', 'entity' => $entity]);
        $model->field('gender_id', ['entity' => $entity]);
        $model->field('contact_person');
        $model->field('telephone');
        $model->field('fax');
        $model->field('email');
        $model->field('special_needs', ['type' => 'string', 'entity' => $entity]);

        $model->field('identity_number');
        $model->field('repeated');

        $model->field('nationalities', [
            'type' => 'element',
            'element' => 'nationalities',
            'visible' => ['view'=>true],
            'data' => $entity->user->nationalities
        ]);

        $model->setFieldOrder(['openemis_no', 'academic_period_id', 'examination_id', 'student_id', 'date_of_birth', 'nationalities', 'identity_number', 'gender_id', 'institution_id', 'repeated', 'contact_person', 'telephone', 'fax', 'email', 'special_needs']);
    }

    public function extractSpecialNeeds(Entity $entity) {
        $specialNeeds = [];
        if ($entity->has('user')) {
            foreach ($entity->user->special_needs as $key => $obj) {
                $specialNeeds[] = $obj->special_needs_type->name;
            }
        }

        return $specialNeeds;
    }
}
