<?php
use Migrations\AbstractMigration;

class POCOR4824 extends AbstractMigration
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

        $table = $this->table('language_proficiencies', [
            'collation' => 'utf8_general_ci',
            'comment' => 'This table contains language proficiencies'
        ]);

        $table
            ->addColumn('name', 'string', array('limit' => 50))
            ->addColumn('order', 'integer', array('limit' => 10))
            ->addColumn('visible', 'integer', array('limit' => 10, 'default' => '1'))
            ->addColumn('editable', 'integer', array('limit' => 10, 'default' => '1'))
            ->addColumn('default', 'integer', array('limit' => 10, 'default' => '0'))
            ->addColumn('international_code', 'string', array('limit' => 50, 'default' => NULL, 'null' => true))
            ->addColumn('national_code', 'string', array('limit' => 50, 'default' => NULL, 'null' => true))

            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => 1,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->save();

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DROP TABLE language_proficiencies');
    }
}
