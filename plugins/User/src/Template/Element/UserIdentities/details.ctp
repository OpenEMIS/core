<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>
<?php 
	//echo "<pre>"; print_r($_SESSION['Directory']); 
	//echo "<pre>"; print_r($attr['data']); 
	
?>

<?php if (!empty($attr['data'])) { ?>
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
						</tr>
					</thead>
					
					<tbody>
						<?php foreach($attr['data'] as $index) { ?>
						<tr>
							<td class="vertical-align-top"><?php if(isset($index['identity_types']) && !empty($index['identity_types']['name'])) { echo $index['identity_types']['name']; } else { echo ''; } ?></td>
							<td class="vertical-align-top"><?php echo !empty($index['number']) ? $index['number'] : ''; ?></td>
							<td class="vertical-align-top"><?php if(isset($index['nationalities']) && !empty($index['nationalities']['name'])) { echo $index['nationalities']['name']; } else { echo ''; } ?></td>
							<td class="vertical-align-top"><?php if($index['user_nationalities']['preferred'] == 1){ echo 'Yes'; } else{ echo 'No'; } ?></td>
						</tr>
						<?php } ?>
					</tbody>				
				</table>
			</div>
		</div>
	</div>
<?php } ?>