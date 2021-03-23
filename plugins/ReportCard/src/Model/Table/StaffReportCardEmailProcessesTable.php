<?php
namespace ReportCard\Model\Table;

use App\Model\Table\AppTable;

class StaffReportCardEmailProcessesTable extends AppTable
{
    const SENDING = 1;
    const SENT = 2;
    const ERROR = -1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('StaffTemplates', ['className' => 'ProfileTemplate.StaffTemplates', 'foreignKey' => 'staff_profile_template_id']);
        $this->belongsTo('Staffs', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
    }

    public function getEmailStatus()
    {
        $statuses = [
            self::SENDING => __('Sending'),
            self::SENT => __('Sent'),
            self::ERROR => __('Error')
        ];

        return $statuses;
    }
}
