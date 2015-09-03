<?php ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th><?= $this->Label->get('InstitutionSiteSections.section'); ?></th>
					<th><?= $this->Label->get('InstitutionSiteSections.security_user_id'); ?></th>
				</tr>
			</thead>
			
			<tbody>
				<?php 
				$startingSectionNumber = $attr['data']['startingSectionNumber'];
				for ($i=0; $i<$attr['data']['numberOfSections']; $i++) :
					$letter = $this->ControllerAction->getColumnLetter($startingSectionNumber);
					$defaultName = !empty($attr['data']['grade']) ? sprintf('%s-%s', $attr['data']['grade']['name'], $letter) : "";
				?>
				<tr>
	    			<?php 
	    			$attrValue = '';
    				$attrErrors = [];
	    			if(empty($this->request->data)) {
	    				$attrValue = $defaultName;
	    			} else {
	    				if ($this->request->data['submit'] == 'save') {
	    					$attrValue = $this->request->data['MultiSections'][$i]['name'];
	    					$attrErrors = $this->request->data['MultiSections'][$i]['errors'];
	    				} else {
		    				$attrValue = $defaultName;
	    				}
	    			}
	    			$field = [
	    				'fieldName' => 'MultiSections['.$i.'][name]',
	    				'attr' => [
	    					'id' => 'multisections-'.$i.'-name',
	    					'label' => false, 
	    					'name' => 'MultiSections['.$i.'][name]',
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
						<?= $this->Form->hidden(sprintf('MultiSections.%d.section_number', $i), array(
							'value' => $startingSectionNumber
						));?>
					</td>

					<td><?php 
					echo $this->Form->input(sprintf('MultiSections.%d.security_user_id', $i), array(
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
