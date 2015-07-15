<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<div class="clearfix">
	<?php
		echo $this->Form->input('Add Payment', [
			'label' => __('Payments'),
			'type' => 'button',
			'class' => 'btn btn-dropdown action-toggle btn-single-action',
			'aria-expanded' => 'true',
			'onclick' => "$('#reload').val('add').click();"
		]);
	?>
	</div>

	<div id="payments-table">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= __('Date') ?></th>
					<th><?= __('Comment') ?></th>
					<th><?= __('Amount ('.$attr['currency'].')') ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<?php if (isset($attr['data']) || isset($attr['paymentField'])) : ?>

			<tbody>
				<?php $i=0; ?>
				<?php foreach ($attr['data'] as $i=>$obj) : ?>
				<?php 
					$record = $obj;
				?>
				<tr>
					<?php foreach ($attr['fields'] as $key=>$field) : ?>

						<?php $field['attr']['name'] = $field['model'].'['.$i.']['.$field['field'].']';?>
						<?php $field['fieldName'] = $field['model'].'['.$i.']['.$field['field'].']';?>
						<?php $field['attr']['id'] = strtolower($field['model']).'-'.$i.'-'.$field['field'];?>
						<?php $field['attr']['value'] = $record->$field['field'];?>

						<?php if ($field['type']=='hidden') : ?>
							<?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?>
						<?php else: ?>
						
						<td><?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?></td>
						
						<?php endif; ?>
		
					<?php endforeach ?>
					
					<td> 
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button>
					</td>

				</tr>

				<?php endforeach ?>

				<?php if (isset($attr['paymentField']) && !empty($attr['paymentField'])) : ?>

				<tr>
					<?php foreach ($attr['fields'] as $key=>$field) : ?>

						<?php $field['attr']['name'] = $field['model'].'['.$i.']['.$field['field'].']';?>
						<?php $field['fieldName'] = $field['model'].'['.$i.']['.$field['field'].']';?>
						<?php $field['attr']['id'] = strtolower($field['model']).'-'.$i.'-'.$field['field'];?>
						<?php $field['attr']['value'] = $attr['paymentField']->$field['field'];?>

						<?php if ($field['type']=='hidden') : ?>
							<?= $this->HtmlField->{$field['type']}($action, $attr['paymentField'], $field, $field['attr']);?>
						<?php else: ?>
						
						<td><?= $this->HtmlField->{$field['type']}($action, $attr['paymentField'], $field, $field['attr']);?></td>
						
						<?php endif; ?>
		
					<?php endforeach ?>
					
					<td> 
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button>
					</td>

				</tr>

				<?php endif; ?>
	
			</tbody>
			
			<?php else : ?>

				<tr>&nbsp;</tr>
			
			<?php endif; ?>

		</table>
	</div>
</div>

<script>
// $(document).ready(function() {	
// 	$('#add-payment').on('click',
// 		function(){
// 			var tbody = $('#payments-table').find('table tbody');
// 			tbody.append('\
// 				<tr>\
// 					<td>type</td>\
// 					<td>type</td>\
// 					<td>type</td>\
// 					<td> \
// 						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">\
// 							Remove\
// 						</button>\
// 					</td>\
// 				</tr>\
// 			');
// 		}
// 	);
// });
</script>

<?php else : ?>

<div>
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?= __('Date') ?></th>
				<th><?= __('Created by') ?></th>
				<th><?= __('Comment') ?></th>
				<th><?= __('Amount ('.$attr['currency'].')') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (isset($attr['data']) && !empty($attr['data'])) :
			foreach ($attr['data'] as $i=>$obj) : ?>
			<tr>
				<td><?= $obj['type'] ?></td>
				<td><?= $obj['type'] ?></td>
				<td><?= $obj['type'] ?></td>
				<td class="cell-number"><?php echo $obj['amount'] ?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
	</table>
</div>

<?php endif ?>
