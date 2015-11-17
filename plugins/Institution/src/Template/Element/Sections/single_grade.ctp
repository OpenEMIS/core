<?php ?>

<div class="input clearfix">
	<?php if ($this->ControllerAction->locale() == 'ar'): ?>
	<label class="pull-right" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<?php else: ?>
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<?php endif; ?>
	<div class="table-in-view">
		<table class="table table-checkable table-input">
			<thead>
				<tr>
					<th><?= $this->Label->get('InstitutionSiteSections.section'); ?></th>
					<th><?= $this->Label->get('InstitutionSiteSections.security_user_id'); ?></th>
				</tr>
			</thead>
			
			<tbody>
				<?php 
				$startingSectionNumber = count($attr['data']['existedSections']) + 1;
				for ($i=0; $i<$attr['data']['numberOfSections']; $i++) :
					$nameIsAvailable = false;
					do {
						/**
						 * In case in the future, a specific arabic locale such as "ar_JO" or "ar_SA" is being used.
						 */
						if ($this->ControllerAction->locale() == 'ar' || substr_count($this->ControllerAction->locale(), 'ar_') > 0) {
							$letter = $this->Label->getArabicLetter($startingSectionNumber);
						} else {
							$letter = $this->ControllerAction->getColumnLetter($startingSectionNumber);
						}
						$defaultName = !empty($attr['data']['grade']) ? sprintf('%s-%s', $attr['data']['grade']['name'], $letter) : "";
						if (!in_array($defaultName, $attr['data']['existedSections'])) {
						    $nameIsAvailable = true;
						} else {
							$startingSectionNumber++;
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
	    					$attrErrors = $this->request->data['MultiClasses'][$i]['errors'];
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
						<?= $this->Form->hidden(sprintf('MultiClasses.%d.section_number', $i), array(
							'value' => $startingSectionNumber
						));?>
					</td>

					<td><?php 
					echo $this->Form->input(sprintf('MultiClasses.%d.security_user_id', $i), array(
						'options' => $attr['data']['staffOptions'], 
						'label' => false,
						'div' => false,
						'between' => false,
						'after' => false
					));
					?></td>
				</tr>
				<?php 
					$startingSectionNumber++;
				endfor; ?>
			</tbody>
		</table>
	</div>
</div>
