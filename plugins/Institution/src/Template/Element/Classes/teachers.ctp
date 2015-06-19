	<div class="clearfix"></div>

	<div class="panel-heading"><label><?php echo __('Teachers') ?></label></div>

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
		foreach($attr['data']['teachers'] as $i => $obj) : 
			if ($obj->status == 0) continue;

			if ($action=='edit') :
		?>

			<tr>
				<?php

				echo $this->Form->hidden("institution_site_class_staff.$i.id", [ 'value'=> $obj->id ]);
				echo $this->Form->hidden("institution_site_class_staff.$i.security_user_id", [ 'value'=> $obj->security_user_id ]);
				echo $this->Form->hidden("institution_site_class_staff.$i.status", [ 'value' => $obj->status ]);
				echo $this->Form->hidden("institution_site_class_staff.$i.institution_site_class_id", [ 'value'=> $obj->institution_site_section_id ]);

				?>
				<td><?php echo $obj->user->openemis_no ?></td>
				<td><?php echo $obj->user->name ?></td>
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
			</tr>

		<?php endif;?>

	<?php endforeach ?>
			
		</tbody>
	</table>
	
	<?php if ($action=='edit') :?>
	<div class="panel-footer">
	<?php
		echo $this->Form->input('staff_id', array(
			'options' => $attr['data']['teacherOptions'],
			'label' => false,
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>
