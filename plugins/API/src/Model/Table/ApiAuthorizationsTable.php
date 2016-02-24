<?php
namespace API\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Exception;
use DateTime;

class ApiAuthorizationsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->addBehavior('API.API');
	}
}
