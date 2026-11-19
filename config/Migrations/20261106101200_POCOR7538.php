<?php
use Migrations\AbstractMigration;

class POCOR7538 extends AbstractMigration
{
    public function up()
    {
        // Backup tables
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7538_security_functions` LIKE `security_functions`');
        // INSERT IGNORE: safe to re-run, and a re-run keeps the original pre-migration copy
        $this->execute('INSERT IGNORE INTO `z_7538_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7538_security_roles` LIKE `security_roles`');
        $this->execute('INSERT IGNORE INTO `z_7538_security_roles` SELECT * FROM `security_roles`');
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7538_security_role_functions` LIKE `security_role_functions`');
        $this->execute('INSERT IGNORE INTO `z_7538_security_role_functions` SELECT * FROM `security_role_functions`');

        $existing = $this->fetchRow("
            SELECT COUNT(*) AS total
            FROM `security_functions`
            WHERE `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `category` = 'Academic'
              AND `name` IN ('Classes', 'Subjects')
            ");

        // Classes/Subjects already exist: POCOR7538 has already run on this server (e.g. the
        // earlier 20260929100000 version), so leave everything as it is - no insert, no update.
        if ((int)$existing['total'] > 0) {
            return;
        }

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

        // Migrate: carry each role's existing All/My Classes and All/My Subjects grants over
        // to the new Classes/Subjects permissions ("if permissions were enabled for my
        // classes or my subjects, it should follow the same for classes or subjects").
        // Edit/Add/Delete/Execute moved to the new rows and Classes.index/Subjects.index was
        // stripped from All Classes/All Subjects above, so without this roles lose page
        // access (e.g. Principal) or Edit/Add/Delete (e.g. teachers) until re-ticked.
        // All Classes/All Subjects are sources too, so roles that only had those keep page
        // access. Only ever raises a tick, never lowers one.
        $map = [
            'Classes' => ['All Classes', 'My Classes'],
            'Subjects' => ['All Subjects', 'My Subjects'],
        ];
        foreach ($map as $new => $old) {
            $row = $this->fetchRow("
                SELECT `id` FROM `security_functions`
                WHERE `module` = 'Institutions' AND `category` = 'Academic' AND `name` = '$new'
                LIMIT 1
            ");
            if (empty($row)) {
                continue;
            }
            $newId = (int)$row['id'];
            $oldNames = "'" . implode("', '", $old) . "'";

            // per role: a tick on any of the old permissions. JOIN security_roles skips
            // orphaned security_role_functions rows of deleted roles (e.g. TO-MET roles 18/19),
            // which would otherwise fail the insert below on secur_role_funct_fk_sec_rol_id.
            $grants = "
                SELECT `srf`.`security_role_id`,
                       MAX(IFNULL(`srf`.`_view`, 0)) AS `v`, MAX(IFNULL(`srf`.`_edit`, 0)) AS `e`,
                       MAX(IFNULL(`srf`.`_add`, 0)) AS `a`, MAX(IFNULL(`srf`.`_delete`, 0)) AS `d`,
                       MAX(IFNULL(`srf`.`_execute`, 0)) AS `x`
                FROM `security_role_functions` `srf`
                JOIN `security_roles` `sr` ON `sr`.`id` = `srf`.`security_role_id`
                JOIN `security_functions` `sf` ON `sf`.`id` = `srf`.`security_function_id`
                WHERE `sf`.`module` = 'Institutions' AND `sf`.`category` = 'Academic'
                  AND `sf`.`name` IN ($oldNames)
                GROUP BY `srf`.`security_role_id`
                HAVING GREATEST(`v`, `e`, `a`, `d`, `x`) = 1
            ";

            // roles that already have a row for the new permission: only raise ticks
            $this->execute("
                UPDATE `security_role_functions` `srf`
                JOIN ($grants) `g` ON `g`.`security_role_id` = `srf`.`security_role_id`
                SET `srf`.`_view` = GREATEST(IFNULL(`srf`.`_view`, 0), `g`.`v`),
                    `srf`.`_edit` = GREATEST(IFNULL(`srf`.`_edit`, 0), `g`.`e`),
                    `srf`.`_add` = GREATEST(IFNULL(`srf`.`_add`, 0), `g`.`a`),
                    `srf`.`_delete` = GREATEST(IFNULL(`srf`.`_delete`, 0), `g`.`d`),
                    `srf`.`_execute` = GREATEST(IFNULL(`srf`.`_execute`, 0), `g`.`x`),
                    `srf`.`modified_user_id` = 1,
                    `srf`.`modified` = NOW()
                WHERE `srf`.`security_function_id` = $newId
            ");

            // roles with no row yet: insert one
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `created_user_id`, `created`)
                SELECT `g`.`v`, `g`.`e`, `g`.`a`, `g`.`d`, `g`.`x`, `g`.`security_role_id`, $newId, 1, NOW()
                FROM ($grants) `g`
                WHERE NOT EXISTS (
                    SELECT 1 FROM (SELECT `security_role_id`, `security_function_id` FROM `security_role_functions`) `existing`
                    WHERE `existing`.`security_role_id` = `g`.`security_role_id`
                      AND `existing`.`security_function_id` = $newId
                )
            ");
        }
    }

    public function down()
    {
        // role grants on the new Classes/Subjects permissions go with them
        $this->execute("
            DELETE `srf` FROM `security_role_functions` `srf`
            JOIN `security_functions` `sf` ON `sf`.`id` = `srf`.`security_function_id`
            WHERE `sf`.`controller` = 'Institutions' AND `sf`.`module` = 'Institutions' AND `sf`.`category` = 'Academic'
              AND `sf`.`name` IN ('Classes', 'Subjects')
        ");

        $this->execute("
            DELETE FROM `security_functions`
            WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Academic'
              AND `name` IN ('Classes', 'Subjects')
        ");

        // zz_ is the original pre-7538 copy on servers where the earlier POCOR7538
        // (20260929100000) ran; use it if present, otherwise this migration's z_ copy
        $backup = $this->hasTable('zz_7538_security_functions') ? 'zz_7538_security_functions' : 'z_7538_security_functions';

        $this->execute("
            UPDATE `security_functions` sf
            JOIN `$backup` bak ON bak.id = sf.id
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
        $this->execute('DROP TABLE IF EXISTS `z_7538_security_functions`');
        $this->execute('DROP TABLE IF EXISTS `z_7538_security_roles`');
        $this->execute('DROP TABLE IF EXISTS `z_7538_security_role_functions`');
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_functions`');
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_roles`');
        $this->execute('DROP TABLE IF EXISTS `zz_7538_security_role_functions`');
    }
}
