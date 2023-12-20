<?php

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR7998 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_7998_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_7998_security_roles` SELECT * FROM `security_roles`');

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.publish|ReportCardStatuses.publishAll|ReportCardStatuses.unpublish|ReportCardStatuses.unpublishAll' 
WHERE `name` = 'Publish/Unpublish' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.mergeAnddownloadAllPdf' 
WHERE `name` = 'Merge and Download PDF' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.generate|ReportCardStatuses.generateAll' 
WHERE `name` = 'Generate All' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.generate' 
WHERE `name` = 'Generate' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.emailPdf|ReportCardStatuses.emailAllPdf' 
WHERE `name` = 'Email/Email All PDF' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'ReportCardStatuses.emailExcel|ReportCardStatuses.emailAllExcel' 
WHERE `name` = 'Email/Email All Excel' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'InstitutionStudentsReportCards.download' 
WHERE `name` = 'Download PDF' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'InstitutionStudentsReportCards.download' 
WHERE `name` = 'Download Excel' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll|InstitutionStudentsReportCards.downloadAllPdf||ReportCardStatuses.downloadAllPdf' 
WHERE `name` = 'Download All PDF' and `category` = 'Report Cards'");

        $this->execute("UPDATE security_functions 
SET `_execute` = 'InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll' 
WHERE `name` = 'Download All Excel' and `category` = 'Report Cards'");

    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `z_7998_security_roles` TO `security_roles`');
    }
}
