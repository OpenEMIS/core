<?php
use Migrations\AbstractMigration;

class POCOR7538Part2 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_7538_part2_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7538_part2_security_functions` SELECT * FROM `security_functions`');

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
    }

    public function down()
    {
        $this->execute("
            UPDATE `security_functions` sf
            JOIN `zz_7538_part2_security_functions` bak ON bak.id = sf.id
            SET sf._view = bak._view
            WHERE sf.module = 'Institutions' AND sf.category = 'Academic'
              AND sf.name IN ('All Classes', 'All Subjects')
        ");

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_7538_part2_security_functions`');
    }
}
