<?php
use Migrations\AbstractMigration;

class POCOR6492 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('DROP TABLE IF EXISTS `zz_6492_locale_contents`');
        $this->execute('CREATE TABLE `zz_6492_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_6492_locale_contents` SELECT * FROM `locale_contents`');

        // Backup security_functions table
        $this->execute('DROP TABLE IF EXISTS `zz_6492_security_functions`');
        $this->execute('CREATE TABLE `zz_6492_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6492_security_functions` SELECT * FROM `security_functions`');

        // Getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");

        $current_time  = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en'              => 'Standard',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
            [
                'en'              => 'Birth Certificate',
                'created_user_id' => 1,
                'created'         => $current_time
            ],
        ];
        $this->insert('locale_contents', $localeContent);

        // Inserting record in security_functions
        $this->insert('security_functions', [
            'name'            => 'Institutions Statistics Standard',
            'controller'      => 'Institutions',
            'module'          => 'Institutions',
            'category'        => 'General',
            'parent_id'       => 1000,
            '_view'           => 'InstitutionStandards.index|InstitutionStandards.view',
            '_add'            => 'InstitutionStandards.add',
            '_delete'         => 'InstitutionStandards.remove',
            '_execute'        => 'InstitutionStandards.excel|InstitutionStandards.download',
            'order'           => $data[0] + 1,
            'visible'         => 1,
            'description'     => NULL,
            'created_user_id' => 1,
            'created'         => $current_time
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6492_locale_contents` TO `locale_contents`');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6492_security_functions` TO `security_functions`');
    }
}
