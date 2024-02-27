<?php

namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Utility\Security;
use Cake\Datasource\ConnectionManager;

use App\Model\Table\ControllerActionTable;

//POCOR-6673
class StudentCurricularsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_curricular_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
        $this->belongsTo('CurricularPositions', ['className' => 'FieldOption.CurricularPositions']);
        $this->toggle('add', true);
        $this->toggle('search', true);
        $this->toggle('edit', false);
        $this->toggle('view', true);
        $this->toggle('remove', false);
    }
    //POCOR-8056
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'StudentCurriculars';
        $userType = 'StudentUser';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }
    //POCOR-8056

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-8028 removed academic period
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $userData = $this->Session->read();
        if ($sId != null) {
            $sId_id = $sId;
        } else {
            $sId_id = $userData['Auth']['User']['id'];
        }
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricular_types = TableRegistry::get('curricular_types');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        if ($this->controller->name == 'Profiles') {
            $where = [$this->aliasField('student_id') => $sId_id];
        } else {
            $where = [$this->aliasField('student_id') => $sId_id,
                $InstitutionCurriculars->aliasField('institution_id') => $institutionId];
        }
        $query
            ->select([
                $this->aliasField('id'),
                'start_date' => $this->aliasField('start_date'),
                'end_date' => $this->aliasField('end_date'),
                'type' => $curricular_types->aliasField('name'),
                'category' => $InstitutionCurriculars->aliasField('category'),
            ])
            // POCOR-8028 query made more strict
            ->InnerJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                [$InstitutionCurriculars->aliasField('id') . ' = ' . $this->aliasField('institution_curricular_id')
                ])
            ->InnerJoin([$curricular_types->alias() => $curricular_types->table()],
                [$curricular_types->aliasField('id') . ' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])

            ->where($where);

        $this->field('student_id', ['visible' => false]);
        $this->field('student_name', ['visible' => true]);
        $this->field('openemis_no', ['visible' => true]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        // $this->field('type', ['visible' => ['index'=>true,'view' => true,'edit' => false,'add'=>false]]);
        $this->field('curricular_type', ['visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]]);
        $this->field('category', ['visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => false]]);

        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->setFieldOrder([
            'student_name',
            'openemis_no',
            'curricular_category',
            'curricular_type',
            'institution_curricular_id',
            'curricular_position_id',
            'start_date',
            'end_date']);
        if ($this->controller->name == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }

    }

    public function onGetCurricularType(Event $event, Entity $entity)
    {
        if ($entity->type != '') {
            return $entity->type;
        } else {
            $ic_id = $entity->institution_curricular_id;
            $connection = ConnectionManager::get('default');
            $ctype_rec = $connection->query("SELECT institution_curriculars.curricular_type_id,curricular_types.name  FROM institution_curriculars LEFT JOIN curricular_types ON curricular_types.id=institution_curriculars.curricular_type_id WHERE institution_curriculars.id=" . $ic_id);
            $ctype_data = $ctype_rec->fetch();
            return (!empty($ctype_data)) ? $ctype_data[1] : '--';
        }
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $connection = ConnectionManager::get('default');
        $student_rec = $connection->query("SELECT openemis_no FROM security_users WHERE security_users.id=" . $sId);
        $student_data = $student_rec->fetch();
        return (!empty($student_data)) ? $student_data[0] : '--';
    }

    public function onGetStudentName(Event $event, Entity $entity)
    {
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $connection = ConnectionManager::get('default');
        $student_rec = $connection->query("SELECT first_name,last_name FROM security_users WHERE security_users.id=" . $sId);
        $student_data = $student_rec->fetch();
        return (!empty($student_data)) ? $student_data[0] . ' ' . $student_data[1] : '--';
    }


    public function onGetType(Event $event, Entity $entity)
    {
        $connection = ConnectionManager::get('default');
        $results = $connection->query("SELECT name FROM curricular_types WHERE id=" . $entity->institution_curricular->curricular_type_id);
        $curr_type = $results->fetch();
        return (!empty($curr_type)) ? $curr_type[0] : '--';

    }



    public function onGetCurricularCategory(Event $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Curricular') : $entity->category ? __('Co-Curricular') : __('Extracurricular'); //POCOR-7751

    }

    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Curricular') : __('Extracurricular');
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {

        $this->field('category', ['visible' => true]);
        $this->field('openemis_no', ['visible' => true]);
        $this->field('student_id', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->field('category', ['visible' => false]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_type', ['visible' => true]);

        $this->field('curricular_position_id', ['visible' => true]);
        $this->setFieldOrder([
            'student_id',
            'openemis_no',
            'curricular_category',
            'curricular_type',
            'institution_curricular_id',
            'curricular_position_id',
            'start_date',
            'end_date']); //POCOR-7604
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        // $this->field('student_id', ['visible' => false]);
        $InstitutionID = $_SESSION['Institution']['Institutions']['id'];
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $result = $InstitutionCurriculars
            ->find()
            ->select(['id', 'name'])
            ->where(['institution_id' => $InstitutionID])
            ->all();

        $ic_arr = [];
        if (!empty($result)) {
            foreach ($result as $key => $val) {
                $ic_arr[$val->id] = $val->name;
            }
        }

        $curricularPosition = TableRegistry::get('curricular_positions');
        $result1 = $curricularPosition
            ->find()
            ->select(['id', 'name'])
            ->all();

        $cp_arr = [];
        if (!empty($result1)) {
            foreach ($result1 as $key => $val) {
                $cp_arr[$val->id] = $val->name;
            }
        }
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $this->field('institution_curricular_id', ['type' => 'select', 'options' => $ic_arr]);
        $this->field('start_date');
        $this->field('end_date');
        $this->field('curricular_position_id', ['type' => 'select', 'options' => $cp_arr]);
        $this->field('hours');
        $this->field('points');
        $this->field('location');
        $this->field('comments', ['type' => 'text']);
        $this->field('student_id', ['type' => 'hidden', 'value' => $sId]);
        $this->field('id', ['type' => 'hidden', 'value' => Security::hash(time(), 'sha256')]);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $curricularData = $this->InstitutionCurriculars->get($entity->institution_curricular_id);

        $studentData = $this->Users->get($entity->student_id)->gender_id;
        $genderCode = TableRegistry::get('User.Genders')->get($studentData)->code;
        $connection = ConnectionManager::get('default');
        if ($genderCode == "M") {
            $data = empty($curricularData->total_male_students) ? 1 : $curricularData->total_male_students + 1;
            $updateQuery = 'UPDATE institution_curriculars SET total_male_students = ' . $data . ' WHERE id = ' . $entity->institution_curricular_id;
        } else {
            $data = empty($curricularData->total_female_students) ? 1 : $curricularData->total_male_students + 1;
            $updateQuery = 'UPDATE institution_curriculars SET total_female_students = ' . $data . ' WHERE id = ' . $entity->institution_curricular_id;
        }
        $connection->execute($updateQuery);
    }

}
