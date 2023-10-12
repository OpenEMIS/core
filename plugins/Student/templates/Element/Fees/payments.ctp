<div class="table-wrapper">
	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<th><?= $attr['fields']['payment_date']['tableHeader'] ?></th>
					<th><?= $attr['fields']['created_user_id']['tableHeader'] ?></th>
					<th><?= $attr['fields']['comments']['tableHeader'] ?></th>
					<th class="text-right"><?= $attr['fields']['amount']['tableHeader'] ?></th>
				</tr>
			</thead>
			<tbody>
			<?php 
			if (isset($attr['data']) && !empty($attr['data'])) :
				foreach ($attr['data'] as $key=>$record) : ?>

				<tr>
					<?php $record->created_user_id = $record->created_by->name; ?>

					<td><?= $this->HtmlField->date($action, $record, $attr['fields']['payment_date'], []);?></td>
					<td><?= $this->HtmlField->string($action, $record, $attr['fields']['created_user_id'], []);?></td>
					<td><?= $this->HtmlField->text($action, $record, $attr['fields']['comments'], []);?></td>
					<td class="text-right"><?= $this->HtmlField->float($action, $record, $attr['fields']['amount'], []);?></td>
				</tr>
			<?php
				endforeach;
			endif;
			?>
			</tbody>
			<?php if (isset($attr['data']) && !empty($attr['data'])) : ?>
			
			<tfoot>
				<td></td>
				<td></td>
				<td class="bold"><?php echo $this->Label->get('general.total') ?></td>
				<td class="text-right bold"><?php echo $attr['total'] ?></td>
			</tfoot>
			
			<?php endif;?>
		</table>
	</div>
</div>
