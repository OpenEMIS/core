<?php
namespace StaffAppraisal\Model\Entity;

use Cake\ORM\Entity;

class AppraisalPeriod extends Entity
{

    protected $_virtual = ['period_form_name'];

    protected function _getPeriodFormName()
    {
        $academicPeriodName = $this->offsetExists('academic_period') ? $this->academic_period->name : '';
        $appraisalForm = $this->offsetExists('appraisal_form') ? $this->appraisal_form->name : '';
        return $academicPeriodName . ' - ' . $appraisalForm;
    }
}
