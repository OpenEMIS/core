<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;
use DateTimeInterface;

class AssessmentPeriod extends Entity
{
	protected $_virtual = ['editable'];

    protected function _getEditable() {
		$today = date('Y-m-d');
		$dateEnabled = $this->date_enabled;
		$dateDisabled = $this->date_disabled;
		if ($dateEnabled instanceof DateTimeInterface && $dateDisabled instanceof DateTimeInterface) {
			return ($today >= $dateEnabled->format('Y-m-d') && $today <= $dateDisabled->format('Y-m-d')) ? 1 : 0;
		} else {
			return 1;
		}
	}
}
