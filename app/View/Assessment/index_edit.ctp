<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);
//echo $this->Html->script('jquery.quicksand', false);
//echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('field.option', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Assessments'));
$this->start('contentActions');
if ($_edit && !empty($data)) {
	echo $this->Html->link(__('List'), array('action' => 'index', $selectedProgramme), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div id="assessment" class="content_wrapper">
	<?php
	echo $this->Form->create('Assessment', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'Assessment', 'action' => 'indexEdit', $selectedProgramme)
	));
	?>
	<div class="filter_wrapper">
		<div class="row edit">
			<label class="control-label col-md-3"><?php echo __('Education Programme'); ?></label>
			<div class="col-md-4">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'class' => 'form-control',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'url' => 'Assessment/indexEdit/',
					'onchange' => 'Assessment.switchProgramme(this)'
				));
				?>
			</div>
		</div>
	</div>

	<?php
	$i = 0;
	foreach ($data as $key => $obj) {
		?>
		<fieldset class="section_group">
			<legend><?php echo $obj['name']; ?></legend>
			<table class="table table-striped table-hover table-bordered">
				<thead>
				<th class="cell_visible"><?php echo __('Status'); ?></th>
				<th class="cell_code"><?php echo __('Code'); ?></th>
				<th class=""><?php echo __('Name'); ?></th>
				<th class="cell_order"><?php echo __('Order'); ?></th>
				</thead>
				<tbody>
					<?php
					$items = $obj['assessment'][$type];
					if (!empty($items)) :
						$index = 1;
						$fieldindex = 0;
						foreach ($items as $item) :
							$isVisible = $item['visible'] == 1;
							?>
							<tr row-id="<?php echo $item['id']; ?>">
								<td>
									<?php 
									echo $this->Form->hidden('id', array(
										'name' => 'data[AssessmentItemType][' . $fieldindex . '][id]',
										'value' => $item['id']
									));
									$options = array(
										'name' => 'data[AssessmentItemType][' . $fieldindex . '][visible]',
										'type' => 'checkbox',
										'value' => 1,
										'autocomplete' => 'off',
										'div' => false,
										'label' => false
									);
									if ($isVisible) {
										$options['checked'] = 'checked';
									}
									echo $this->Form->input('visible', $options);
									?></td>
								<td><?php echo $item['code']; ?></td>
								<td><?php echo $item['name']; ?></td>
								<td class="action">
									<?php
									$size = count($items);
									echo $this->element('layout/reorder', compact('index', 'size'));
									$index++;
									?>
								</td>
							</tr>
							<?php
							$fieldindex++;
						endforeach;
					endif;
					?>
				</tbody>
			</table>
		</fieldset>
	

	<?php } ?>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index', $selectedProgramme), array('class' => 'btn_cancel btn_left')); ?>
	</div>

	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
