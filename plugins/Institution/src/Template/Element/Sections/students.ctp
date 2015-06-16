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
				if ($obj->status == 0) continue;

				// pr($obj);
				if ($action=='edit') :
			?>

				<tr>
					<?php
					echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.id");
					echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.student_id");
					echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.status", array('value' => 1));
					echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.education_grade_id", array('value' => !empty($this->data[$model]['education_grade_id']) ? $this->data[$model]['education_grade_id'] : 0));

					foreach ($obj['Student']['SecurityUser'] as $field => $value) {
						if ($field == 'Gender') {
							echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.Student.SecurityUser.$field.name", array('value' => $value['name'])); 
							continue;
						} else {
							echo $this->Form->hidden("InstitutionSiteSectionStudents.$i.Student.SecurityUser.$field", array('value' => $value));	
						}
					}
					?>
					<td><?php //echo $obj['Student']['SecurityUser']['openemis_no']; ?></td>
					<td><?php //echo ModelHelper::getName($obj['Student']['SecurityUser']) ?></td>
					<td><?php //echo __($obj['Student']['SecurityUser']['Gender']['name']) ?></td>
					<td><?php //echo $this->Utility->formatDate($obj['Student']['SecurityUser']['date_of_birth']); ?></td>
					<td>
						<?php
						echo $this->Form->input("InstitutionSiteSectionStudents.$i.student_category_id", array(
							'label' => false,
							'div' => false,
							'before' => false,
							'between' => false,
							'after' => false,
							'options' => $categoryOptions,
							'value' => $obj['student_category_id']
						));
						?>
					</td>
					<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
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
