<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class TextbooksTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('institution_textbooks');
        parent::initialize($config);

        $this->belongsTo('MainTextbooks',       ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses',    ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions',  ['className' => 'Textbook.TextbookConditions']);
        $this->belongsTo('Institutions',        ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Users',               ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents() {
       $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields) {
        $searchableFields[] = 'textbook_id';
    }

    public function beforeAction()
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('institution_id', ['type' => 'select']);
        $this->field('student_id', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'institution_id', 'code', 'textbook_id', 'education_grade_id', 'education_subject_id', 'textbook_condition_id', 'textbook_status_id'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->fields['textbook_id']['sort'] = ['field' => 'MainTextbooks.title'];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();

        // POCOR-1893 Profile using loginId as studentId
        if ($this->controller->name == 'Profiles') {
            $session = $this->request->session();
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

        $query->where([$this->aliasField('student_id') => $studentId]);

        $searchKey = $this->getSearchKey();
        if (strlen($searchKey)) {
            $query->matching('MainTextbooks'); //to enable search by textbook title
            $extra['OR'] = [
                $this->MainTextbooks->aliasField('title').' LIKE' => '%' . $searchKey . '%',
                $this->MainTextbooks->aliasField('code').' LIKE' => '%' . $searchKey . '%',
            ];
        }

        $sortList = ['code', 'MainTextbooks.title'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onGetTextbookId(Event $event, Entity $entity)
    {
        return $entity->main_textbook->code_title;
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        if (($this->action == 'view') || ($this->action == 'index')) {
            return $entity->academic_period->name;
        }
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }
}
