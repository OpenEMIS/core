<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class StaffAppraisalTypesTable extends ControllerActionTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->hasMany('Staff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_status_id']);
	}

	public function getIdByCode($code)
    {
		$entity = $this->find()
			->where([$this->aliasField('code') => $code])
			->first();
		return $entity->id;
	}
}
