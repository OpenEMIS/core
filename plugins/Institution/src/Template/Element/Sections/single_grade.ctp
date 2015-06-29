<?php ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-in-view col-md-5 table-responsive">
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
					<td><?php 
					echo $this->Form->input(sprintf('MultiSections.%d.name', $i), array(
						'value' => $defaultName,
						'label' => false, 
						'div' => false, 
						'between' => false, 
						'after' => false
						)); 
					echo $this->Form->hidden(sprintf('MultiSections.%d.section_number', $i), array(
						'value' => $startingSectionNumber
					));
					?></td>
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
