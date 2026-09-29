<?php
use Migrations\AbstractMigration;

class POCOR7538 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7538_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7538_security_functions` SELECT * FROM `security_functions`');

        $existing = $this->fetchRow("
            SELECT COUNT(*) AS total
            FROM `security_functions`
            WHERE `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `category` = 'Academic'
              AND `name` IN ('Classes', 'Subjects')
            ");

        if ((int)$existing['total'] === 0) {
            $row = $this->fetchRow("
                SELECT MAX(`order`) AS max_order, MAX(`parent_id`) AS parent_id
                FROM `security_functions`
                WHERE `module` = 'Institutions'
                  AND `category` = 'Academic'
                ");
            $order = (int)$row['max_order'];
            $parentId = $row['parent_id'];

            $records = [
                [
                    'name' => 'Classes', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Academic', 'parent_id' => $parentId,
                    '_view' => 'Classes.index|Classes.view', '_edit' => 'Classes.edit', '_add' => 'Classes.add', '_delete' => 'Classes.remove', '_execute' => 'Classes.excel|Classes.download',
                    'order' => $order + 1, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Subjects', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Academic', 'parent_id' => $parentId,
                    '_view' => 'Subjects.index|Subjects.view', '_edit' => 'Subjects.edit', '_add' => 'Subjects.add', '_delete' => 'Subjects.remove', '_execute' => 'Subjects.excel|Subjects.download',
                    'order' => $order + 2, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ],
            ];
            $this->table('security_functions')->insert($records)->save();
        }

        // POCOR-7538 point 1.3: hide My Classes/My Subjects now that Classes/Subjects
        // (inserted above) replace them.
        $this->execute("
            UPDATE `security_functions`
            SET `visible` = 0
            WHERE `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` IN ('My Classes', 'My Subjects')
        ");

        // POCOR-7538 points 1.4/1.5: All Classes/My Classes/All Subjects/My Subjects
        // keep View only - Edit/Add/Delete/Execute now live on the new Classes/Subjects
        // permissions inserted above.
        $this->execute("
            UPDATE `security_functions`
            SET `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
            WHERE `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` IN ('All Classes', 'My Classes', 'All Subjects', 'My Subjects')
        ");
    }

    public function down()
    {
        $this->execute("
            DELETE FROM `security_functions`
            WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` IN ('Classes', 'Subjects')
        ");

        $this->execute("
            UPDATE `security_functions` sf
            JOIN `zz_7538_security_functions` bak ON bak.id = sf.id
            SET sf.visible = bak.visible, sf._edit = bak._edit, sf._add = bak._add, sf._delete = bak._delete, sf._execute = bak._execute
            WHERE sf.module = 'Institutions' AND sf.category = 'Academic'
              AND sf.name IN ('All Classes', 'My Classes', 'All Subjects', 'My Subjects')
        ");

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_functions`');
    }
}
