<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if($_add) {
	$params = array_merge(array('action' => 'add', $selectedOption));
	echo $this->Html->link($this->Label->get('general.add'), $params, array('class' => 'divider'));
}
if($_edit && count($data) > 1) {
	$params = array_merge(array('action' => 'indexEdit', $selectedOption));
	echo $this->Html->link($this->Label->get('general.reorder'), $params, array('class' => 'divider'));
}
$this->end(); // end contentActions

$this->start('contentBody');
$this->Form->create('FieldOption', array('inputDefaults' => array('label'=>false, 'div'=>false, 'class'=>'default', 'autocomplete'=>'off', 'onchange'=>'jsForm.change(this)')));

$optionSelect = $this->Form->input('options', array('options'=>$options, 'default'=>$selectedOption, 'url'=>$this->params['controller'] . '/index'));	
echo $this->element('layout/row', array('rowClass'=>'category', 'rowBody'=>$optionSelect));

if(isset($data['subOptions'])) {
	$subOptionSelect = $this->Form->input('suboptions', array('options' => $subOptions,	'default'=>$selectedSubOption, 'url'=>$this->params['controller'] . '/fieldOption/' . $selectedOption));
	echo $this->element('layout/row', array('rowClass'=>'category', 'rowBody'=>$subOptionSelect));
}
$this->Form->end();
?>

<div class="table-responsive" style="margin-top: 10px;">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="col-visible" style="width: 60px;"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.option'); ?></th>
				<?php
				if(isset($fields)) {
					foreach($fields as $field => $value) {
						if($value['display']) {
							echo '<th>' . __($value['label']) . '</th>';
						}
					}
				}
				?>
			</tr>
		</thead>
		<tbody>
			<?php 
			if(!empty($data)) :
				foreach($data as $obj) :
			?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'view', $selectedOption, $obj[$model]['id'])); ?></td>
				<?php
				if(isset($fields)) {
					foreach($fields as $field => $value) {
						if($value['display']) {
							echo '<td>' .$obj[$model][$field] . '</td>';
						}
					}
				}
				?>
			</tr>
			<?php 
				endforeach;
			endif;
			?>
		</tbody>
	</table>
</div>
<?php $this->end(); // end contentBody ?>
