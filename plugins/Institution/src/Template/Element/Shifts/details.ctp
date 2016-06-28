<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>

<?php if ($action == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<th>Shift</th>
						<th>Start Time</th>
						<th>End Time</th>
						<th>Owner</th>
						<th>Occupier</th>
					</tr>
				</thead>
				
				<tbody>
					<?php foreach($attr['data'] as $index) { ?>
					<tr>
						<td class="vertical-align-top"><?php echo $index['Shift']; ?></td>
						<td class="vertical-align-top"><?php echo $index['StartTime']; ?></td>
						<td class="vertical-align-top"><?php echo $index['EndTime']; ?></td>
						<td class="vertical-align-top"><?php echo $index['Owner']; ?></td>
						<td class="vertical-align-top"><?php echo $index['Occupier']; ?></td>
					</tr>
					<?php } ?>
				</tbody>				
			</table>
		</div>
	</div>
<?php endif ?>