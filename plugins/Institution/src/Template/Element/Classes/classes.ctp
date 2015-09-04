<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>
	
<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= $this->Label->get($attr['model'] .'.education_subject') ?></th>
					<th><?= $this->Label->get('general.name') ?></th>
					<th><?= $this->Label->get($attr['model'] .'.teacher') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<?php //pr($this->request->data);?>
			<tbody>
				<?php foreach ($attr['data']['subjects'] as $i=>$obj) : ?>
					<?php $n = intval($obj->education_subject->id) ?>
					<?php //pr($obj->toArray());?>
					<?php
						$selected = (isset($attr['data']['existedSubjects']) && array_key_exists($n, $attr['data']['existedSubjects'])) ? 'checked' : ''; 
						if ($selected) {
							// pr($attr['data']['existedSubjects'][$n]);
							$attrValue = $attr['data']['existedSubjects'][$n];
							$disabled = 'disabled';
						} else {
			    			$attrValue = $obj->education_subject->name;
							$disabled = false;
						}
					?>
				<tr>
	    			<?php 
    				$attrErrors = [];
    				$selectedInForm = false;
	    			if(!empty($this->request->data) && isset($this->request->data['MultiClasses'])) {
	    				$requestData = $this->request->data['MultiClasses'][$i];
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
	    				'fieldName' => 'MultiClasses['.$i.'][name]',
	    				'attr' => [
	    					'id' => 'multiclasses-'.$i.'-name',
	    					'label' => false, 
	    					'name' => 'MultiClasses['.$i.'][name]',
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

					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('MultiClasses[%d][education_subject_id]', $i) ?>" value="<?php echo $n?>" <?php echo $selectedInForm;?> <?php echo $selected;?> <?php echo $disabled;?> />
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
						<input type="hidden" name="<?php echo sprintf('MultiClasses[%d][institution_site_class_staff][0][status]', $i) ?>" value="1" />
						<?php 
						if (!$selected) {
							echo $this->Form->input(sprintf('MultiClasses.%d.institution_site_class_staff.0.security_user_id', $i), array(
								'options' => $attr['data']['teachers'], 
								'label' => false,
							));
						}
						?>
					</td>

				</tr>
				<?php endforeach ?>
			</tbody>
			<?php endif ?>
		</table>
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
