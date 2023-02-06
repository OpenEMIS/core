<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;

class InstitutionPosition extends Entity
{
	protected $_virtual = ['name'];
	
	protected function _getName() {
		/* POCOR 7216*/
		// $name = $this->position_no;
		$name = explode('-',$this->position_no);
		$name = !empty($name) ? $name[0] : '';
		// END POCOR 7216
		if (strlen($name) > 0) {
			$name .= ' - ';
		}
		if ($this->has('staff_position_title')) {
			$name .= $this->staff_position_title->name;
		} else {
			$table = TableRegistry::get('Institution.StaffPositionTitles');
			$id = $this->staff_position_title_id;
			try {
				$name .= $table->get($id)->name;
			} catch (InvalidPrimaryKeyException $ex) {
				Log::write('error', __METHOD__ . ': ' . $table->alias() . ' primary key not found (' . $id . ')');
				$name = $this->name;
			}
		}
		return $name;
	}
}
