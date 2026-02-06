<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use Cake\Collection\Collection;

class DepartmentStaffTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('department_staff');

        $this->belongsTo('Departments', [
            'className' => 'Institution.InstitutionDepartments',
            'foreignKey' => 'institution_department_id'
            ]);
        $this->belongsTo('Staff', [
            'className' => 'Institution.Staff',
            'foreignKey' => 'institution_staff_id']);

    }

}
