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
	public function initialize(array $config) {
		parent::initialize($config);

        $model = $this->_table;
        $model->toggle('add', false);
        $model->toggle('edit', false);
        $model->toggle('remove', false);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $model = $this->_table;
        $model->field('openemis_no', ['sort' => true]);
        $model->field('student_id', [
            'type' => 'select',
            'sort' => ['field' => 'Users.first_name']
        ]);
        $model->field('student_status_id', ['visible' => false]);
        $model->field('education_grade_id', ['visible' => false]);
        $model->field('academic_period_id', ['visible' => false]);
        $model->field('start_date', ['visible' => false]);
        $model->field('start_year', ['visible' => false]);
        $model->field('end_date', ['visible' => false]);
        $model->field('end_year', ['visible' => false]);
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
        if ($selectedExamination == -1) {
            $where[$model->aliasField('student_id')] = '-1';
        } else {
            $Examinations = TableRegistry::get('Examination.Examinations');
            $ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
            $examination = $Examinations->find()->where([$Examinations->aliasField('id') => $selectedExamination])->first();

            $where[$model->aliasField('education_grade_id')] = $examination->education_grade_id;
            $where[] = $ExaminationCentreStudents->aliasField('id IS NULL');
            $query
                ->leftJoin(
                    [$ExaminationCentreStudents->alias() => $ExaminationCentreStudents->table()],
                    [
                        $ExaminationCentreStudents->aliasField('student_id = ') . $model->aliasField('student_id'),
                        $ExaminationCentreStudents->aliasField('academic_period_id = ') . $model->aliasField('academic_period_id'),
                        $ExaminationCentreStudents->aliasField('education_grade_id = ') . $model->aliasField('education_grade_id'),
                        $ExaminationCentreStudents->aliasField('examination_id') => $selectedExamination
                    ]
                );
        }
        // End

        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $search = $model->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $model->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }

        $currentStatus = $model->StudentStatuses->getIdByCode('CURRENT');
        $where[$model->aliasField('student_status_id')] = $currentStatus;

        $query
            ->where($where)
            ->group([
                $model->aliasField('student_id'),
                $model->aliasField('academic_period_id'),
                $model->aliasField('education_grade_id')
            ])
            ->order([$model->Institutions->aliasField('name') => 'asc']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query
            ->contain(['Users.SpecialNeeds.SpecialNeedTypes'])
            ->matching('AcademicPeriods')
            ->matching('EducationGrades')
            ->matching('Institutions');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
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
        $model->field('contact_person');
        $model->field('telephone');
        $model->field('fax');
        $model->field('email');
        $model->field('special_needs', ['type' => 'string', 'entity' => $entity]);

        $model->setFieldOrder(['academic_period_id', 'examination_id', 'openemis_no', 'student_id', 'institution_id', 'contact_person', 'telephone', 'fax', 'email', 'special_needs']);
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
