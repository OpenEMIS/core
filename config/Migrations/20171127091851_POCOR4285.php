<?php
use Migrations\AbstractMigration;

class POCOR4285 extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE workflow_models SET model = 'Cases.InstitutionCases' WHERE model = 'Institution.InstitutionCases'");
    }

    public function down()
    {
        $this->execute("UPDATE workflow_models SET model = 'Institution.InstitutionCases' WHERE model = 'Cases.InstitutionCases'");
    }
}
