<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
//use User\Model\Table\ContactsTable as BaseTable;

class ManualsTable extends AppTable
{
	public function initialize(array $config): void
	{
		parent::initialize($config);
	}
}
