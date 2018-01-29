<?php
use Migrations\AbstractMigration;

class POCOR4372 extends AbstractMigration
{
    /**
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $sql = "UPDATE `security_functions`
                SET `_view` = 'Risks.index|Risks.view|InstitutionStudentIndexes.index|InstitutionStudentIndexes.view',
                    `_execute` = 'Risks.generate'
                WHERE `id`=1055";

        $this->execute($sql);

        $sql = "UPDATE `security_functions`
                SET `_view` = 'Risks.index|Risks.view',
                    `_edit` = 'Risks.edit',
                    `_add` = 'Risks.add',
                    `_delete` ='Risks.remove'
                WHERE `id`=5066";

        $this->execute($sql);
    }

    public function down()
    {
        $sql = "UPDATE `security_functions`
                SET `_view` = 'Indexes.index|Indexes.view|InstitutionStudentIndexes.index|InstitutionStudentIndexes.view',
                    `_execute` = 'Indexes.generate'
                WHERE `id`=1055";

        $this->execute($sql);

        $sql = "UPDATE `security_functions`
                SET `_view` = 'Indexes.index|Risks.view',
                    `_edit` = 'Indexes.edit',
                    `_add` = 'Indexes.add',
                    `_delete` ='Indexes.remove'
                WHERE `id`=5066";

        $this->execute($sql);
    }
}
