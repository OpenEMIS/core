<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Log\Log;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;

class EducationGrade extends Entity
{
	protected $_virtual = ['programme_name', 'programme_grade_name', 'programme_order', 'code_name'];

	protected function _getProgrammeGradeName() {
		$name = '';
		if ($this->has('education_programme')) {
			$name = $this->education_programme->name . ' - ' . $this->name;
		} else {
			$table = TableRegistry::get('Education.EducationProgrammes');
			$id = $this->education_programme_id;
			try {
				$name = $table->get($id)->name . ' - ' . $this->name;
			} catch (InvalidPrimaryKeyException $ex) {
				Log::write('error', __METHOD__ . ': ' . $table->alias() . ' primary key not found (' . $id . ')');
				$name = $this->name;
			}
		}
		return $name;
	}

	protected function _getProgrammeName() {
		$name = '';
		if ($this->has('education_programme')) {
			$name = $this->education_programme->name;
		} else {
			$table = TableRegistry::get('Education.EducationProgrammes');
			$id = $this->education_programme_id;
			$name = $table->get($id)->name;
		}
		return $name;
	}

	protected function _getProgrammeOrder() {
		$name = '';
		if ($this->has('education_programme')) {
			$name = $this->education_programme->order;
		} else {
			$table = TableRegistry::get('Education.EducationProgrammes');
			$id = $this->education_programme_id;
			$name = $table->get($id)->order . ' - ' . $this->name;			
		}
		return $name;
	}

	protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
