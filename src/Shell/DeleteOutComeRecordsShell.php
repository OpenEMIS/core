<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;

class DeleteOutComeRecordsShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Delete OutCome Shell');
        $student_id = $this->args[0];
        $outcome_period_id = $this->args[1];
        $education_grade_id = $this->args[2];
        $education_subject_id = $this->args[3];
        $institution_id = $this->args[4];
        $academic_period_id = $this->args[5];
        $outcome_criteria_id = $this->args[6];
        $outcome_template_id = $this->args[7];

        $canCopy = $this->deleteRecords($student_id, $outcome_period_id, $education_grade_id, $education_subject_id, $institution_id, $academic_period_id, $outcome_criteria_id, $outcome_template_id);
        $this->out('Start Delete OutCome Shell');
    }

    private function deleteRecords($student_id, $outcome_period_id, $education_grade_id, $education_subject_id, $institution_id, $academic_period_id, $outcome_criteria_id, $outcome_template_id)
    {
        $InstitutionOutcomeResults = TableRegistry::get('Institution.InstitutionOutcomeResults');
        $InstitutionOutcomeResults->deleteAll([
            'student_id' => $student_id,
            'outcome_period_id' => $outcome_period_id,
            'education_grade_id' => $education_grade_id,
            'education_subject_id' => $education_subject_id,
            'institution_id' => $institution_id,
            'academic_period_id' => $academic_period_id,
            'outcome_criteria_id' => $outcome_criteria_id,
            'outcome_template_id' => $outcome_template_id
            ]);

        return true;
    }
}
