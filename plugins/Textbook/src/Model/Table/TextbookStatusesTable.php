<?php
namespace Textbook\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class TextbookStatusesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function findCodeList() {
		return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
	}

	public function getIdByCode($code) {
		$entity = $this->find()
			->where([$this->aliasField('code') => $code])
			->first();
		return $entity->id;
	}

	public function getSelectOptions()
    {
        return  $this
                ->find('list')
                ->toArray();
    }
}
