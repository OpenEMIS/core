<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9847
 * `institution_scanned` was created (POCOR-8666) with ENGINE=InnoDB DEFAULT CHARSET=utf8 (utf8mb3),
 * while the rest of the schema defaults to utf8mb4. utf8mb3 cannot store 4-byte UTF-8 sequences, so
 * data written via the scanning hardware/app (e.g. emoji or non-BMP characters in access/location)
 * gets silently corrupted into invalid byte sequences. When the Laravel API later reads those rows
 * and json_encode()s them, PHP raises "Malformed UTF-8 characters, possibly incorrectly encoded"
 * and the /core/api/v4/scanned endpoints return a 500.
 *
 * This migration converts `access` and `location` to utf8mb4 to match the rest of the schema and
 * stop new corruption from occurring.
 *
 * `openemis_no` is intentionally left on utf8mb3: it's a foreign key to security_users.openemis_no,
 * and MySQL refuses to change a referencing column's charset unless the referenced column matches
 * (confirmed: ERROR 3780, "Referencing column 'openemis_no' and referenced column 'openemis_no' in
 * foreign key constraint 'institution_scanned_ibfk_1' are incompatible"). Converting security_users
 * itself is out of scope here -- it's referenced by many other foreign keys across the schema --
 * and openemis_no is a structured ID rather than free text, so it isn't the source of the corruption
 * anyway.
 */
class POCOR9847 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9847_institution_scanned` LIKE `institution_scanned`');
        $this->execute('INSERT INTO `z_9847_institution_scanned` SELECT * FROM `institution_scanned`');

        $this->execute(
            'ALTER TABLE `institution_scanned`
                MODIFY `access`   VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                MODIFY `location` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
    }

    // rollback
    public function down(): void
    {
        $this->execute(
            'ALTER TABLE `institution_scanned`
                MODIFY `access`   VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
                MODIFY `location` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci'
        );
        $this->execute('DROP TABLE IF EXISTS `z_9847_institution_scanned`');
    }
}
