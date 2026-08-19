<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
/**
 * Class responsible for handling archived student report card data.
 *
 * This class manages archived records and their associations.
 *
 * @ticket POCOR-8898
 */

class InstitutionStudentsReportCardsArchivedTable extends ControllerActionTable
{
    //Report Card Status Events
    const NEW_REPORT   = 1;
    const IN_PROGRESS  = 2;
    const GENERATED    = 3;
    const PUBLISHED    = 4;
    const ERROR        = -1;

    // Report Card Process Events
    const NEW_PROCESS  = 1;
    const RUNNING      = 2;
    const COMPLETED    = 3;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->hasMany('StudentsReportCardsComments', [
            'className' => 'Institution.InstitutionStudentsReportCardsComments',
            'foreignKey' => ['report_card_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['index', 'add']
        ]);
    }
}
