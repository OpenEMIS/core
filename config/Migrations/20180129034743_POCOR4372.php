<?php
use Migrations\AbstractMigration;

class POCOR4372 extends AbstractMigration
{
    /**
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // rename indexes
        $risks = $this->table('indexes');
        $risks->rename('risks');
        // end

        // rename indexes_criterias
        $riskCriterias = $this->table('indexes_criterias');
        $riskCriterias->rename('risk_criterias');

        $riskCriterias->renameColumn('index_value', 'risk_value');
        $riskCriterias->renameColumn('index_id', 'risk_id');

        $riskCriterias
            ->changeColumn('risk_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment'=>'links to risks.id'
            ])
            ->save();
        //end

        //rename institution_indexes
        $institutionRisks = $this->table('institution_indexes');
        $institutionRisks->rename('institution_risks');

        $institutionRisks->renameColumn('index_id', 'risk_id');
        $institutionRisks
            ->changeColumn('risk_id', 'integer', [
                'limit'=>11,
                'null' =>false,
                'comment'=>'links to risk.id'
            ])
            ->save();
        //end

        //rename institution_student_indexes
        $institutionStudentRisks = $this->table('institution_student_indexes');
        $institutionStudentRisks->rename('institution_student_risks');

        $institutionStudentRisks->renameColumn('index_id', 'risk_id');
        $institutionStudentRisks
            ->changeColumn('risk_id', 'integer', [
                'limit'=>11,
                'null' =>false,
                'comment'=>'links to risks.id'
            ])
            ->save();
        //end

        //rename student_indexes_criterias
        $studentRisksCriterias = $this->table('student_indexes_criterias');
        $studentRisksCriterias->rename('student_risks_criterias');

        $studentRisksCriterias->renameColumn('indexes_criteria_id', 'risk_criteria_id');
        $studentRisksCriterias
            ->changeColumn('risk_criteria_id', 'integer', [
                'limit'=>11,
                'null' => false,
                'comment'=>'links to indexes_criterias.id'
            ])
            ->save();
        //end

        // update permission from indexes to risks
        $sql = "UPDATE `security_functions`
                SET `_view` = 'Risks.index|Risks.view|InstitutionStudentIndexes.index|InstitutionStudentIndexes.view',
                    `_execute` = 'Risks.generate'
                WHERE `id`=1055";

        $this->execute($sql);

        $sql = "UPDATE `security_functions`
                SET `_view` = 'Risks.index|Risks.view',
                    `_edit` = 'Risks.edit',
                    `_add` = 'Risks.add',
                    `_delete` ='Risks.remove'
                WHERE `id`=5066";

        $this->execute($sql);
        // end
    }

    public function down()
    {
        // restore indexes and drop risks
        $risk = $this->table('risks');
        $risk->rename('indexes');
        // end

        // restore indexes_criterias and drop risks_criterias

        $riskCriterias = $this->table('risk_criterias');
        $riskCriterias->rename('indexes_criterias');
        $riskCriterias->renameColumn('risk_value', 'index_value');
        $riskCriterias->renameColumn('risk_id', 'index_id');
        // end

        // restore institution_indexes and drop institution_risks
        $institutionRisks = $this->table('institution_risks');
        $institutionRisks->rename('institution_indexes');
        $institutionRisks->renameColumn('risk_id', 'index_id');
        // end

        // restore institution_student_indexes and drop institution_student_risks
        $institutionStudentRisks = $this->table('institution_student_risks');
        $institutionStudentRisks->rename('institution_student_indexes');
        $institutionStudentRisks->renameColumn('risk_id', 'index_id');
        // end

        // restore student_indexes_criterias and drop student_indexes_criterias
        $studentRisksCriterias = $this->table('student_risks_criterias');
        $studentRisksCriterias->rename('student_indexes_criterias');
        $studentRisksCriterias->renameColumn('risk_criteria_id', 'indexes_criteria_id');
        // end

        // revert permission from indexes to risks
        $sql = "UPDATE `security_functions`
                SET `_view` = 'Indexes.index|Indexes.view|InstitutionStudentIndexes.index|InstitutionStudentIndexes.view',
                    `_execute` = 'Indexes.generate'
                WHERE `id`=1055";

        $this->execute($sql);

        $sql = "UPDATE `security_functions`
                SET `_view` = 'Indexes.index|Indexes.view',
                    `_edit` = 'Indexes.edit',
                    `_add` = 'Indexes.add',
                    `_delete` ='Indexes.remove'
                WHERE `id`=5066";

        $this->execute($sql);
        // end
    }
}
