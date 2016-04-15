<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;

class AssessmentPeriod extends Entity
{
	protected $_virtual = ['editable'];

    protected function _getEditable() {
		$today = date('Y-m-d');
		$dateEnabled = $this->date_enabled;
		$dateDisabled = $this->date_disabled;
		return ($today >= $dateEnabled->format('Y-m-d') && $today <= $dateDisabled->format('Y-m-d')) ? 1 : 0;
	}
}
