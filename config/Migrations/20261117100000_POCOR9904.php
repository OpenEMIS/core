<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9904 extends AbstractMigration
{
    // POCOR-9904: new "View Attachment" permission under Institutions > Staff - General and
    // Institutions > Students - General.
   

    private const NAME = 'View Attachment';
    private const MODULE = 'Institutions';

    private const TARGETS = [
        ['controller' => 'Students', 'category' => 'Students - General'],
        ['controller' => 'Staff', 'category' => 'Staff - General'],
    ];

    public function up(): void
    {
        // Backup
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9904_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_9904_security_functions` SELECT * FROM `security_functions`');

        foreach (self::TARGETS as $target) {
            $controller = $target['controller'];
            $category = $target['category'];

            $existing = $this->fetchRow("
                SELECT `id` FROM `security_functions`
                WHERE `name` = '" . self::NAME . "'
                  AND `controller` = '$controller'
                  AND `module` = '" . self::MODULE . "'
                  AND `category` = '$category'
            ");
            if (!empty($existing)) {
                continue;
            }

            $attachments = $this->fetchRow("
                SELECT `parent_id`, `order` FROM `security_functions`
                WHERE `name` = 'Attachments'
                  AND `controller` = '$controller'
                  AND `module` = '" . self::MODULE . "'
                  AND `category` = '$category'
            ");
            if (empty($attachments)) {
                continue;
            }

            $order = (int)$attachments['order'];
            $parentId = (int)$attachments['parent_id'];

            // Make room directly below Attachments, within this module + category only.
            $this->execute("
                UPDATE `security_functions`
                SET `order` = `order` + 1
                WHERE `module` = '" . self::MODULE . "'
                  AND `category` = '$category'
                  AND `order` > $order
            ");

            $this->table('security_functions')->insert([
                'name' => self::NAME,
                'controller' => $controller,
                'module' => self::MODULE,
                'category' => $category,
                'parent_id' => $parentId,
                '_view' => 'Attachments.viewAttachment',
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => null,
                'order' => $order + 1,
                'visible' => 1,
                'description' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ])->save();
        }
    }

    public function down(): void
    {
        // Without the pre-migration snapshot we cannot tell rows this migration created from a
        // same-named permission that already existed, so leave everything untouched.
        if (!$this->hasTable('z_9904_security_functions')) {
            return;
        }

        foreach (self::TARGETS as $target) {
            $category = $target['category'];

            // Only rows created by this migration: absent from the pre-migration snapshot.
            $created = $this->fetchAll("
                SELECT `sf`.`id`, `sf`.`order`
                FROM `security_functions` AS `sf`
                LEFT JOIN `z_9904_security_functions` AS `bk` ON `bk`.`id` = `sf`.`id`
                WHERE `sf`.`name` = '" . self::NAME . "'
                  AND `sf`.`controller` = '{$target['controller']}'
                  AND `sf`.`module` = '" . self::MODULE . "'
                  AND `sf`.`category` = '$category'
                  AND `bk`.`id` IS NULL
            ");

            foreach ($created as $row) {
                $id = (int)$row['id'];
                $order = (int)$row['order'];

                $this->execute("DELETE FROM `security_role_functions` WHERE `security_function_id` = $id");
                $this->execute("DELETE FROM `security_functions` WHERE `id` = $id");

                // Close the gap with the inverse of up()'s shift instead of restoring the snapshot's
                // order values, so ordering changes made after the migration are kept.
                $this->execute("
                    UPDATE `security_functions`
                    SET `order` = `order` - 1
                    WHERE `module` = '" . self::MODULE . "'
                      AND `category` = '$category'
                      AND `order` > $order
                ");
            }
        }

        $this->execute('DROP TABLE IF EXISTS `z_9904_security_functions`');
    }
}
