<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Log;
class POCOR9727 extends AbstractMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void
    {
        // 1. backup report_card_processes tables
        $this->execute("CREATE TABLE zz_9727_report_card_processes LIKE report_card_processes");

        $this->execute("INSERT INTO zz_9727_report_card_processes SELECT * FROM report_card_processes");
        // 2. backup institution_students_report_cards tables
        $this->execute("CREATE TABLE zz_9727_institution_students_report_cards LIKE institution_students_report_cards");

        $this->execute("INSERT INTO zz_9727_institution_students_report_cards SELECT * FROM institution_students_report_cards");
        $table = $this->table('report_card_processes');

        if (!$table->hasIndex(['status'])) {
            $table->addIndex(['status'], [
                'name' => 'idx_report_card_processes_status'
            ])->update();
        }

        $table = $this->table('institution_students_report_cards');

        if (!$table->hasIndex(['status'])) {
            $table->addIndex(['status'], [
                'name' => 'idx_inst_students_report_cards_status'
            ])->update();
        }
    }

    /**
     * Reverse order of {{up()}}: revoke grants first, then drop nav rows,
     * then drop the runtime tables. Backup tables are intentionally NOT
     * restored — surgical deletes keep any concurrently-added rows safe.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS zz_9727_report_card_processes");
        $this->execute("RENAME TABLE zz_9727_report_card_processes TO report_card_processes");
        $this->execute("DROP TABLE IF EXISTS zz_9727_institution_students_report_cards");
        $this->execute("RENAME TABLE zz_9727_institution_students_report_cards TO institution_students_report_cards");
    }
}
