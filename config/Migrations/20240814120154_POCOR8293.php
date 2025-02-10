<?php
declare(strict_types=1);
use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class POCOR8293 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup Table
        $this->execute('CREATE TABLE `zz_8293_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_8293_security_functions` SELECT * FROM `security_functions`');
        
        //Insert security functions for Student Health List
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Overview', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'Healths.index|Healths.view', 'Healths.edit', 'Healths.add', 'Healths.remove', 'Healths.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Allergies', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthAllergies.index|HealthAllergies.view', 'HealthAllergies.edit', 'HealthAllergies.add', 'HealthAllergies.remove', 'HealthAllergies.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Consultations', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthConsultations.index|HealthConsultations.view', 'HealthConsultations.edit', 'HealthConsultations.add', 'HealthConsultations.remove', 'HealthConsultations.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Families', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthFamilies.index|HealthFamilies.view', 'HealthFamilies.edit', 'HealthFamilies.add', 'HealthFamilies.remove', 'HealthFamilies.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Histories', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthHistories.index|HealthHistories.view', 'HealthHistories.edit', 'HealthHistories.add', 'HealthHistories.remove', 'HealthHistories.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Vaccinations', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthImmunizations.index|HealthImmunizations.view', 'HealthImmunizations.edit', 'HealthImmunizations.add', 'HealthImmunizations.remove', 'HealthImmunizations.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Medications', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthMedications.index|HealthMedications.view', 'HealthMedications.edit', 'HealthMedications.add', 'HealthMedications.remove', 'HealthMedications.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Tests', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthTests.index|HealthTests.view', 'HealthTests.edit', 'HealthTests.add', 'HealthTests.remove', 'HealthTests.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Student Body Mass', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthBodyMasses.index|HealthBodyMasses.view', 'HealthBodyMasses.edit', 'HealthBodyMasses.add', 'HealthBodyMasses.delete', 'HealthBodyMasses.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Student Insurance', 'GuardianNavs', 'Guardian', 'Students - Health', '2000', 'HealthInsurances.index|HealthInsurances.view', 'HealthInsurances.edit', 'HealthInsurances.add', 'HealthInsurances.delete', 'HealthInsurances.excel', '2000', '1', NULL, NULL, NULL, '2', CURRENT_TIMESTAMP);");
    }
    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8293_security_functions` TO `security_functions`');
    }
}
