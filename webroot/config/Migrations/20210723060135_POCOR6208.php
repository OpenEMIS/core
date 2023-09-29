<?php
use Migrations\AbstractMigration;

class POCOR6208 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_6208_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6208_security_functions` SELECT * FROM `security_functions`');

        //update delete function for all reports module
        $this->execute("UPDATE security_functions SET _delete = 'Institutions.delete' WHERE module = 'Reports' AND name = 'Institution'");
        $this->execute("UPDATE security_functions SET _delete = 'Students.delete' WHERE module = 'Reports' AND name = 'Students'");
        $this->execute("UPDATE security_functions SET _delete = 'Staff.delete' WHERE module = 'Reports' AND name = 'Staff'");
        $this->execute("UPDATE security_functions SET _delete = 'Surveys.delete' WHERE module = 'Reports' AND name = 'Surveys'");
        $this->execute("UPDATE security_functions SET _delete = 'InstitutionRubrics.delete' WHERE module = 'Reports' AND name = 'Rubrics'");
        $this->execute("UPDATE security_functions SET _delete = 'DataQuality.delete' WHERE module = 'Reports' AND name = 'Data Quality'");
        $this->execute("UPDATE security_functions SET _delete = 'Audits.delete' WHERE module = 'Reports' AND name = 'Audits'");
        $this->execute("UPDATE security_functions SET _delete = NULL WHERE module = 'Reports' AND name = 'Map'");
        $this->execute("UPDATE security_functions SET _delete = 'Examinations.delete' WHERE module = 'Reports' AND name = 'Examinations'");
        $this->execute("UPDATE security_functions SET _delete = 'Textbooks.delete' WHERE module = 'Reports' AND name = 'Textbooks'");
        $this->execute("UPDATE security_functions SET _delete = 'Trainings.delete' WHERE module = 'Reports' AND name = 'Trainings'");
        $this->execute("UPDATE security_functions SET _delete = 'CustomReports.delete' WHERE module = 'Reports' AND name = 'Custom'");
        $this->execute("UPDATE security_functions SET _delete = 'Workflows.delete' WHERE module = 'Reports' AND name = 'Workflows'");
        $this->execute("UPDATE security_functions SET _delete = 'Scholarships.delete' WHERE module = 'Reports' AND name = 'Scholarships'");
        $this->execute("UPDATE security_functions SET _delete = 'Directory.delete' WHERE module = 'Reports' AND name = 'Directory'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6208_security_functions` TO `security_functions`');
    }
}
