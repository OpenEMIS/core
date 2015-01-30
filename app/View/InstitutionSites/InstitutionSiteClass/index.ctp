<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Classes'));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model , 'add', $selectedPeriod, $selectedSection), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteClass/controls', array());
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.class') ?></th>
				<th><?php echo $this->Label->get('general.subject'); ?></th>
				<th><?php echo $this->Label->get('general.teacher'); ?></th>
				<th><?php echo $this->Label->get('general.male_students'); ?></th>
				<th><?php echo $this->Label->get('general.female_students'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($data as $id => $obj) : 
			?>
				<tr>
					<td><?php echo $this->Html->link($obj['InstitutionSiteClass']['name'], array('action' => $model , 'view', $obj['InstitutionSiteClass']['id']), array('escape' => false)); ?></td>
					<td><?php echo !empty($obj[$model]['EducationSubject']['name']) ? $obj[$model]['EducationSubject']['name'] : ''; ?></td>
					<td>
					<?php
					$staffNames = array();
					if (!empty($obj[$model]['InstitutionSiteClassStaff'])) {
						foreach ($obj[$model]['InstitutionSiteClassStaff'] as $staff) {
							array_push($staffNames, ModelHelper::getName($staff['Staff']));
						}
					}
					echo implode(', <br>', $staffNames)
					?>
					</td>
					<td class="cell-number"><?php echo !empty($obj['InstitutionSiteClass']['gender']['M']) ? $obj['InstitutionSiteClass']['gender']['M'] : 0; ?></td>
					<td class="cell-number"><?php echo !empty($obj['InstitutionSiteClass']['gender']['F']) ? $obj['InstitutionSiteClass']['gender']['F'] : 0; ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
