<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9864
 *
 * Adds execution logging for scheduled report queries, to track which
 * report_queries were triggered and for easier troubleshooting.
 *
 * 1. Creates `report_query_logs` -- one row per executed report_queries entry
 *    (start/end time, status RUNNING/SUCCESS/FAILED, rows affected, MySQL error).
 * 2. Replaces the `openemis_core_reports` procedure (called by the
 *    openemis_core_minute/hour/day/week/month/year events) with a version that
 *    writes to that table. A failing query is logged as FAILED and the
 *    procedure continues with the next one, same as before.
 *
 * Differences from the SQL attached to the ticket (agreed with reporter):
 * - No DEFINER: `root`@`%` does not exist on every server; the procedure is
 *   owned by the migration's DB user, as with the existing procedure.
 * - No DELIMITER: client-only syntax, not valid through PDO.
 * - utf8mb4 instead of latin1: report_queries is utf8, so a non-latin1
 *   character in a query's SQL would make the log INSERT fail and stop the
 *   whole run. Environments where this table was already created by hand
 *   (prd_cor_jm_moe) keep their data and are converted in place.
 */
class POCOR9864 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `report_query_logs` (
            `id` bigint NOT NULL AUTO_INCREMENT,
            `executed_at` datetime DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `frequency` varchar(50) DEFAULT NULL,
            `query_sql` longtext,
            `status` varchar(20) DEFAULT NULL,
            `rows_affected` int DEFAULT 0,
            `error_message` longtext,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // No-op on a freshly created table; converts a pre-existing latin1 table.
        $this->execute('ALTER TABLE `report_query_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        $this->execute('DROP PROCEDURE IF EXISTS `openemis_core_reports`');

        $this->execute(<<<'SQL'
CREATE PROCEDURE `openemis_core_reports`(IN `var_interval` VARCHAR(50))
BEGIN

    DECLARE var_row LONGTEXT;
    DECLARE var_done INT DEFAULT FALSE;

    DECLARE var_log_id BIGINT;
    DECLARE var_error TEXT DEFAULT NULL;

    DECLARE var_cursor CURSOR FOR
        SELECT query_sql
        FROM report_queries
        WHERE status = 1
          AND frequency = var_interval
        ORDER BY id ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND
        SET var_done = TRUE;

    -- Same as the previous procedure (POCOR-7230): an error not handled by an
    -- inner block (e.g. a failed log UPDATE) must not abort the whole run.
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        SET var_done = FALSE;

    OPEN var_cursor;

    read_loop: LOOP

        FETCH var_cursor INTO var_row;

        IF var_done THEN
            LEAVE read_loop;
        END IF;

        SET var_error = NULL;
        SET var_log_id = NULL;

        -- If the log row cannot be written, still run the query, just without
        -- logging. EXIT (not CONTINUE) so LAST_INSERT_ID() is never read after a
        -- failed INSERT -- it would still hold the previous query's log id.
        BEGIN

            DECLARE EXIT HANDLER FOR SQLEXCEPTION
                SET var_log_id = NULL;

            INSERT INTO report_query_logs (
                executed_at,
                frequency,
                query_sql,
                status
            )
            VALUES (
                NOW(),
                var_interval,
                var_row,
                'RUNNING'
            );

            SET var_log_id = LAST_INSERT_ID();

        END;

        BEGIN

            DECLARE stmt_prepared BOOLEAN DEFAULT FALSE;

            DECLARE EXIT HANDLER FOR SQLEXCEPTION
            BEGIN

                GET DIAGNOSTICS CONDITION 1
                    var_error = MESSAGE_TEXT;

                IF stmt_prepared THEN
                    DEALLOCATE PREPARE report_query;
                END IF;

                UPDATE report_query_logs
                SET
                    completed_at = NOW(),
                    status = 'FAILED',
                    error_message = var_error
                WHERE id = var_log_id;

            END;

            SET @var_row = var_row;

            PREPARE report_query FROM @var_row;
            SET stmt_prepared = TRUE;

            EXECUTE report_query;

            SET @rows_affected = ROW_COUNT();

            DEALLOCATE PREPARE report_query;
            SET stmt_prepared = FALSE;

            UPDATE report_query_logs
            SET
                completed_at = NOW(),
                status = 'SUCCESS',
                rows_affected = @rows_affected
            WHERE id = var_log_id;

        END;

    END LOOP;

    CLOSE var_cursor;

END
SQL
        );
    }

    // rollback
    public function down(): void
    {
        // Restore the previous procedure (POCOR-7230). report_query_logs is kept
        // intentionally so existing log data is not lost.
        $this->execute('DROP PROCEDURE IF EXISTS `openemis_core_reports`');

        $this->execute('CREATE PROCEDURE `openemis_core_reports`(IN `var_interval` VARCHAR(10)) NO SQL BEGIN DECLARE var_row TEXT; DECLARE var_done INT DEFAULT FALSE; DECLARE var_cursor CURSOR FOR SELECT query_sql FROM report_queries WHERE status = 1 AND frequency LIKE var_interval ORDER BY id ASC; DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_done = TRUE; DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET var_done = FALSE; OPEN var_cursor; read_loop: LOOP FETCH var_cursor INTO var_row; IF var_done THEN LEAVE read_loop; END IF; SET @var_row = var_row; PREPARE report_query FROM @var_row; EXECUTE report_query; DEALLOCATE PREPARE report_query; END LOOP; CLOSE var_cursor; END ;');
    }
}
