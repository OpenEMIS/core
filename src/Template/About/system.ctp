<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
<tab heading="System Information">
		<!-- System Information -->
			<div id="system-info">
				<div class="table-wrapper">
					<div class="table-responsive">
						<table class="table table-curved table-sortable">
							<thead>
								<th><?php echo __('Database') ?></th>
								<th><?php echo __('PHP Version') ?></th>
								<th><?php echo __('Web Server') ?></th>
								<th><?php echo __('Operating System') ?></th>
							</thead>
							<tbody>
								<td><?php echo $databaseInfo; ?></td>
								<td><?php echo phpversion(); ?></td>
								<td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
								<td><?php echo php_uname("s") . '/' . php_uname("r"); ?></td>
							</tbody>
						</table>			
					</div>	
				</div>
			</div>		
		</tab>
<?php $this->end() ?>