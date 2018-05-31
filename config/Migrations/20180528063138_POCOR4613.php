<?php

use Phinx\Migration\AbstractMigration;

class POCOR4613 extends AbstractMigration
{
   public function up()
    {
        $this->execute('ALTER TABLE `scholarship_recipient_disbursements` ADD COLUMN `scholarship_recipient_payment_structure_id` int(11) NOT NULL COMMENT "links to scholarship_recipient_payment_structures.id" AFTER `scholarship_disbursement_category_id`');
        
        $ScholarshipRecipientDisbursements = $this->table('scholarship_recipient_disbursements');
        $ScholarshipRecipientDisbursements
            ->addIndex('scholarship_recipient_payment_structure_id')
            ->save();

       // download permission     
       $this->execute("UPDATE security_functions 
                    SET `_view` = 'index|view|download' 
                    WHERE `id` = 5099");    
    }

    public function down()
    {
        $this->execute('ALTER TABLE `scholarship_recipient_disbursements` DROP COLUMN `scholarship_recipient_payment_structure_id`');

        $ScholarshipRecipientDisbursements = $this->table('scholarship_recipient_disbursements');
        $ScholarshipRecipientDisbursements->removeIndex('scholarship_recipient_payment_structure_id');

        $this->execute("UPDATE security_functions 
                        SET `_view` = 'index|view' 
                        WHERE `id` = 5099");    
    }
}
