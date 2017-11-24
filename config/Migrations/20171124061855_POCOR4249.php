<?php
use Migrations\AbstractMigration;

class POCOR4249 extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE workflow_models SET model = 'Quality.VisitRequests' WHERE model = 'Institution.VisitRequests'");
        $this->execute("UPDATE workflow_models SET model = 'Cases.InstitutionCases' WHERE model = 'Institution.InstitutionCases'");
    }

    public function down()
    {
        $this->execute("UPDATE workflow_models SET model = 'Institution.VisitRequests' WHERE model = 'Quality.VisitRequests'");
        $this->execute("UPDATE workflow_models SET model = 'Institution.InstitutionCases' WHERE model = 'Cases.InstitutionCases'");
    }
}
