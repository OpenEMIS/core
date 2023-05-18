<?php
use Migrations\AbstractMigration;

class POCOR7320 extends AbstractMigration
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
        // $data = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` WHERE `name` = 'Merge and Download PDF' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Report Cards' ");
        $data = $this->fetchRow("SELECT `order`,`parent_id` FROM `security_functions` ORDER BY `order` DESC LIMIT 1");

        $this->insert('security_functions', [
            'name' => 'Merge and Download PDF',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Report Cards',
            'parent_id' => $data[1],
            '_view' => '',
            '_execute' => 'ReportCardStatuses.mergeAnddownloadAllPdf',            
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

    }

    public function down()
    {
        $this->execute('DELETE FROM security_functions WHERE name ="Merge and Download PDF"');

    }
}
