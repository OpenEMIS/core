<?php
use Migrations\AbstractMigration;

class POCOR6800 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6800_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6800_security_functions` SELECT * FROM `security_functions`');

        /*fetching data*/ 
        $getData = $this->fetchAll("SELECT `order`, `parent_id` FROM security_functions WHERE `module` = 'Institutions' AND `controller` = 'Institutions' AND `category` = 'Report Cards'");
        $orderId = $getData[0]['order'];
        $parentId = $getData[0]['parent_id'];
        /** inserting record */
        $data = [
            [   
                'name' => 'All Comments',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Report Cards',
                'parent_id' => $parentId,
                '_view' => 'ReportCardComments.index|ReportCardComments.view|Comments.index|Comments.view',
                '_edit' => 'Comments.edit',
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => NULL,
                'order' => $orderId - 1,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('security_functions', $data);
    }

    /** rollback */ 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6800_security_functions` TO `security_functions`');
    }
}
