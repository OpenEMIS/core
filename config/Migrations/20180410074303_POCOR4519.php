<?php

use Phinx\Migration\AbstractMigration;

class POCOR4519 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `guardian_relations` ADD COLUMN `gender_id` int(1) NULL COMMENT "links to genders.id" AFTER `name`');
        
        $GuardianRelations = $this->table('guardian_relations');
        $GuardianRelations->addIndex('gender_id')
                         ->save();
    }

    public function down()
    {
        $this->execute('ALTER TABLE `guardian_relations` DROP COLUMN `gender_id`');

        $GuardianRelations = $this->table('guardian_relations');
        $GuardianRelations->removeIndex('gender_id');
    }
}
