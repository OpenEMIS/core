<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>

<?php
//POCOR-9590: getViewUserIdentities returns ['data' => [...], 'sync_status' => 0|1|2, 'active_source_identity_type_id' => int|null]
$identityRows  = !empty($attr['data']['data']) ? $attr['data']['data'] : [];
$syncStatus    = isset($attr['data']['sync_status']) ? (int)$attr['data']['sync_status'] : \User\Model\Behavior\UserBehavior::SYNC_STATUS_LOCAL;
$activeTypeId  = isset($attr['data']['active_source_identity_type_id']) ? $attr['data']['active_source_identity_type_id'] : null;

//POCOR-9590: per-row badge — Yes (synced) / No (drifted or not eligible) badge per row
$renderStatusBadge = function ($row) use ($syncStatus, $activeTypeId) {
    $rowTypeId = isset($row['identity_type_id']) ? (int)$row['identity_type_id'] : null; //POCOR-9590: read user_identities.identity_type_id (selected) instead of identity_types.id (not selected)
    $eligible  = ($row['preferred'] == 1) && ($activeTypeId !== null) && ($rowTypeId === $activeTypeId);
    if (!$eligible) {
        return '<span class="label label-default">' . __('No') . '</span>'; //POCOR-9590: grey
    }
    if ($syncStatus === \User\Model\Behavior\UserBehavior::SYNC_STATUS_SYNCED) {
        return '<span class="label label-success">' . __('Yes') . '</span>'; //POCOR-9590: green
    }
    if ($syncStatus === \User\Model\Behavior\UserBehavior::SYNC_STATUS_DRIFTED) {
        return '<span class="label label-warning">' . __('No') . '</span>'; //POCOR-9590: orange
    }
    return '<span class="label label-default">' . __('No') . '</span>'; //POCOR-9590: fallback grey when stored = 0
};
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
							<td class="vertical-align-top"><?= $renderStatusBadge($index); ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php } ?>
