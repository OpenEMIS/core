<?php 
namespace Examination\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\Behavior;
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
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
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
            'sort' => ['field' => 'Users.first_name']
        ]);
        $model->field('academic_period_id', ['visible' => false]);
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
        $examinationOptions = ['-1' => __('All Examination')] + $examinationOptions;
        $selectedExamination = !is_null($model->request->query('examination_id')) ? $model->request->query('examination_id') : -1;
        $model->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
            $where[$model->aliasField('examination_id')] = $selectedExamination;
        }
        // End

        // Examination Centre
        $examinationCentreOptions = $this->getExaminationCentreOptions($selectedAcademicPeriod, $selectedExamination);
        $examinationCentreOptions = ['-1' => __('All Examination Centre')] + $examinationCentreOptions;
        $selectedExaminationCentre = !is_null($model->request->query('examination_centre_id')) ? $model->request->query('examination_centre_id') : -1;
        $model->controller->set(compact('examinationCentreOptions', 'selectedExaminationCentre'));
        if ($selectedExaminationCentre != -1) {
            $where[$model->aliasField('examination_centre_id')] = $selectedExaminationCentre;
        }
        // End

        // Education Subject
        $subjectOptions = $this->getExaminationCentreSubjectOptions($selectedAcademicPeriod, $selectedExaminationCentre);
        $subjectOptions = ['-1' => __('All Subject')] + $subjectOptions;
        $selectedSubject = !is_null($model->request->query('education_subject_id')) ? $model->request->query('education_subject_id') : -1;
        $model->controller->set(compact('subjectOptions', 'selectedSubject'));
        if ($selectedSubject != -1) {
            $where[$model->aliasField('education_subject_id')] = $selectedSubject;
        }
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

    public function onGetOpenemisNo(Event $event, Entity $entity) {
        $value = '';
        if ($entity->has('_matchingData')) {
            $value = $entity->_matchingData['Users']->openemis_no;
        } else if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }

        return $value;
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

    public function getExaminationOptions($selectedAcademicPeriod) {
        $model = $this->_table;
        $examinationOptions = $model->Examinations
            ->find('list')
            ->where([$model->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }

    public function getExaminationCentreOptions($selectedAcademicPeriod, $selectedExamination) {
        $model = $this->_table;
        $examinationCentreOptions = $model->ExaminationCentres
            ->find('list')
            ->where([
                $model->ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $model->ExaminationCentres->aliasField('examination_id') => $selectedExamination
            ])
            ->toArray();

        return $examinationCentreOptions;
    }

    public function getExaminationCentreSubjectOptions($selectedAcademicPeriod, $selectedExaminationCentre) {
        $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
        $subjectOptions = $ExaminationCentreSubjects
            ->find('list', ['keyField' => 'education_subject.id', 'valueField' => 'education_subject.name'])
            ->contain(['EducationSubjects'])
            ->where([
                $ExaminationCentreSubjects->aliasField('academic_period_id') => $selectedAcademicPeriod,
                $ExaminationCentreSubjects->aliasField('examination_centre_id') => $selectedExaminationCentre
            ])
            ->toArray();

        return $subjectOptions;
    }
}
