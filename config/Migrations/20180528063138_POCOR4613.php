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
      
        //export for scholarship
        $this->execute("UPDATE security_functions 
                    SET `_execute` = 'Scholarships.excel' 
                    WHERE `id` = 5090");   

        // download permission
       $this->execute("UPDATE security_functions 
                    SET `_view` = 'index|view|download' 
                    WHERE `id` = 5099");   
       // edit permission for Scholarship Recipients
        $this->execute("UPDATE security_functions 
                    SET `_edit` = 'edit' 
                    WHERE `id` = 5100");   

        $this->insert('security_functions', [
                'id' => 5101,
                'name' => 'Institution Choices',
                'controller' => 'ScholarshipRecipientInstitutionChoices',
                'module' => 'Administration',
                'category' => 'Scholarships - Recipients',
                'parent_id' => 5000,
                '_view' => 'index|view',
                '_edit' => 'edit',
                '_add' => 'add',
                '_delete' => 'delete',
                'order' => 330,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
        ]);

        $this->insert('security_functions', [
            'id' => 5102,
            'name' => 'Payment Structures',
            'controller' => 'Scholarships',
            'module' => 'Administration',
            'category' => 'Scholarships - Recipients',
            'parent_id' => 5000,
            '_view' => 'RecipientPaymentStructures.index|RecipientPaymentStructures.view',
            '_edit' => 'RecipientPaymentStructures.edit',
            '_add' => 'RecipientPaymentStructures.add',
            '_delete' => 'RecipientPaymentStructures.remove',
            'order' => 331,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->insert('security_functions', [
            'id' => 5103,
            'name' => 'Disbursements',
            'controller' => 'Scholarships',
            'module' => 'Administration',
            'category' => 'Scholarships - Recipients',
            'parent_id' => 5000,
            '_view' => 'RecipientPayments.index|RecipientPayments.view',
            '_edit' => 'RecipientPayments.edit',
            '_add' => null,
            '_delete' => null,
            'order' => 332,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->insert('security_functions', [
            'id' => 5104,
            'name' => 'Collections',
            'controller' => 'ScholarshipRecipientCollections',
            'module' => 'Administration',
            'category' => 'Scholarships - Recipients',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 333,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->insert('security_functions', [
            'id' => 5105,
            'name' => 'Academic Standings',
            'controller' => 'ScholarshipRecipientAcademicStandings',
            'module' => 'Administration',
            'category' => 'Scholarships - Recipients',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 334,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('ALTER TABLE `scholarship_recipient_disbursements` DROP COLUMN `scholarship_recipient_payment_structure_id`');

        $ScholarshipRecipientDisbursements = $this->table('scholarship_recipient_disbursements');
        $ScholarshipRecipientDisbursements->removeIndex('scholarship_recipient_payment_structure_id');

        $this->execute("UPDATE security_functions 
                    SET `_execute` = null 
                    WHERE `id` = 5090");   
        
        $this->execute("UPDATE security_functions 
                    SET `_view` = 'index|view' 
                    WHERE `id` = 5099");   

        $this->execute("UPDATE security_functions 
                    SET `_edit` = null 
                    WHERE `id` = 5100");   

        $this->execute('DELETE FROM security_functions WHERE id = 5101');
        $this->execute('DELETE FROM security_functions WHERE id = 5102');
        $this->execute('DELETE FROM security_functions WHERE id = 5103');
        $this->execute('DELETE FROM security_functions WHERE id = 5104');
        $this->execute('DELETE FROM security_functions WHERE id = 5105');
    }
}
