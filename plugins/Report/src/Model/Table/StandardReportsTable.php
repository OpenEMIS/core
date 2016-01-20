<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StandardReportsTable extends AppTable  {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function beforeAction(Event $event) {
	}
}