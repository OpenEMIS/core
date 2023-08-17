<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use User\Model\Table\ContactsTable as BaseTable;

class ManualsTable extends BaseTable
{
	public function initialize(array $config): void
	{
		parent::initialize($config);
	}
}
