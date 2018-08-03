<?php
namespace ReportCard\Model\Table;

use App\Model\Table\AppTable;

class ReportCardEmailProcessesTable extends AppTable
{
    const SENDING = 1;
    const SENT = 2;
    const ERROR = -1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
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
