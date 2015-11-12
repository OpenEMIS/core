<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class AuditTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->hasMany('Identities', 		['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Nationalities', 	['className' => 'User.Nationalities',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('SpecialNeeds', 		['className' => 'User.SpecialNeeds',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Contacts', 			['className' => 'User.Contacts',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Attachments', 		['className' => 'User.Attachments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('BankAccounts', 		['className' => 'User.BankAccounts',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Comments', 			['className' => 'User.Comments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Languages', 		['className' => 'User.UserLanguages',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Awards', 			['className' => 'User.Awards',			'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->addBehavior('Excel', ['excludes' => ['username', 'address', 'postal_code', 
			'address_area_id', 'birthplace_area_id', 'gender_id', 'date_of_birth', 'date_of_death', 'super_admin', 
			'photo_name', 'photo_content',  'photo_name', 'is_student', 'is_staff', 'is_guardian'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onExcelGetStatus(Event $event, Entity $entity) {
		if ($entity->status == 1) {
			return __('Active');
		} else {
			return __('Inactive');
		}
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}
}
