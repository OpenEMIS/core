<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LanguagesTable extends AppTable {
	public $CAVersion = '4.0';
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('languages');
		parent::initialize($config);
		$this->hasMany('UserLanguages', ['className' => 'UserLanguages', 'foreignKey' => 'language_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
