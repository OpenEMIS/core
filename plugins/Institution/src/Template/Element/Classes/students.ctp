	<div class="clearfix"></div>

	<div class="panel-heading"><label><?php echo __('Students') ?></label></div>

	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.gender'); ?></th>
				<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
				<th class="cell-delete"></th>
			</tr>
		</thead>

		<tbody>
		<?php 
		foreach($attr['data']['students'] as $i => $obj) : 
			if ($obj->status == 0) continue;

			if ($action=='edit') :
		?>

			<tr>
				<?php

				echo $this->Form->hidden("institution_site_class_students.$i.id", [ 'value'=> $obj->id ]);
				echo $this->Form->hidden("institution_site_class_students.$i.security_user_id", [ 'value'=> $obj->security_user_id ]);
				echo $this->Form->hidden("institution_site_class_students.$i.status", [ 'value' => $obj->status ]);
				echo $this->Form->hidden("institution_site_class_students.$i.institution_site_class_id", [ 'value'=> $obj->institution_site_class_id ]);

				?>
				<td><?php echo $obj->user->openemis_no ?></td>
				<td><?php echo $obj->user->name ?></td>
				<td><?php echo $obj->user->gender->name ?></td>
				<td><?php echo $obj->user->date_of_birth ?></td>
				<td> 
					<button class="btn btn-lg" type="button" aria-expanded="true" onclick="jsTable.doRemove(this)">
						<?= __('Remove') ?>
					</button>
				</td>
			</tr>

		<?php else:?>

			<tr>
				<td><?php echo $obj->user->openemis_no ?></td>
				<td><?php echo $obj->user->name ?></td>
				<td><?php echo $obj->user->gender->name ?></td>
				<td><?php echo $obj->user->date_of_birth ?></td>
			</tr>

		<?php endif;?>

	<?php endforeach ?>
			
		</tbody>
	</table>
	
	<?php if ($action=='edit') :?>
	<div class="panel-footer">
	<?php
		echo $this->Form->input('student_id', array(
			'options' => $attr['data']['studentOptions'],
			'label' => false,
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>
