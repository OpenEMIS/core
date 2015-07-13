<?php
namespace Localization\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;

class TranslationsTable extends AppTable {

	// Initialisation
	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);
		$this->belongsTo('ModifiedUser', ['className' => 'Security.Users', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);
	}

	// Search component

}

?>