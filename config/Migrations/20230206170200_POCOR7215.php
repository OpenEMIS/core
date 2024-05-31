<?php
use Migrations\AbstractMigration;

class POCOR7215 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_7215_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `zz_7215_security_roles` SELECT * FROM `security_roles`');


        // DROP foreign key relationship     
        $this->execute("ALTER TABLE security_roles DROP FOREIGN KEY `secur_roles_fk_secur_group_id`");
        
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `zz_7215_security_roles` TO `security_roles`');
    }
}

?>