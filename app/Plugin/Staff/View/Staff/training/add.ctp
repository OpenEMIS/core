<?php /*
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>

<!-- <div class="table_row"> -->
<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
	<input type="hidden" value="0" name="data[TeacherTraining][<?php echo $index;?>][id]" />
    <div class="table_cell">
        <?php 
            echo $this->Utility->getDatePicker($this->Form, 'completed_date', array('name'=> 'data[TeacherTraining]['.$index.'][completed_date]','desc' => true)); 
        ?>
        <?php //echo $this->Utility->getDatePicker($this->Form, 'completed_date',array('name'=> 'data[TeacherTraining]['.$index.'][completed_date]', 'desc' => true)); ?>
    </div>
    <div class="table_cell">
        <?php echo $this->Form->input('TeacherTraining]['.$index.'][teacher_training_category_id', array('label' => false, 'class' => 'training_category', 'options' => $categories)); ?>
    </div>
    <div class="table_cell">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="Training.removeRow(this)"></span>
    </div>
</div>
 * 
 */?>

<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'trainingView', $this->data[$model]['id']);
		$setDate = array('data-date' => $this->data[$model]['completed_date']);
    }
    else{
        $redirectAction = array('action' => 'training');
		$setDate = null;
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('completed_date', $setDate);
echo $this->Form->input('staff_training_category_id', array('options'=>$categoryOptions, 'label'=>array('text'=> $this->Label->get('general.category'),'class'=>'col-md-3 control-label')));
echo $this->FormUtility->getFormButtons(array('cancelURL' =>$redirectAction));


echo $this->Form->end();
$this->end();
?>
