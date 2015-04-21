<fieldset class="section_group">
	<legend><?php echo $this->Label->get('general.current') ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('date.start'); ?></th>
					<th><?php echo $this->Label->get('InstitutionSiteStudent.fte'); ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php 
				// if $current is 0, we need to add an empty row so that the table header and table footer columns will have separator lines; else the lines will not show up.
				// Probably due to the existence of <tfoot> element.
				if (count($current)>0): 
				?>

					<?php foreach ($current as $i => $obj) : ?>
					<tr>
						<td><?php echo (array_key_exists('SecurityUser', $obj['Staff']))? $obj['Staff']['SecurityUser']['openemis_no']:""; ?></td>
						<td>
							<?php 
							$name = (array_key_exists('SecurityUser', $obj['Staff']))? $this->Model->getName($obj['Staff']['SecurityUser']): "";
							if ($_edit) {
								echo $this->Html->link($name, array('action' => $model, 'staffEdit', $obj['InstitutionSiteStaff']['id']));
							} else {
								echo $name;
							}
							?>
						</td>
						<td><?php $this->Utility->formatDate($obj['InstitutionSiteStaff']['start_date']) ?></td>
						<td><?php echo $obj['InstitutionSiteStaff']['FTE'] ?></td>
					</tr>
					<?php endforeach ?>

				<?php 
				// Need to add an empty row so that an empty table will not look ugly without columns separator lines
				else: 
				?>
				
				<tr></tr>
				
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
					<td colspan="3" class="side-label"><?php echo $this->Label->get('InstitutionSiteStudent.total_fte'); ?></td>
					<td><?php echo $totalCurrentFTE;?></td>
				</tr>
			</tfoot>
		</table>
	</div>
</fieldset>
