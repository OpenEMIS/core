<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class TransitionTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_students');
		parent::initialize($config);
	}
}
