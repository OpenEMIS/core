<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionCommitteeTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_type_id']);
		$this->addBehavior('FieldOption.FieldOption');
	}

	public function getAvailableCommitteeTypes($list = true, $order='DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
                        $this->aliasField('editable') => 1,
                        $this->aliasField('visible') . ' >' => 0,
                    ])
                    ->order($this->aliasField('id') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    }
}
