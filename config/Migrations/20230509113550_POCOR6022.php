<?php
use Migrations\AbstractMigration;

class POCOR6022 extends AbstractMigration
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
        //Backup table
        $this->execute('CREATE TABLE `security_functions` LIKE `zz_6022_security_functions`');
        $this->execute('INSERT INTO `security_functions` SELECT * FROM `zz_6022_security_functions`');

        $this->execute("UPDATE `security_functions` SET category = 'User Data Completeness' WHERE category = 'User Completeness'");
        $this->execute("UPDATE `security_functions` SET category = 'Institution Data Completeness' WHERE category = 'Institution Completeness'");

    }
    
    public function down()
    {
        //Restore table
        $this->execute('DROP TABLE security_functions');
        $this->table('zz_6022_security_functions')->rename('security_functions');
    }

}
