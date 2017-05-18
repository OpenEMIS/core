<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ContactOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->hasMany('ContactTypes', ['className' => 'User.ContactTypes']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

    public function getIdByCode($code) {
        $entity = $this->find()
        ->where([$this->aliasField('code') => $code])
        ->first();

        if ($entity) {
            return $entity->id;
        } else {
            return '';
        }
    }

    public function findCodeList() {
        return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
    }
}
