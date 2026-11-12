<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR4477 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->insertConfigItem();
        $this->extendConfigurationsPermission();
        $this->createFieldConfigurationsTable();
        $this->seedFieldConfigurations();
    }

    public function down()
    {
        $this->execute("DELETE FROM `config_items` WHERE `type` = 'Fields Configurations'");
        $this->restoreTables();
        $this->dropTable('field_configurations');
    }

    /**
     * Back up config_items before modifying it, following the established
     * convention (e.g. POCOR8872) so `down()` can restore it exactly.
     */
    private function backupTables()
    {
        if (!$this->hasTable('z_4477_config_items')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_4477_config_items` LIKE `config_items`');
            $this->execute('INSERT INTO `z_4477_config_items` SELECT * FROM `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function restoreTables()
    {
        if ($this->hasTable('z_4477_config_items')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_4477_config_items` TO `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * New config_items row - its `type` becomes a selectable option in the
     * System Configurations page's primary dropdown (ConfigItemsBehavior
     * builds that list from distinct visible `type` values). No corresponding
     * `field_type`/`option_type` needed - this type is handled by a dedicated
     * controller action (FieldsConfiguration), not the generic CRUD fallback.
     */
    private function insertConfigItem()
    {
        $table = $this->table('config_items');
        $table->insert([
            [
                'id' => null,
                'name' => 'Fields Configurations',
                'code' => 'fields_configurations',
                'type' => 'Fields Configurations',
                'label' => 'Fields Configurations',
                'value' => '0',
                'value_selection' => '',
                'default_value' => '0',
                'editable' => '1',
                'visible' => '1',
                'field_type' => '',
                'option_type' => '',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s'),
            ],
        ])->save();
    }

    /**
     * Extend the existing generic "Configurations" permission row (id=5020)
     * the same way Themes did (POCOR4178) - append the new action names to
     * _view/_edit rather than overwrite, and use CONCAT so this is safe to
     * run regardless of whatever else has already been appended by other
     * tabs since. Action name must be "FieldsConfigurations" (plural, no
     * space) to match ConfigItemsBehavior::checkController()'s
     * Inflector::camelize('Fields Configurations', ' ') result - that's what
     * it uses to find/redirect to the matching controller method.
     */
    private function extendConfigurationsPermission()
    {
        $this->execute("
            UPDATE `security_functions`
            SET `_view` = CONCAT(`_view`, '|FieldsConfigurations.index|FieldsConfigurations.view'),
                `_edit` = CONCAT(`_edit`, '|FieldsConfigurations.edit')
            WHERE `id` = 5020
                AND `_view` NOT LIKE '%FieldsConfigurations.index%'
        ");
    }

    private function createFieldConfigurationsTable()
    {
        $table = $this->table('field_configurations');
        $table
            ->addColumn('module', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Institution | Student | Staff',
            ])
            ->addColumn('field_name', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Matches the field key used by the owning ControllerActionTable',
            ])
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
                'comment' => 'Display label shown on the Fields Configurations admin screen',
            ])
            ->addColumn('is_mandatory', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Mandatory fields are locked - visible always, not admin-configurable',
            ])
            ->addColumn('visible', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('order', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Display sequence within its module, managed by ControllerAction.Reorder',
            ])
            ->addColumn('modified_user_id', 'integer', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addColumn('created_user_id', 'integer', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['module', 'field_name'], ['unique' => true])
            ->create();
    }

    /**
     * Seed data per POCOR-4477 field classification, confirmed 2026-09-14:
     * a field is mandatory (locked) if it's required on the front end, the
     * back end, or both; config-driven-sometimes-mandatory fields default to
     * mandatory=true (conservative - never let an admin hide something that
     * might currently be enforced); custom fields are explicitly excluded.
     */
    private function seedFieldConfigurations()
    {
        $now = date('Y-m-d H:i:s');
        $rows = [];
        $order = 0;

        $add = function (string $module, string $fieldName, string $name, bool $mandatory) use (&$rows, &$order, $now) {
            $rows[] = [
                'module' => $module,
                'field_name' => $fieldName,
                'name' => $name,
                'is_mandatory' => $mandatory,
                'visible' => true,
                'order' => $order,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => $now,
            ];
            $order++;
        };

        // Institution - plugins/Institution/src/Model/Table/InstitutionsTable.php
        $order = 0;
        $add('Institution', 'logo_content', 'Logo', false);
        $add('Institution', 'name', 'Institution Name', true);
        $add('Institution', 'alternative_name', 'Alternative Name', false);
        $add('Institution', 'code', 'Institution Code', true);
        $add('Institution', 'classification', 'Classification', false);
        $add('Institution', 'institution_sector_id', 'Sector', true);
        $add('Institution', 'institution_provider_id', 'Provider', false);
        $add('Institution', 'institution_type_id', 'Type', true);
        $add('Institution', 'institution_ownership_id', 'Ownership', true);
        $add('Institution', 'institution_gender_id', 'Gender', true);
        $add('Institution', 'date_opened', 'Date Opened', false);
        $add('Institution', 'date_closed', 'Date Closed', false);
        $add('Institution', 'institution_status_id', 'Status', false);
        $add('Institution', 'address', 'Address', true);
        $add('Institution', 'postal_code', 'Postal Code', false);
        $add('Institution', 'institution_locality_id', 'Locality', true);
        $add('Institution', 'latitude', 'Latitude', true);
        $add('Institution', 'longitude', 'Longitude', true);
        $add('Institution', 'area_id', 'Area', false);
        $add('Institution', 'area_administrative_id', 'Area Administrative', false);
        $add('Institution', 'contact_person', 'Contact Person', false);
        $add('Institution', 'telephone', 'Telephone', false);
        $add('Institution', 'email', 'Email', false);
        $add('Institution', 'website', 'Website', false);

        // Staff - plugins/Institution/src/Model/Table/StaffTable.php (add) +
        // StaffUserTable.php (view)
        $order = 0;
        $add('Staff', 'openemis_no', 'OpenEMIS ID', true);
        $add('Staff', 'photo_content', 'Photo', false);
        $add('Staff', 'first_name', 'First Name', true);
        $add('Staff', 'middle_name', 'Middle Name', false);
        $add('Staff', 'third_name', 'Third Name', false);
        $add('Staff', 'last_name', 'Last Name', true);
        $add('Staff', 'preferred_name', 'Preferred Name', false);
        $add('Staff', 'gender_id', 'Gender', true);
        $add('Staff', 'date_of_birth', 'Date Of Birth', true);
        $add('Staff', 'nationality_id', 'Nationality', true);
        $add('Staff', 'identity_type_id', 'Identity Type', true);
        $add('Staff', 'identity_number', 'Identity Number', true);
        $add('Staff', 'email', 'Email', true);
        $add('Staff', 'mobile_number', 'Mobile Number', true);
        $add('Staff', 'contact_type', 'Contact Type', true); //POCOR-4477: live field key on StaffUserTable is 'contact_type', not 'contact_type_id' - see MandatoryBehavior::addBeforeAction
        $add('Staff', 'contact_value', 'Contact Value', true);
        $add('Staff', 'address', 'Address', false);
        $add('Staff', 'postal_code', 'Postal Code', false);
        $add('Staff', 'address_area_id', 'Address Area', false);
        $add('Staff', 'birthplace_area_id', 'Birthplace Area', false);
        $add('Staff', 'username', 'Username', true);
        $add('Staff', 'password', 'Password', true);
        $add('Staff', 'position_type', 'Position Type', true);
        $add('Staff', 'FTE', 'FTE', true);
        $add('Staff', 'institution_position_id', 'Position', true);
        $add('Staff', 'is_homeroom', 'Is Homeroom', true);
        $add('Staff', 'staff_position_grade_id', 'Position Grade', true);
        $add('Staff', 'staff_type_id', 'Staff Type', true);
        $add('Staff', 'start_date', 'Start Date', true);
        $add('Staff', 'end_date', 'End Date', false);

        // Student - plugins/Institution/src/Model/Table/StudentUserTable.php
        $order = 0;
        $add('Student', 'openemis_no', 'OpenEMIS ID', true);
        $add('Student', 'photo_content', 'Photo', false);
        $add('Student', 'first_name', 'First Name', true);
        $add('Student', 'middle_name', 'Middle Name', false);
        $add('Student', 'third_name', 'Third Name', false);
        $add('Student', 'last_name', 'Last Name', true);
        $add('Student', 'preferred_name', 'Preferred Name', false);
        $add('Student', 'gender_id', 'Gender', true);
        $add('Student', 'date_of_birth', 'Date Of Birth', true);
        $add('Student', 'email', 'Email', false);
        $add('Student', 'mobile_number', 'Mobile Number', false);
        $add('Student', 'address', 'Address', false);
        $add('Student', 'postal_code', 'Postal Code', false);
        $add('Student', 'address_area_id', 'Address Area', false);
        $add('Student', 'birthplace_area_id', 'Birthplace Area', false);
        $add('Student', 'identity_number', 'Identity Number', false);
        $add('Student', 'class', 'Class', false);
        $add('Student', 'start_date', 'Start Date', true);
        $add('Student', 'education_grade_id', 'Grade', true);
        $add('Student', 'academic_period_id', 'Academic Period', true);

        $this->table('field_configurations')->insert($rows)->save();
    }
}
