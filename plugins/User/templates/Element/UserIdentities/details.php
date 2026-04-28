<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>
<?php
	//echo "<pre>"; print_r($_SESSION['Directory']);
	//echo "<pre>"; print_r($attr['data']);

?>

<?php
//POCOR-9590: getViewUserIdentities now returns ['data' => [...], 'sync_status' => N]
$identityRows = !empty($attr['data']['data']) ? $attr['data']['data'] : [];
$syncStatus   = isset($attr['data']['sync_status']) ? $attr['data']['sync_status'] : 0;
?>
<?php if (!empty($identityRows)) { ?>
	<div class="form-input table-full-width">
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead>
						<tr>
							<th><?= __('Identity Type'); ?></th>
							<th><?= __('Identity Number'); ?></th>
							<th><?= __('Nationality'); ?></th>
							<th><?= __('Preferred'); ?></th>
							<th><?= __('Synced'); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach($identityRows as $index) { ?>
						<tr>
							<td class="vertical-align-top"><?php if(isset($index['identity_types']) && !empty($index['identity_types']['name'])) { echo $index['identity_types']['name']; } else { echo ''; } ?></td>
							<td class="vertical-align-top"><?php echo !empty($index['number']) ? $index['number'] : ''; ?></td>
							<td class="vertical-align-top"><?php if(isset($index['nationalities']) && !empty($index['nationalities']['name'])) { echo $index['nationalities']['name']; } else { echo ''; } ?></td>
							<td class="vertical-align-top"><?php if($index['preferred'] == 1){ echo 'Yes'; } else{ echo 'No'; } ?></td>
							<?php //POCOR-9590: Synced = preferred identity AND user has been synced ?>
							<td class="vertical-align-top"><?php if($index['preferred'] == 1 && !empty($syncStatus)){ echo '<span class="label label-success">Yes</span>'; } else { echo '<span class="label label-default">No</span>'; } ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php } ?>
