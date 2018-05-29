<?php

use Phinx\Migration\AbstractMigration;

class POCOR4613 extends AbstractMigration
{
    public function up()
    {
        $this->table('scholarship_recipient_disbursements')->rename('z_4613_scholarship_recipient_disbursements');

        // scholarship_recipient_disbursements
        $table = $this->table('scholarship_recipient_disbursements', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of disbursements linked to specific payment structure of scholarship recipient'
        ]);

        $table
            ->addColumn('disbursement_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('scholarship_semester_id', 'integer', [ 
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_semesters.id'
            ])
            ->addColumn('scholarship_disbursement_category_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to scholarship_disbursement_categories.id'
            ])
            ->addColumn('scholarship_recipient_payment_structure_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_recipient_payment_structures.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('scholarship_semester_id')
            ->addIndex('scholarship_disbursement_category_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {
        $this->dropTable('scholarship_recipient_disbursements');
        $this->table('z_4613_scholarship_recipient_disbursements')->rename('scholarship_recipient_disbursements');
    }
}
