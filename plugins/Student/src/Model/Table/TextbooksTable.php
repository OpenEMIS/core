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
        $this->belongsTo('Users',               ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->toggle('view', false);
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
        $this->field('institution_id', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'code', 'textbook_id', 'textbook_status_id', 'textbook_condition_id', 'education_subject_id'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) 
    {
        $this->fields['textbook_id']['sort'] = ['field' => 'MainTextbooks.title'];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');
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

    
}
