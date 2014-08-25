<fieldset class="section_group">
	<legend><?php echo $this->Label->get('general.past') ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('date.start'); ?></th>
					<th><?php echo $this->Label->get('date.end'); ?></th>
					<th><?php echo $this->Label->get('general.status'); ?></th>
					<th>FTE</th>
				</tr>
			</thead>
	
			<tbody>
				<?php foreach ($past as $i => $obj) : ?>
				<tr>
					<td><?php echo $obj['Staff']['identification_no'] ?></td>
					<td>
						<?php 
						$name = trim($obj['Staff']['first_name'] . ' ' . $obj['Staff']['middle_name'] . ' ' . $obj['Staff']['last_name']);
						if ($_edit) {
							echo $this->Html->link($name, array('action' => $model, 'staffEdit', $obj['InstitutionSiteStaff']['id']));
						} else {
							echo $name;
						}
						?>
					</td>
					<td><?php $this->Utility->formatDate($obj['InstitutionSiteStaff']['start_date']) ?></td>
					<td><?php $this->Utility->formatDate($obj['InstitutionSiteStaff']['end_date']) ?></td>
					<td><?php echo $obj['StaffStatus']['name'] ?></td>
					<td><?php echo $obj['InstitutionSiteStaff']['FTE'] ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</fieldset>
