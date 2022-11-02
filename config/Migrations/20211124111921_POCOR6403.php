<?php
use Migrations\AbstractMigration;

class POCOR6403 extends AbstractMigration
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
        //creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6403_security_functions`');
        $this->execute('CREATE TABLE `zz_6403_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6403_security_functions` SELECT * FROM `security_functions`');

        //creating identical of reports table
        $this->execute('CREATE TABLE `institution_statistics` LIKE `reports`');

        //adding localizations
        $this->insert('locale_contents', [
                'en' => 'Statistics',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
        ]);

        //getting parent_id value
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'General'");
        
        $parentId = $row['id'];

        //getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");
        //inserting record
        $this->insert('security_functions', [
            'name' => 'Statistics',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'General',
            'parent_id' => $parentId,
            '_view' => 'InstitutionStatistics.index|InstitutionStatistics.view',
            '_edit' => NULL,
            '_add' => 'InstitutionStatistics.add',
            '_delete' => 'InstitutionStatistics.remove',
            '_execute' => 'InstitutionStatistics.excel|InstitutionStatistics.download',
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6403_security_functions` TO `security_functions`');
    }
}
