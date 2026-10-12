<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9847
 *
 * NOTE on root cause: the "Malformed UTF-8 characters" 500 actually reported against
 * /core/api/v4/scanned was traced to the Laravel API eager-loading the related
 * security_users row -- including photo_content (a longblob of raw binary image
 * bytes) -- and json_encode()-ing it (fixed in ScannedRepository.php, see that
 * file's POCOR-9847 comments). Direct DB validation on the affected environment
 * found zero rows with actually-corrupted bytes in institution_scanned.access/
 * location at the time of investigation.
 *
 * This migration is a separate, real fix, not the fix for that reported 500:
 * `institution_scanned` was created (POCOR-8666) with ENGINE=InnoDB DEFAULT
 * CHARSET=utf8 (utf8mb3), while the rest of the schema defaults to utf8mb4.
 * utf8mb3 cannot store 4-byte UTF-8 sequences, so data written via the scanning
 * hardware/app (e.g. emoji in access/location) would get silently corrupted into
 * invalid byte sequences going forward, which would independently trip the same
 * class of json_encode() failure the next time such a value was written. This
 * converts `access` and `location` to utf8mb4 to match the rest of the schema and
 * prevent that future corruption -- it does not repair any row already corrupted
 * before this migration runs (none were found on validation, but this is a schema
 * change only, not a data repair; re-run the detection query from the POCOR-9847
 * SQL patch script if that's in question on a given environment).
 *
 * `openemis_no` is intentionally left on utf8mb3: it's a foreign key to
 * security_users.openemis_no, and MySQL refuses to change a referencing column's
 * charset unless the referenced column matches (confirmed: ERROR 3780,
 * "Referencing column 'openemis_no' and referenced column 'openemis_no' in
 * foreign key constraint 'institution_scanned_ibfk_1' are incompatible").
 * Converting security_users itself is out of scope here -- it's referenced by
 * many other foreign keys across the schema -- and openemis_no is a structured ID
 * rather than free text, so it isn't a source of this kind of corruption anyway.
 */
class POCOR9847 extends AbstractMigration
{
    public function up(): void
    {
        // POCOR-9847: DROP + CREATE + INSERT (rather than CREATE IF NOT EXISTS followed
        // by an unconditional INSERT) so this is safe to re-run after a partial earlier
        // attempt -- otherwise a backup table left over from a prior failed run would
        // make the INSERT hit a primary-key duplicate before ever reaching the ALTER.
        $this->execute('DROP TABLE IF EXISTS `z_9847_institution_scanned`');
        $this->execute('CREATE TABLE `z_9847_institution_scanned` LIKE `institution_scanned`');
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
