<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleTimetableCustomizesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_timetable_customizes');
        parent::initialize($config);        
        $this->addBehavior('Schedule.Schedule');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index', 'view', 'edit','add'],
        ]);
    }   
    
    public function findDeleteTimetableCustomizeData(Query $query, array $options){
        $timetable_id = $options['institution_schedule_timetable_id'];
        return $this->deleteAll(['institution_schedule_timetable_id' => $timetable_id]);
    }
}
