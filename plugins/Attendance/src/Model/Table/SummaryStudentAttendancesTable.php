<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class SummaryStudentAttendancesTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('summary_student_attendances');
        parent::initialize($config);
    }

   
}
