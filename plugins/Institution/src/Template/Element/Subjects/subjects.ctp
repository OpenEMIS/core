<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable table-input">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/></th>
						<th><?= $this->Label->get($attr['model'] .'.education_subject') ?></th>
						<th><?= $this->Label->get('general.name') ?></th>
						<th><?= $this->Label->get($attr['model'] .'.teacherOrTeachers') ?></th>
					</tr>
				</thead>
				<?php if (isset($attr['data'])) : ?>
				<?php //pr($this->request->data);?>
				<tbody>
					<?php foreach ($attr['data']['subjects'] as $i=>$obj) : ?>
						<?php
							$n = intval($obj->education_subject->id);
							$educationGradeId = intval($obj->education_grade_id);
							$selected = (array_key_exists('existedSubjects', $attr['data']) && array_key_exists($n, $attr['data']['existedSubjects'])) ? 'checked' : '';
							if ($selected) {
								$selected = '';
								$attrValue = $attr['data']['existedSubjects'][$n]['name'];
								$disabled = 'disabled';
								$attr['data']['existedSubjects'][$n]['name'] = '';
								$disabled = '';
								$teachers = $attr['data']['existedSubjects'][$n]['teachers'];
								unset($attr['data']['existedSubjects'][$n]);
							} else {
								if (!$obj->visible) {
									continue;
								}
				    			$attrValue = $obj->education_subject->name;
								$disabled = false;
							}
						?>
					<tr>
		    			<?php
	    				$attrErrors = [];
	    				$selectedInForm = false;
		    			if(!empty($this->request->data) && array_key_exists('MultiSubjects', $this->request->data) && isset($this->request->data['MultiSubjects'][$i])) {
		    				$requestData = $this->request->data['MultiSubjects'][$i];
		    				if ($this->request->data['submit'] == 'save') {
		    					if (!$selected) {
		    						$attrValue = $requestData['name'];
			    					if (isset($requestData['errors'])) {
				    					$attrErrors = $requestData['errors'];
				    				}
				    			}
				    			if (isset($requestData['education_subject_id'])) {
				    				$selectedInForm = 'checked';
				    			}
		    				}
		    			}
		    			$field = [
		    				'fieldName' => 'MultiSubjects['.$i.'][name]',
		    				'attr' => [
		    					'id' => 'MultiSubjects-'.$i.'-name',
		    					'label' => false,
		    					'name' => 'MultiSubjects['.$i.'][name]',
		    					'value' => $attrValue
		    				],
		    			];
		    			if ($disabled) {
		    				$field['attr']['disabled'] = $disabled;
		    			}
						$tdClass = '';
						if (!empty($attrErrors) && isset($attrErrors['name'])) {
							$field['attr']['class'] = 'form-error';
							$tdClass = 'error';
						}
						?>

						<td <?php if(!$disabled){ ?> class="checkbox-column" <?php } ?>>
							<?= $this->Form->checkbox(sprintf('MultiSubjects[%d][education_subject_id]', $i), [
									'value' => $n,
									'checked' => ($selected || $selectedInForm),
									'disabled' => $disabled,
									'class' => 'no-selection-label',
									'kd-checkbox-radio' => ''
								]);
							?>
							<?= $this->Form->input(sprintf('MultiSubjects[%d][education_grade_id]', $i), [
									'type' => 'hidden',
									'label' => false,
									'value' => $educationGradeId
								]);
							?>
						</td>
						<td><?= $obj->education_subject->name ?></td>

						<td class="<?= $tdClass ?>">
							<?php
								echo $this->Form->input($field['fieldName'], $field['attr']);
							?>
							<?php if (!empty($attrErrors) && isset($attrErrors['name'])) : ?>
								<ul class="error-message" style="margin-left:20px">
								<?php foreach ($attrErrors['name'] as $error) : ?>
									<li><?= $error ?></li>
								<?php endforeach ?>
								</ul>
							<?php endif; ?>
						</td>

						<td>
							<?php
							echo $this->Form->input(sprintf('MultiSubjects[%d][subject_staff][0][status]', $i), [
										'label' => false,
										'type' => 'hidden',
										'value' => 1
									]);
							if (!$selected) {
								echo $this->Form->input(sprintf('MultiSubjects.%d.subject_staff.0.staff_id', $i), array(
									'options' => $attr['data']['teachers'],
									'label' => false,
								));
							} else {
								if (count($teachers)>0) {
									$teachers = new \Cake\Collection\Collection($teachers);
									$names = $teachers->extract('name_with_id');
									echo implode(', ', $names->toArray());
								}
							}
							?>
						</td>

					</tr>
					<?php endforeach;//end $attr['data']['subjects'] ?>

					<?php
					/**
					 * if existedSubjects array is still not empty, it means that this class has this subject but the subject was set as not visible in the education structure
					 * It should still be shown but disabled.
					 */
					?>
					<?php if (array_key_exists('existedSubjects', $attr['data']) && !empty($attr['data']['existedSubjects'])) : ?>
						<?php foreach ($attr['data']['existedSubjects'] as $key=>$obj) : ?>
						<tr>
							<?php
								$n = intval($key);
								$i = $i + $key + 1;
				    			$field = [
				    				'fieldName' => 'MultiSubjects['.$i.'][name]',
				    				'attr' => [
				    					'id' => 'MultiSubjects-'.$i.'-name',
				    					'label' => false,
				    					'name' => 'MultiSubjects['.$i.'][name]',
				    					'value' => $obj['name'],
				    					'disabled' => 'disabled'
				    				],
				    			];
							?>
							<td>
								<?= $this->Form->checkbox(sprintf('MultiSubjects[%d][education_subject_id]', $i), [
										'value' => $n,
										'checked' => 'checked',
										'disabled' => 'disabled',
										'class' => 'no-selection-label',
										'kd-checkbox-radio' => ''
									]);
								?>
							</td>
							<td><?= $obj['subject_name'] ?></td>

							<td class="<?= $tdClass ?>">
								<?php
									echo $this->Form->input($field['fieldName'], $field['attr']);
								?>
							</td>

							<td>
								<?= $this->Form->input(sprintf('MultiSubjects[%d][subject_staff][0][status]', $i), [
										'label' => false,
										'type' => 'hidden',
										'value' => 1
									]);
								?>
							</td>
						</tr>
						<?php endforeach;//end $attr['data']['existedSubjects'] ?>
					<?php endif ?>

				</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
</div>

<?php else : ?>

<?php
	foreach ($attr['data']['grades'] as $grade) {
		// pr($grade);die;
		echo $grade->name.'<br/>';
	}
	// pr($attr['data']['grades']);
?>

<?php endif ?>
