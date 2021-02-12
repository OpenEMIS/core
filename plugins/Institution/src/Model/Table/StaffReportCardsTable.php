<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class StaffReportCardsTable extends ControllerActionTable
{
     // for status
     CONST NEW_REPORT = 1;
     CONST IN_PROGRESS = 2;
     CONST GENERATED = 3;
     CONST PUBLISHED = 4;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('StaffTemplates', ['className' => 'ProfileTemplate.StaffTemplates', 'foreignKey' => 'staff_profile_template_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staffs', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

        $this->addBehavior('CompositeKey');
    }
}
