<?php

use Phinx\Migration\AbstractMigration;

class POCOR4466 extends AbstractMigration
{
    public function up()
    {
        $InstitutionCompetencyResults = $this->table('institution_competency_results');
        $InstitutionCompetencyResults
            ->addColumn('comments', 'text', [
                'after' => 'competency_grading_option_id',
                'null' => true
            ])
            ->save();
    }

    public function down()
    {
        $InstitutionCompetencyResults = $this->table('institution_competency_results');
        $InstitutionCompetencyResults
            ->removeColumn('comments')
            ->save();
    }
}
