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
	}

	// public function beforeAction(Event $event) {
	// 	pr($this->fields);die;
	// }

	// Search component

}

?>