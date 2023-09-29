<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Network\Request;

class StudentAttendancePerDayPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }
}