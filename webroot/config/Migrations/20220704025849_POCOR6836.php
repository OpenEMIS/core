<?php
use Migrations\AbstractMigration;

class POCOR6836 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6836_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6836_security_functions` SELECT * FROM `security_functions`');

        /*fetching data*/ 
        $getData = $this->fetchAll("SELECT * FROM security_functions WHERE `module` = 'Institutions' AND `controller` = 'Institutions' AND `category` = 'Report Cards' AND `name` = 'Email/Email All'");
        $parentId = $getData[0]['parent_id'];
        $orderId = $getData[0]['order'];
        
        /**update existing record*/
        $this->execute("UPDATE `security_functions` SET `name` = 'Email/Email All PDF', `_execute` = 'ReportCardStatuses.emailPdf|ReportCardStatuses.emailAllPdf' WHERE `module` = 'Institutions' AND `controller` = 'Institutions' AND `category` = 'Report Cards' AND `name` = 'Email/Email All'");

        /**inserting new record*/
        $this->insert('security_functions', [
            'name' => 'Email/Email All Excel',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Report Cards',
            'parent_id' => $parentId,
            '_view' => NULL,
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => 'ReportCardStatuses.emailExcel|ReportCardStatuses.emailAllExcel',
            'order' => $orderId + 1,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        
    }

    /** rollback */ 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6836_security_functions` TO `security_functions`');
    }
}
