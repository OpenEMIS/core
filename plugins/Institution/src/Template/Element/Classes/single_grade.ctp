<?php ?>

<div class="input clearfix">
	<label for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get('InstitutionClasses.class'); ?></th>
						<th><?= $this->Label->get('InstitutionClasses.staff_id'); ?></th>
						<th><?= $this->Label->get('InstitutionClasses.secondary_staff_id'); ?> </th>
					</tr>
				</thead>

				<tbody>
					<?php
					$startingClassNumber = count($attr['data']['existedClasses']) + 1;
					for ($i=0; $i<$attr['data']['numberOfClasses']; $i++) :
						$nameIsAvailable = false;
						do {
							/**
							 * In case in the future, a specific arabic locale such as "ar_JO" or "ar_SA" is being used.
							 */
							if ($this->ControllerAction->locale() == 'ar' || substr_count($this->ControllerAction->locale(), 'ar_') > 0) {
								$letter = $this->Label->getArabicLetter($startingClassNumber);
							} else {
								$letter = $this->ControllerAction->getColumnLetter($startingClassNumber);
							}
							$defaultName = !empty($attr['data']['grade']) ? sprintf('%s-%s', $attr['data']['grade']['name'], $letter) : "";
							if (!in_array($defaultName, $attr['data']['existedClasses'])) {
							    $nameIsAvailable = true;
							} else {
								$startingClassNumber++;
							}
						} while (!$nameIsAvailable);
					?>
					<tr>
		    			<?php
		    			$attrValue = '';
	    				$attrErrors = [];
		    			if(empty($this->request->data)) {
		    				$attrValue = $defaultName;
		    			} else {
		    				if ($this->request->data['submit'] == 'save') {
		    					$attrValue = $this->request->data['MultiClasses'][$i]['name'];
		    					$attrErrors = (array_key_exists('errors', $this->request->data['MultiClasses'][$i]))? $this->request->data['MultiClasses'][$i]['errors']: null;
		    				} else {
			    				$attrValue = $defaultName;
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
						$tdClass = '';
						if (!empty($attrErrors) && isset($attrErrors['name'])) {
							$field['attr']['class'] = 'form-error';
							$tdClass = 'error';
						}
						?>

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
							<?= $this->Form->hidden(sprintf('MultiClasses.%d.class_number', $i), array(
								'value' => $startingClassNumber
							));?>
						</td>

						<td><?php
						echo $this->Form->input(sprintf('MultiClasses.%d.staff_id', $i), array(
							'options' => $attr['data']['staffOptions'],
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false
						));

						?>
						<?php if (!empty($attrErrors) && isset($attrErrors['staff_id'])) : ?>
							<ul class="error-message" style="margin-left:20px">
							<?php foreach ($attrErrors['staff_id'] as $error) : ?>
								<li><?= $error ?></li>
							<?php endforeach ?>
							</ul>
						<?php endif; ?>
						</td>

						<td><?php
						echo $this->Form->input(sprintf('MultiClasses.%d.secondary_staff_id', $i), array(
							'options' => $attr['data']['staffOptions'],
							'label' => false,
							'div' => false,
							'between' => false,
							'after' => false
						));
						?></td>
					</tr>
					<?php
						$startingClassNumber++;
					endfor; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
