<?php
use Migrations\AbstractMigration;
class POCOR8100 extends AbstractMigration
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
        $this->execute("SET foreign_key_checks = 0");
        $this->execute("CREATE TABLE training_session_evaluators( id char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, training_session_id int NOT NULL COMMENT 'links to training_sessions.id', training_evaluators_id int NOT NULL COMMENT 'links to security_users.id') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all staff for all institution classes';");
        $this->execute("ALTER TABLE training_session_evaluators ADD PRIMARY KEY(training_session_id,training_evaluators_id), ADD KEY id (id), ADD KEY institution_session_id (training_session_id), ADD KEY training_evaluators_id (training_evaluators_id);");
        $this->execute("ALTER TABLE training_session_evaluators ADD CONSTRAINT tse_training_evaluators_id FOREIGN KEY(training_evaluators_id) REFERENCES security_users (id) ON DELETE RESTRICT ON UPDATE RESTRICT, ADD CONSTRAINT tse_training_session_id FOREIGN KEY (training_session_id) REFERENCES training_sessions (id) ON DELETE RESTRICT ON UPDATE RESTRICT; COMMIT;");
        $this->execute("SET foreign_key_checks = 1");

    }
    public function down()
    {
        $this->execute("SET foreign_key_checks = 0");
        $this->execute('DROP TABLE IF EXISTS `training_session_evaluators`');
        $this->execute("SET foreign_key_checks = 1");
    }
}
