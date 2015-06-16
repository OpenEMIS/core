	<div class="clearfix"></div>

	<div class="panel-heading"><label><?php echo __('Students') ?></label></div>

	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.gender'); ?></th>
				<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
				<th><?php echo $this->Label->get('general.category'); ?></th>
				<th class="cell-delete"></th>
			</tr>
		</thead>

		<tbody>
		<?php 
		foreach($attr['data']['students'] as $i => $obj) : 
			// pr($obj);die;
			if ($obj->status == 0) continue;

			if ($action=='edit') :
			// pr($obj);
		?>

			<tr>
				<?php

				// [id] => 277
	   //          [security_user_id] => 285
	   //          [institution_site_section_id] => 2
	   //          [education_grade_id] => 2
	   //          [student_category_id] => 214
	   //          [status] => 1
				echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.id", [ 'value'=> $obj->id ]);
				echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.security_user_id", [ 'value'=> $obj->security_user_id ]);
				echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.status", [ 'value' => 1 ]);
				echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.education_grade_id", [ 'value'=> $obj->education_grade_id ]);
				echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.institution_site_section_id", [ 'value'=> $obj->institution_site_section_id ]);

				?>
				<td><?php echo $obj->openemis_no ?></td>
				<td><?php echo $obj->name ?></td>
				<td><?php echo $obj->gender ?></td>
				<td><?php echo $obj->date_of_birth ?></td>
				<td>
					<?php
					echo $this->Form->input("InstitutionSiteSectionStudents.$i.student_category_id", array(
						'label' => false,
						'div' => false,
						'before' => false,
						'between' => false,
						'after' => false,
						'options' => $attr['data']['categoryOptions'],
						'value' => $obj->student_category_id
					));
					?>
				</td>
				<td> 

					<?php
						$icon = $this->ControllerAction->getLabel('general', 'delete');
						$options['record-id'] = $obj->id;
						$options['record-user-id'] = $obj->security_user_id;
						$options['onclick'] = 'jsTable.doRemove(this)';
					?>
					<?= $this->Html->link($icon, '#', $options) ?>
				</td>
			</tr>

		<?php else:?>

					<tr>
						<td><?php echo $obj->openemis_no ?></td>
						<td><?php echo $obj->name ?></td>
						<td><?php echo $obj->gender ?></td>
						<td><?php echo $obj->date_of_birth ?></td>
						<td><?php echo $attr['data']['categoryOptions'][$obj->student_category_id] ?></td>
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
			'div' => false,
			'before' => false,
			'between' => false,
			'after' => false,
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>
