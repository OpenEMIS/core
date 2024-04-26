<?php

use Migrations\AbstractMigration;

class POCOR8039 extends AbstractMigration
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

        $this->backupTables();

        $this->renameConfigItems();

        $this->renameSecurityFunctions();

        $this->insertNewConfigItems();

        $this->insertNewSecurityItems();

    }

    // rollback

    /**
     * @return void
     */
    public function backupTables()
    {
        if(!$this->hasTable('zz_8039_config_items')){
        $this->execute('CREATE TABLE `zz_8039_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8039_config_items` SELECT * FROM `config_items`');
        }
        if(!$this->hasTable('zz_8039_security_functions')) {
            $this->execute('CREATE TABLE `zz_8039_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `zz_8039_security_functions` SELECT * FROM `security_functions`');
        }
    }

    public function down()
    {
        $this->restoreTable();
    }

    /**
     * @return void
     */
    public function restoreTable()
    {
        if ($this->hasTable('zz_8039_config_items')) {
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `zz_8039_config_items` TO `config_items`');
        }
        if ($this->hasTable('zz_8039_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `zz_8039_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * @return void
     */
    public function renameConfigItems()
    {
        $this->execute("UPDATE config_items SET
                        name='Infrastructure Needs',
                        label='Infrastructure Needs',
                        code='infrastructures_need'
                    WHERE code='infrastructures_needs'");
        $this->execute("UPDATE config_items SET
                        name='Infrastructure Land',
                        label='Infrastructure Land',
                        code='infrastructure_land'
                    WHERE code='infrastructures_overview'");
        $this->execute("UPDATE config_items SET
                        type='Personal Data Completeness',
                        code=CONCAT('personal_', `code`)
                    WHERE type='User Data Completeness'");
    }
    public function renameSecurityFunctions()
    {
        $this->execute("UPDATE security_functions SET
                        name='Institution Profile Completeness',
                        _view='InstitutionProfileCompleteness.view'
                    WHERE name='Institution Profile Completness'");
        $this->execute("UPDATE security_functions SET
                        controller='Profiles',
                        name='Personal Profile Completeness',
                        _view='PersonalDashboard.view'
                    WHERE name='User Profile Completeness'");
    }

    function generateConfigData($name, $code, $type, $label) {
        return [
            'id' => NULL,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'label' => $label,
            'value' => '1',
            'value_selection' => '0',
            'default_value' => '0',
            'editable' => '1',
            'visible' => '1',
            'field_type' => 'Dropdown',
            'option_type' => 'completeness',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
    }
    function generateSecurityData($name, $category, $view) {
        return [
            'id' => NULL,
            'name' => $name,
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => $category,
            'parent_id' => 8,
            '_view' => $view . '.view',
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => NULL,
            'order' => 1,
            'visible' => 1,
            'description' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * @return void
     */
    public function insertNewConfigItems()
    {
        $table = $this->table('config_items');
        $data = [
            $this->generateConfigData(
                'Infrastructure Building',
                'infrastructure_building',
                'Institution Data Completeness',
                'Infrastructure Building'),
            $this->generateConfigData(
                'Infrastructure Floor',
                'infrastructure_floor',
                'Institution Data Completeness',
                'Infrastructure Floor'),
            $this->generateConfigData(
                'Infrastructure Room',
                'infrastructure_room',
                'Institution Data Completeness',
                'Infrastructure Room'),
            $this->generateConfigData(
                'Overview',
                'staff_overview',
                'Staff Data Completeness',
                'Overview'),
            $this->generateConfigData(
                'Nationalities',
                'staff_nationalities',
                'Staff Data Completeness',
                'Nationalities'),
            $this->generateConfigData(
                'Identities',
                'staff_identities',
                'Staff Data Completeness',
                'Identities'),
            $this->generateConfigData(
                'Contacts',
                'staff_contacts',
                'Staff Data Completeness',
                'Contacts'),
            $this->generateConfigData(
                'Qualifications',
                'staff_qualifications',
                'Staff Data Completeness',
                'Qualifications'),
            $this->generateConfigData(
                'Overview',
                'student_overview',
                'Student Data Completeness',
                'Overview'),
            $this->generateConfigData(
                'Nationalities',
                'student_nationalities',
                'Student Data Completeness',
                'Nationalities'),
            $this->generateConfigData(
                'Identities',
                'student_identities',
                'Student Data Completeness',
                'Identities'),
            $this->generateConfigData(
                'Guardians',
                'student_guardians',
                'Student Data Completeness',
                'Guardians'),
            $this->generateConfigData(
                'Absence',
                'student_absence',
                'Student Data Completeness',
                'Absence'),
        ];
        $table->insert($data)->save();
    }
    public function insertNewSecurityItems()
    {
        $table = $this->table('security_functions');
        $data = [
            $this->generateSecurityData(
                'Staff Profile Completeness',
                'Staff',
                'StaffDashboard'),
            $this->generateSecurityData(
                'Student Profile Completeness',
                'Students',
                'StudentDashboard'),
        ];
        $table->insert($data)->save();
    }
}
