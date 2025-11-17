<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class InstitutionProgramGradeSubjectsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_program_grade_subjects');
        parent::initialize($config);
    }
}
