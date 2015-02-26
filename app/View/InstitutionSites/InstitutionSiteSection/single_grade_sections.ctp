<?php ?>
<div class="form-group">
	<label class="col-md-3 control-label"></label>
	<div class="col-md-8">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.section'); ?></th>
						<th><?php echo $this->Label->get('InstitutionSiteSection.staff_id'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php 
					for($i=0; $i<$numberOfSections; $i++) :
					$letter = $this->Utility->getColumnLetter($startingSectionNumber);
					$defaultName = !empty($grade) ? sprintf('%s-%s', $grade['EducationGrade']['name'], $letter) : "";
					?>
					<tr>
						<td><?php 
						echo $this->Form->input(sprintf('InstitutionSections.%d.name', $i), array(
							'value' => $defaultName,
							'label' => false, 
							'div' => false, 
							'between' => false, 
							'after' => false
							)); 
						echo $this->Form->hidden(sprintf('InstitutionSections.%d.section_number', $i), array(
							'value' => $startingSectionNumber
						));
						?></td>
						<td><?php 
						echo $this->Form->input(sprintf('InstitutionSections.%d.staff_id', $i), array(
							'options' => $staffOptions, 
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false
						));
						?></td>
					</tr>
					<?php 
					$startingSectionNumber += 1;
					endfor; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>