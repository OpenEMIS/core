<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;


class DutiesTable extends ControllerActionTable
{

    public function initialize(array $config)
    {
        $this->table('institution_staff_duties');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('StaffDuties', ['className' => 'Institution.StaffDuties', 'foreignKey' => 'staff_duties_id']);
        $this->toggle('view', true);
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
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } else if ($field == 'staff_duties_id') {
            return __('Duty Type');
        } else if ($field == 'comment') {
            return __('Comment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['academic_period_id','institution_id', 'staff_duties_id',  'comment']);
    }
  
    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Duties');
    }

}
