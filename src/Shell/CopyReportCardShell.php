<?php

namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;
class PerformanceAssessmentShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Report Card Copy Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Report Card Copy Shell');
    }
    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;

        $ReportCard = TableRegistry::get('ReportCard.ReportCards');
        $count = $ReportCard->find()->where([$ReportCard->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no assessment created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
        }

        return $canCopy;
    }
    // private function copyProcess($copyFrom, $copyTo)
    // {
    //     $ReportCard = TableRegistry::get('ReportCard.ReportCards');
    //     $assessm = $AssessmentTable->find()->where([$AssessmentTable->aliasField('academic_period_id') => $copyFrom])->toArray();
    // }
}