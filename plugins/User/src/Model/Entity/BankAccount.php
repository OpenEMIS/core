<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class BankAccount extends Entity
{
	protected $_virtual = ['bank_name'];
	
    protected function _getBankName() {
    	$name = '';
    	if ($this->has('bank_branch') && $this->bank_branch->has('bank_id')) {
    		$Banks = TableRegistry::get('FieldOption.Banks'); 
    		$data = $Banks
    			->find()
    			->where([$Banks->aliasField($Banks->primaryKey()) => $this->bank_branch->bank_id])
    			->first()
    			->toArray()
    		;
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}
}
