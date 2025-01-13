<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR7510 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {

        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7510_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO`zz_7510_security_functions` SELECT * FROM `security_functions`');

        $resultRow = $this->fetchRow("SELECT * FROM `security_functions`
                                WHERE `name` = 'Results' AND 
                                `controller` = 'Examinations' AND
                                `module` = 'Administration' AND
                                `category` = 'Examinations' 
                                ");

        $query = $this->fetchRow("SELECT * FROM `security_functions`
                                WHERE `name` = 'Sync' AND 
                                `controller` = 'Examinations' AND
                                `module` = 'Administration' AND
                                `category` = 'Examinations' 
                    ");

        if (!$query && $resultRow) {

            $parentId = $resultRow['parent_id'];
            $order = $resultRow['order'];

            $record = [
                [
                    'name' => 'Sync',
                    'controller' => 'Examinations',
                    'module' => 'Administration',
                    'category' => 'Examinations',
                    'parent_id' => $parentId,
                    '_view' => NULL,
                    '_edit' => NULL,
                    '_add' => NULL,
                    '_delete' => NULL,
                    '_execute' => 'Exam.sync',
                    'order' => $order,
                    'visible' => 1,
                    'description' => NULL,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => 1,
                    'created' => date('Y-m-d H:i:s')
                ]
            ];
            $this->table('security_functions')->insert($record)->save();
        }
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7510_security_functions` TO `security_functions`');
    }
}
