<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array('action' => 'index', $selectedOption);
if(isset($conditionId)) {
	$params = array_merge($params, array($conditionId => $selectedSubOption));
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'delete',$selectedOption,$selectedValue));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('FieldOptionValue', $formOptions);

echo 'From: '.$currentFieldOptionValue['FieldOptionValue']['name'].'<br>';

echo $this->Form->input('convert', array(
	'class' => 'form-control',
	'options' => $allOtherFieldOptionValues
));


echo 'Applying on... <br>';
foreach ($modifyForeignKey as $key => $value) {
	echo $value.' records in '.$key;
	echo '<br>';
}

?>
<div class="form-group">
	<div class="col-md-offset-4">
		<input type="submit" value="<?php echo $this->Label->get('general.delete'); ?>" class="btn_save btn_right"/>
		<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'view',$selectedOption,$selectedValue), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>
<?php 
echo $this->Form->end();
$this->end();
?>