<?php

namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Http\Session;
use Cake\Http\ServerRequest;

class SummaryStudentAssessmentsTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('summary_student_assessments');
        parent::initialize($config);
    }

}
