<?php
use Migrations\AbstractMigration;

class POCOR6513a extends AbstractMigration
{
    /**
     * Change Method.
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * Ticket no - 6513
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_6513_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_6513_locale_contents` SELECT * FROM `locale_contents`');

        // // Backup security_functions table
        $this->execute('CREATE TABLE `zz_6513_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6513_security_functions` SELECT * FROM `security_functions`');
        
        // //inserting data into locale_contents table
        $localeContent = [
            [
                'en' => 'Performance',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('locale_contents', $localeContent);

        //getting last record's order
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Reports'");
        $order = $row[0] + 1;
        //inserting data into security_functions table
        $data = [
            'name' => 'Performance',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => 'Performance.index|Performance.view',
            '_edit' => NULL,
            '_add' => 'Performance.add',
            '_delete' => 'Performance.remove',
            '_execute' => 'Performance.download',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $this->insert('security_functions', $data);
    }

    // rollback migration
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6513_security_functions` TO `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6513_locale_contents` TO `locale_contents`');
    }
}
