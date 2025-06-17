<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8870 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute("CREATE TABLE institution_student_programmes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                institution_id INT NOT NULL,
                student_id INT NOT NULL,
                education_programme_id INT NOT NULL,
                registration_number VARCHAR(11) UNIQUE NULL,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL,
                FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES security_users(id) ON DELETE CASCADE,
                FOREIGN KEY (education_programme_id) REFERENCES education_programmes(id) ON DELETE CASCADE
            )");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `institution_student_programmes`');
    }
}
