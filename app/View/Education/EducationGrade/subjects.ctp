<?php
	if(isset($this->data) && count($this->data)>0){
		$isView = false;
	} else {
		$this->data = $data;
		$isView = true;
	}
?>
<div class="form-group" style="padding: 10px;">
	
	<div class="panel panel-default">
		<div class="panel-heading center"><?php echo $this->Label->get('EducationSubject.title'); ?></div>

		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th class="center"><?php echo $this->Label->get('general.code'); ?></th>
					<th class="center"><?php echo $this->Label->get('EducationGradeSubject.hours_required'); ?></th>
					<?php echo  (!$isView) ? '<th class="cell-delete"></th>' : '';?>
				</tr>
			</thead>

			<tbody>

		<?php
			if(!$isView) {
				echo $this->Form->hidden("EducationProgramme.id",array('value'=>$this->data['EducationProgramme']['id']));
				echo $this->Form->hidden("EducationProgramme.name",array('value'=>$this->data['EducationProgramme']['name']));
			}
				foreach($this->data['EducationGradeSubject'] as $i => $obj) :
					if ($obj['visible'] == 0) continue;
		?>

					<tr>

					<?php
						if(!$isView) {
							echo $this->Form->hidden("EducationGradeSubject.$i.id");
							echo $this->Form->hidden("EducationGradeSubject.$i.visible", array('value' => 1));
							echo $this->Form->hidden("EducationGradeSubject.$i.education_subject_id", array('value' => $obj['EducationSubject']['id']));
							echo $this->Form->hidden("EducationGradeSubject.$i.education_grade_id", array('value' => !empty($this->data[$model]['id']) ? $this->data[$model]['id'] : 0));

							foreach ($obj['EducationSubject'] as $field => $value) {
								echo $this->Form->hidden("EducationGradeSubject.$i.EducationSubject.$field", array('value' => $value));
							}
						}
					?>
						<td><?php echo $obj['EducationSubject']['name']; ?></td>
						<td class="center"><?php echo $obj['EducationSubject']['code']; ?></td>
					
					<?php if(!$isView): ?>
						<td class="center">
							<?php
							echo $this->Form->input("EducationGradeSubject.$i.hours_required", array(
								'label' => false,
								'div' => false,
								'before' => false,
								'between' => false,
								'after' => false,
								'value' => $obj['hours_required']
							));
							?>
						</td>
						<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
					<?php else: ?>
						<td class="center"><?php echo $obj['hours_required'];?></td>
					<?php endif; ?>

					</tr>

			<?php endforeach; ?>

			</tbody>
		</table>

		<?php if(!$isView):?>
		<div class="panel-footer">
		<?php
			echo $this->Form->input('education_subject_id', array(
				'options' => $subjectOptions,
				'label' => false,
				'div' => false,
				'before' => false,
				'between' => false,
				'after' => false,
				'onchange' => "$('#reload').val('add').click();"
			));
		?>
		</div>
		<?php endif; ?>
	</div>
</div>
