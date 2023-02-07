<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;


class InstitutionCurricularStudentsTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_curricular_students');
		parent::initialize($config);
        /*$this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);*/
        
	}
	
}
