<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-bordered" style="width: 50%;">
			<tbody>
				<tr>
					<td style="background-color: <?php echo $data['RubricTemplateOption']['color']; ?>;"></td>
				</tr>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo $this->Label->get('RubricTemplateOption.color');?></label>
		<div class="col-md-6">
			<?php echo $this->Form->input('color', array('type' => 'color', 'onchange' => 'clickColor(0, -1, -1, 5);', 'style' => 'width: 50%;', 'label' => false, 'div' => false, 'between' => false, 'after' => false)); ?>
		</div>
	</div>
<?php endif ?>
