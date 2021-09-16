<?php
use Migrations\AbstractMigration;

class POCOR6314 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6314_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6314_security_functions` SELECT * FROM `security_functions`');

        // Awards
        $this->insert('security_functions', [
            'name' => 'StaffAwards',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Professional',
            'parent_id' => 9030,
            '_view' => 'StaffAwards.index|StaffAwards.view',
            '_edit' => 'StaffAwards.edit',
            '_add' => 'StaffAwards.add',
            '_delete' => 'StaffAwards.remove',
            'order' => 445,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Staff Qualifications
        $this->insert('security_functions', [
            'name' => 'StaffQualifications',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Professional',
            'parent_id' => 9030,
            '_view' => 'StaffQualifications.index|StaffQualifications.view',
            '_edit' => 'StaffQualifications.edit',
            '_add' => 'StaffQualifications.add',
            '_delete' => 'StaffQualifications.remove',
            '_execute' => 'StaffQualifications.excel',
            'order' => 447,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Staff Extracurriculars
        $this->insert('security_functions', [
            'name' => 'StaffExtracurriculars',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Professional',
            'parent_id' => 9030,
            '_view' => 'StaffExtracurriculars.index|StaffExtracurriculars.view',
            '_edit' => 'StaffExtracurriculars.edit',
            '_add' => 'StaffExtracurriculars.add',
            '_delete' => 'StaffExtracurriculars.remove',
            'order' => 449,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Staff Memberships
        $this->insert('security_functions', [
            'name' => 'StaffMemberships',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Professional',
            'parent_id' => 9030,
            '_view' => 'StaffMemberships.index|StaffMemberships.view',
            '_edit' => 'StaffMemberships.edit',
            '_add' => 'StaffMemberships.add',
            '_delete' => 'StaffMemberships.remove',
            'order' => 451,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        // Staff Licenses
        $this->insert('security_functions', [
            'name' => 'StaffLicenses',
            'controller' => 'Profiles',
            'module' => 'Personal',
            'category' => 'Professional',
            'parent_id' => 9030,
            '_view' => 'StaffLicenses.index|StaffLicenses.view',
            '_edit' => 'StaffLicenses.edit',
            '_add' => 'StaffLicenses.add',
            '_delete' => 'StaffLicenses.remove',
            'order' => 453,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

     //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6314_security_functions` TO `security_functions`');
    }
}
