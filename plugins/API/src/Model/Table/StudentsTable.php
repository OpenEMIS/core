<?php
namespace API\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Exception;
use DateTime;

class StudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		$this->hasMany('Identities', ['className' => 'User.Identities']);

		$this->addBehavior('API.API');
	}
}
