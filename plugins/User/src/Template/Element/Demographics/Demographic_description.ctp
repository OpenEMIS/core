<?php use Cake\Utility\Inflector;?>

	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<?php foreach ($attr['formFields'] as $formField) : ?>
						<th><?= __(Inflector::humanize(str_replace('_id', '', $formField))) ?></th>
					<?php endforeach;?>
				</tr>
			</thead>
			<tbody>
			<?php
				// iterate each row
				foreach ($attr['fields']['description'] as $key => $record) :
			?>
				<tr>
					<td><?php echo $key ?></td>
					<td><?php echo $record ?></td>
				</tr>
			<?php
				endforeach;
			?>
			</tbody>
		</table>
	</div>

