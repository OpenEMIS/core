<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;

class UpdateIndexesShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionStudentIndexes');
        $this->loadModel('Institution.StudentIndexesCriterias');
        $this->loadModel('Indexes.IndexesCriterias');
        $this->loadModel('Indexes.Indexes');
        $this->loadModel('Institution.Students');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
    }

    public function main()
    {
        $institutionId = !empty($this->args[0]) ? $this->args[0] : 0;
        $userId = !empty($this->args[1]) ? $this->args[1] : 0;
        $indexId = !empty($this->args[2]) ? $this->args[2] : 0;
        $academicPeriodId = !empty($this->args[3]) ? $this->args[3] : 0;

        // $indexesCriteriaData = $this->Indexes->getCriteriasData(); // all the criteria in the indexesTable
        $indexesCriteriaData = $this->IndexesCriterias->getCriteriaKey($indexId);


        if (!empty($indexesCriteriaData)) {
            foreach ($indexesCriteriaData as $key => $obj) {
                $criteriaData = $this->Indexes->getCriteriasDetails($key);
                $this->Indexes->autoUpdateIndexes($key, $criteriaData['model'], $institutionId, $userId, $academicPeriodId);
            }

            // update the generated_by and generated_on in indexes table
            $today = Time::now();
            $this->Indexes->query()
                ->update()
                ->set([
                    'generated_by' => $userId,
                    'generated_on' => $today
                ])
                ->execute();
        }
    }
}
