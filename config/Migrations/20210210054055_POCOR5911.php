<?php
use Migrations\AbstractMigration;

class POCOR5911 extends AbstractMigration
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
        // sql to create institute staff_payslips
        $this->execute("CREATE TABLE `staff_payslips` (
            `id` int(11) NOT NULL,
            `name` varchar(250) NOT NULL,
            `description` text,
            `file_name` varchar(250) NOT NULL,
            `file_content` longblob NOT NULL,
            `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains payslip attachments for every staff'");
    
        // sql to alter staff_payslips
        $this->execute("ALTER TABLE `staff_payslips`
                        ADD PRIMARY KEY (`id`),
                        ADD KEY `staff_id` (`staff_id`)");

        // sql to alter staff_payslips
        $this->execute("ALTER TABLE `staff_payslips`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_payslips`');
    }
}
