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
							<th><?= __('Need Name'); ?></th>
						</tr>
					</thead>
					
					<tbody>
						<?php foreach($attr['data'] as $index) { ?>
						<tr>
							<td class="vertical-align-top"><?php if(isset($index['link']) && !empty($index['link'])) { echo $index['link']; } else { echo ''; } ?></td>
						</tr>
						<?php } ?>
					</tbody>				
				</table>
			</div>
		</div>
	</div>
<?php } ?>
