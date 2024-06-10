<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use User\Model\Table\ContactsTable as BaseTable;
use App\Model\Table\ControllerActionTable;

class ManualsTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		parent::initialize($config);
	}
}
