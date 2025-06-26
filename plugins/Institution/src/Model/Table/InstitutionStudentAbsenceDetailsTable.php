<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionStudentAbsenceDetailsTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_student_absence_details');
        parent::initialize($config);
    }
}
