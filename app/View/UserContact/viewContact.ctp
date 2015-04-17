<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="table_cell"><?php echo __('Description'); ?></th>
				<th class="table_cell"><?php echo __('Value'); ?></th>
				<th class="table_cell"><?php echo __('Preferred'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $key=>$value) : ?>
			<tr>
				<td class="table_cell"><?php echo $value['ContactType']['name'] . ' - ' . $value['ContactType']['ContactOption']['name']; ?></td>
				<td class="table_cell"><?php echo $value['value']; ?></td>
				<td class="table_cell"><?php echo $this->Utility->checkOrCrossMarker($value['preferred']==1); ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>