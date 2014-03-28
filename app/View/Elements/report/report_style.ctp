<?php
$outputOptions = array(
	'html' => 'HTML',
	'xls' => 'Excel'
);
$typeOptions = array(
	0 => __('System Report'),
	1 => __('My Report')
);
?>
<div style="margin-top: 10px; padding-left: 5px;">
<div class="row">
	<div class="label"><?php echo __('Report Name'); ?></div>
	<div class="value"><?php echo $this->Form->input('ReportName', array('label' => false, 'class' => 'default')); ?></div>
</div>
<div class="row">
	<div class="label"><?php echo __('Description'); ?></div>
	<div class="value"><?php echo $this->Form->textarea('ReportDescription', array('label' => false, 'class' => 'default')); ?></div>
</div>
<div class="row">
	<div class="label"><?php echo __('Output'); ?></div>
	<div class="value"><?php echo $this->Form->input('Output', array('label' => false, 'class' => 'default', 'options' => $outputOptions)); ?></div>
</div>
<div class="row">
	<div class="label"><?php echo __('Type'); ?></div>
	<div class="value"><?php echo $this->Form->input('Type', array('label' => false, 'class' => 'default', 'options' => $typeOptions)); ?></div>
</div>
<?php 
echo __d('report_manager','Save report');
echo $this->Form->checkbox('SaveReport', array('hiddenField' => true, 'checked' => true));
?>
</div>


<!--table class="reportManagerReportStyleSelector" cellpadding="0" cellspacing="0">
<?php
$outputOptions = array(
	'html' => 'HTML',
	'xls' => 'Excel'
);

echo '<tr>';
echo '<td>';
echo $this->Form->input('ReportName',array('size'=>'80','maxlength'=>'80'));            
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>';
echo $this->Form->input('Output',array('type'=>'select','options'=>$outputOptions));            
echo '</td>';             
echo '</tr>';

if ($oneToManyOption != '') {
	echo '<tr>';
	echo '<td>';
	echo __d('report_manager','Show items with no related records');
	if (isset($this->data['Report']['ShowNoRelated']))
		$showNoRelated = $this->data['Report']['ShowNoRelated'];
	else
		$showNoRelated = false;
	echo $this->Form->checkbox('ShowNoRelated',array('hiddenField' => true,'checked'=>$showNoRelated));
	echo '</td>';             
	echo '</tr>';
}

echo '<tr>';
echo '<td>';
echo __d('report_manager','Save report');         
echo $this->Form->checkbox('SaveReport',array('hiddenField' => true, 'checked'=>true));                     
echo '</td>';             
echo '</tr>';            

?>
</table-->