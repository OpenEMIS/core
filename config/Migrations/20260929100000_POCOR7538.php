<?php
use Migrations\AbstractMigration;

class POCOR7538 extends AbstractMigration
{
    public function up()
    {
        // Backup tables
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7538_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7538_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7538_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `zz_7538_security_roles` SELECT * FROM `security_roles`');

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

        // POCOR-7538 point 1.3/1.4: hide My Classes/My Subjects/All Classes/All Subjects
        // now that Classes/Subjects (inserted above) and the Access All Classes/Access
        // All Subjects role-level flags (below) replace them.
        $this->execute("
            UPDATE `security_functions`
            SET `visible` = 0
            WHERE `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` IN ('My Classes', 'My Subjects', 'All Classes', 'All Subjects')
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

        // POCOR-7538 points 2.1/2.2: All Classes/All Subjects' `_view` still contains
        // Classes.index|Classes.view / Subjects.index|Subjects.view alongside their own
        // AllClasses.*/AllSubjects.* actions. AccessControlComponent::buildPermissions()
        // grants a session permission per '|'-separated action in that string, so a role
        // with only "All Classes" checked was also silently granted the "Classes" page
        // access key (used by SecurityAuthorize::authorize() to gate Institutions >
        // Academic > Classes) - meaning the page was still effectively controlled by
        // "All Classes", not the dedicated "Classes" permission. Stripping the
        // overlapping segment decouples them, leaving AllClasses/AllSubjects to control
        // only their own "show every record" query-scoping behaviour.
        $this->execute("
            UPDATE `security_functions`
            SET `_view` = 'AllClasses.index|AllClasses.view'
            WHERE `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` = 'All Classes'
        ");
        $this->execute("
            UPDATE `security_functions`
            SET `_view` = 'AllSubjects.index|AllSubjects.view'
            WHERE `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` = 'All Subjects'
        ");

        // POCOR-7538 point 1.5: role-level "Access All Classes"/"Access All Subjects"
        // flags, replacing the All Classes/All Subjects permission-grid checkboxes
        // (now hidden above) as the mechanism for unrestricted (not teacher-scoped)
        // access.
        $rolesTable = $this->table('security_roles');
        if (!$rolesTable->hasColumn('access_all_classes')) {
            $rolesTable
                ->addColumn('access_all_classes', 'integer', ['default' => 0, 'null' => false, 'after' => 'visible'])
                ->addColumn('access_all_subjects', 'integer', ['default' => 0, 'null' => false, 'after' => 'access_all_classes'])
                ->update();
        }

        // Migrate: any role that previously had All Classes/All Subjects View granted
        // gets the equivalent new flag set, so existing access is preserved.
        $this->execute("
            UPDATE `security_roles` sr
            JOIN `security_role_functions` srf ON srf.security_role_id = sr.id
            JOIN `security_functions` sf ON sf.id = srf.security_function_id
            SET sr.access_all_classes = 1
            WHERE sf.module = 'Institutions' AND sf.category = 'Academic'
              AND sf.name = 'All Classes' AND srf._view = 1
        ");
        $this->execute("
            UPDATE `security_roles` sr
            JOIN `security_role_functions` srf ON srf.security_role_id = sr.id
            JOIN `security_functions` sf ON sf.id = srf.security_function_id
            SET sr.access_all_subjects = 1
            WHERE sf.module = 'Institutions' AND sf.category = 'Academic'
              AND sf.name = 'All Subjects' AND srf._view = 1
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
            SET sf.visible = bak.visible, sf._view = bak._view, sf._edit = bak._edit, sf._add = bak._add, sf._delete = bak._delete, sf._execute = bak._execute
            WHERE sf.module = 'Institutions' AND sf.category = 'Academic'
              AND sf.name IN ('All Classes', 'My Classes', 'All Subjects', 'My Subjects')
        ");

        $rolesTable = $this->table('security_roles');
        if ($rolesTable->hasColumn('access_all_classes')) {
            $rolesTable
                ->removeColumn('access_all_classes')
                ->removeColumn('access_all_subjects')
                ->update();
        }

        // Remove backup tables
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_functions`');
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_roles`');
    }
}
