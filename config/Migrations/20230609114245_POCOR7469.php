<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7469 extends AbstractMigration
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

         // Backup student_behaviours table
         $this->execute('DROP TABLE IF EXISTS `zz_7469_student_behaviours`');
         $this->execute('CREATE TABLE `zz_7469_student_behaviours` LIKE `student_behaviours`');
         $this->execute('INSERT INTO `zz_7469_student_behaviours` SELECT * FROM `student_behaviours`');
        
         //workflow_models
        $this->execute('DROP TABLE IF EXISTS `zz_7469_workflow_models`');
        $this->execute('CREATE TABLE `zz_7469_workflow_models` LIKE `workflow_models`');
        $this->execute('INSERT INTO `zz_7469_workflow_models` SELECT * FROM `workflow_models`');

         // Backup workflows table
         $this->execute('DROP TABLE IF EXISTS `zz_7469_workflows`');
         $this->execute('CREATE TABLE `zz_7469_workflows` LIKE `workflows`');
         $this->execute('INSERT INTO `zz_7469_workflows` SELECT * FROM `workflows`');
 
         // Backup workflow_steps table
         $this->execute('DROP TABLE IF EXISTS `zz_7469_workflow_steps`');
         $this->execute('CREATE TABLE `zz_7469_workflow_steps` LIKE `workflow_steps`');
         $this->execute('INSERT INTO `zz_7469_workflow_steps` SELECT * FROM `workflow_steps`');




        $WorkFlowModelT = TableRegistry::get('workflow_models');
        $WorkFlowT = TableRegistry::get('workflows');
        $WorkFlowStepT = TableRegistry::get('workflow_steps');

        $workFlowModel = $WorkFlowModelT->find()->where(['name'=>'Institutions > Behaviour > Students'])->first();
        $workFlow = $WorkFlowT->find()->where(['workflow_model_id'=> $workFlowModel->id])->first();
        $workFlowStep = $WorkFlowStepT->find()->where(['workflow_id'=> $workFlow->id,'name'=>'Open'])->first();

        $this->execute("UPDATE student_behaviours SET student_behaviours.status_id = $workFlowStep->id");
    }


     //rollback
     public function down()
     {
         $this->execute('DROP TABLE IF EXISTS `student_behaviours`');
         $this->execute('RENAME TABLE `zz_7469_student_behaviours` TO `student_behaviours`');
 
         $this->execute('DROP TABLE IF EXISTS `workflows`');
         $this->execute('RENAME TABLE `zz_7469_workflows` TO `workflows`');
 
         $this->execute('DROP TABLE IF EXISTS `workflow_steps`');
         $this->execute('RENAME TABLE `zz_7469_workflow_steps` TO `workflow_steps`');
 
         $this->execute('DROP TABLE IF EXISTS `workflow_models`');
         $this->execute('RENAME TABLE `zz_7469_workflow_models` TO `workflow_models`');  
     }
}
