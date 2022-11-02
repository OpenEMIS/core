<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\Log;

class IncomeTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('income_types');
        parent::initialize($config);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
        $this->addBehavior('FieldOption.FieldOption');
    }
}