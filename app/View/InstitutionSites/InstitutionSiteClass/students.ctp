<div class="form-group" style="padding: 10px;">
	
	<div class="panel panel-default">
		<div class="panel-heading dark-background"><?php echo __('Students') ?></div>

		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('general.sex'); ?></th>
					<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
					<!--th><?php echo $this->Label->get('general.category'); ?></th-->
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			if (isset($this->data['InstitutionSiteClassStudent'])) :
				foreach($this->data['InstitutionSiteClassStudent'] as $i => $obj) : 
					if ($obj['status'] == 0) continue;
			?>

				<tr>
					<?php
					echo $this->Form->hidden("InstitutionSiteClassStudent.$i.id");
					echo $this->Form->hidden("InstitutionSiteClassStudent.$i.student_id");
					echo $this->Form->hidden("InstitutionSiteClassStudent.$i.institution_site_section_id", array('value' => $selectedSectionId));
					echo $this->Form->hidden("InstitutionSiteClassStudent.$i.status", array('value' => 1));
					//echo $this->Form->hidden("InstitutionSiteClassStudent.$i.education_grade_id", array('value' => !empty($this->data[$model]['education_grade_id']) ? $this->data[$model]['education_grade_id'] : 0));

					foreach ($obj['Student'] as $field => $value) {
						echo $this->Form->hidden("InstitutionSiteClassStudent.$i.Student.$field", array('value' => $value));
					}
					?>
					<td><?php echo $obj['Student']['identification_no']; ?></td>
					<td><?php echo ModelHelper::getName($obj['Student']) ?></td>
					<td><?php echo $this->Model->getGender($obj['Student']['gender']) ?></td>
					<td><?php echo $this->Utility->formatDate($obj['Student']['date_of_birth']); ?></td>
					<!--td>
						<?php
						echo $this->Form->input("InstitutionSiteClassStudent.$i.student_category_id", array(
							'label' => false,
							'div' => false,
							'before' => false,
							'between' => false,
							'after' => false,
							'options' => $categoryOptions,
							'value' => $obj['student_category_id']
						));
						?>
					</td-->
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
			echo $this->Form->input('student_id', array(
				'options' => $studentOptions,
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
