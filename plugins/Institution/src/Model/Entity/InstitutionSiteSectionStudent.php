<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSiteSectionStudent extends Entity
{
	protected $_virtual = ['name', 'openemis_no', 'gender', 'date_of_birth'];
	
    protected function _getName() {
        $value = '';
        if ($this->has('user')) {
            $value = $this->user->name;
        } else {
            $table = TableRegistry::get('User.Users');
            $id = $this->security_user_id;
            $value = $table->get($id)->name;            
        }
    	return $value;
	}

    protected function _getOpenemisNo() {
        $value = '';
        if ($this->has('user')) {
            $value = $this->user->openemis_no;
        } else {
            $table = TableRegistry::get('User.Users');
            $id = $this->security_user_id;
            $value = $table->get($id)->openemis_no;            
        }
    	return $value;
	}

    protected function _getGender() {
        $value = '';
        if ($this->has('user')) {
            $value = $this->user->gender->name;
        } else {
            $table = TableRegistry::get('User.Users');
            $id = $this->security_user_id;

            $data = $table->find()
            				->contain(['Genders'])
            				->where([$table->aliasField('id')=>$id])
            				->first()
            				;
            // pr($data);die;
            $value = $data->gender->name;            
        }
    	return $value;
	}

    protected function _getDateOfBirth() {
        $value = '';
        if ($this->has('user')) {
            $value = $this->user->date_of_birth;
        } else {
            $table = TableRegistry::get('User.Users');
            $id = $this->security_user_id;
            $value = $table->get($id)->date_of_birth;            
        }
    	return $value;
	}
    
}
