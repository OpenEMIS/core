<?php
namespace Student\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentFeesTable extends AppTable {
	public $currency;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionFees', ['className' => 'Institution.InstitutionFees']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		$ConfigItems = TableRegistry::get('ConfigItems');
    	$this->currency = $ConfigItems->value('currency');

    	$this->ControllerAction->field('amount', 		['type'=>'float', 'visible' => true]);
    	$this->ControllerAction->field('payment_date', 	['visible' => ['index'=>true, 'view'=>true]]);
    	$this->ControllerAction->field('comments', 		['visible' => ['index'=>true, 'view'=>true]]);

    	$this->ControllerAction->field('student_id', 			['visible' => false]);
    	$this->ControllerAction->field('institution_fee_id', 	['visible' => false]);

    	// pr($this->fields);die;
	}

	public function onGetAmount(Event $event, Entity $entity) {
		// pr($entity->amount);die;
		return $this->currency.' '.number_format($entity->amount, 2);
	}


}
