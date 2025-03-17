<?php


use Phinx\Migration\AbstractMigration;

class POCOR8919 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateDateFields();

    }

    private function updateCreateZTable(): void
    {
        if (!$this->hasTable('z_8919_examination_centres')) {
            $this->execute('CREATE TABLE `z_8919_examination_centres` LIKE `examination_centres`');
            $this->execute('INSERT IGNORE INTO `z_8919_examination_centres` SELECT * FROM `examination_centres`');
        }
        if (!$this->hasTable('z_8919_examination_centres_examinations')) {
            $this->execute('CREATE TABLE `z_8919_examination_centres_examinations` LIKE `examination_centres_examinations`');
            $this->execute('INSERT IGNORE INTO `z_8919_examination_centres_examinations` SELECT * FROM `examination_centres_examinations`');
        }
    }

    private function updateDateFields(): void
    {

$this->execute('SET FOREIGN_KEY_CHECKS=0;');
if ($this->table('examination_centres_examinations')->hasColumn('academic_period_id')) {
    $this->query("ALTER TABLE examination_centres_examinations DROP COLUMN academic_period_id;");
}
if ($this->table('examination_centres')->hasColumn('academic_period_id')) {
    $this->query("ALTER TABLE examination_centres DROP COLUMN academic_period_id;");
}
$this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function down(): void
    {
        if ($this->hasTable('z_8919_examination_centres')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `examination_centres`');
            $this->execute('RENAME TABLE `z_8919_examination_centres` TO `examination_centres`');
        }
        if ($this->hasTable('z_8919_examination_centres_examinations')) {
            $this->execute('DROP TABLE IF EXISTS `examination_centres_examinations`');
            $this->execute('RENAME TABLE `z_8919_examination_centres_examinations` TO `examination_centres_examinations`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
