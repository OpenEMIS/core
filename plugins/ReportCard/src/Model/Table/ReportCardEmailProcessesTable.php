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

    public function getEmailErrorMsg($reportCardId, $institutionClassId, $studentId) {
        return $this
            ->find()
            ->where([
                $this->aliasField('report_card_id') => $reportCardId,
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('student_id') => $studentId
            ])
            ->select([
                $this->aliasField('error_message')
            ])
            ->first();
    }
}
