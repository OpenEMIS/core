<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class AssessmentGradingTypesTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		// $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);

		$this->addBehavior('Reorder');
	}

	public function beforeAction(Event $event) {
	}
}
