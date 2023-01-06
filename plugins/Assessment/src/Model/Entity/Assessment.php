<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;

class Assessment extends Entity
{
	protected $_virtual = ['code_name', 'education_programme_id'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}

    protected function _getEducationProgrammeId() {
    	if (!empty($this->education_grade)) {
	    	return $this->education_grade->education_programme_id;
    	} else {
    		return '';
    	}
	}

}
