<?php
use Migrations\AbstractMigration;

class POCOR6838 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_6838_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6838_security_functions` SELECT * FROM `security_functions`');

        /*fetching data*/ 
        $this->execute("UPDATE security_functions SET `name` = 'Generate' WHERE `name` = 'Generate/Download' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Report Cards' ");

        $sql = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Download Excel", "Institutions", "Institutions", "Report Cards", "1000", NULL, NULL, NULL, NULL, "ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll", "120", "1", NULL, "2", NOW() , "1", NOW() )';
        $this->execute($sql);

        $sql1 = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Download PDF", "Institutions", "Institutions", "Report Cards", "1000", NULL, NULL, NULL, NULL, "ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll", "120", "1", NULL, "2",  NOW(), "1",  NOW() )';
        $this->execute($sql1);

        $sql2 = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Generate All", "Institutions", "Institutions", "Report Cards", "1000", NULL, NULL, NULL, NULL, "ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll", "120", "1", NULL, "2", NOW(), "1", NOW() ) ';
        $this->execute($sql2);

        $sql3 = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Download All Excel", "Institutions", "Institutions", "Report Cards", "1000", NULL, NULL, NULL, NULL, "ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll", "120", "1", NULL, "2", NOW(), "1", NOW() ) ';
        $this->execute($sql3);

        $sql4 = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Download All PDF", "Institutions", "Institutions", "Report Cards", "1000", NULL, NULL, NULL, NULL, "ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll|InstitutionStudentsReportCards.downloadAllPdf||ReportCardStatuses.downloadAllPdf", "120", "1", NULL, "2",  NOW() , "1",  NOW() ) ';
        $this->execute($sql4);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6838_security_functions` TO `security_functions`');
    }
}
