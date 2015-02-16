<div class="form-group" style="padding: 10px;">
	
	<div class="panel panel-default">
		<div class="panel-heading dark-background"><?php echo __('Teachers') ?></div>

		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			if (isset($this->data['InstitutionSiteClassStaff'])) :
				foreach($this->data['InstitutionSiteClassStaff'] as $i => $obj) : 
					if ($obj['status'] == 0) continue;
			?>

				<tr>
					<?php
					echo $this->Form->hidden("InstitutionSiteClassStaff.$i.id");
					echo $this->Form->hidden("InstitutionSiteClassStaff.$i.staff_id");
					echo $this->Form->hidden("InstitutionSiteClassStaff.$i.status", array('value' => 1));

					foreach ($obj['Staff'] as $field => $value) {
						echo $this->Form->hidden("InstitutionSiteClassStaff.$i.Staff.$field", array('value' => $value));
					}
					?>
					<td><?php echo $obj['SecurityUser']['openemis_no']; ?></td>
					<td><?php echo ModelHelper::getName($obj['Staff']) ?></td>
					<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
				</tr>
			<?php 
				endforeach;
			endif;
			?>
				
			</tbody>
		</table>

		<div class="panel-footer">
		<?php
			echo $this->Form->input('staff_id', array(
				'options' => $staffOptions,
				'label' => false,
				'div' => false,
				'before' => false,
				'between' => false,
				'after' => false,
				'onchange' => "$('#reload').val('add').click();"
			));
			?>
		</div>
	</div>
</div>
