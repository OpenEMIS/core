<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;


class InstitutionAssociationStaffTable extends ControllerActionTable
{
    private $InstitutionAssociationStudent;
    public function initialize(array $config)
    {
        $this->table('institution_associations');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('add', false);
             
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
       if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('total_students', []);
        $this->fields['code']['visible'] = false;
        $this->setFieldOrder(['academic_period_id','name','institution_id','total_male_students','total_female_students','total_students']);
    }
  
    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Associations');
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        if (!isset($this->InstitutionAssociationStudent)) {
            $this->InstitutionAssociationStudent = TableRegistry::get('Student.InstitutionAssociationStudent');
        }
        $count = $this->InstitutionAssociationStudent->getMaleCountByAssociations($entity->id) + $this->InstitutionAssociationStudent->getFemaleCountByAssociations($entity->id);
        return $count.' ';
    }

}
