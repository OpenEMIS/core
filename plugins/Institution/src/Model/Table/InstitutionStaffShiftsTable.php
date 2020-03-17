<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

use Institution\Model\Table\Institutions;

class InstitutionStaffShiftsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index'],
            'Staff' => ['index', 'add']
        ]);
        
        $this->setDeleteStrategy('restrict');
    }

}
