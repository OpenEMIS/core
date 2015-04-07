<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', 'status' => $selectedStatus), array('class' => 'divider'));
	if($_delete) {
		if ($selectedAction == 1) {
			echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		} else if ($selectedAction == 2) {
			echo $this->Html->link(
				$this->Label->get('general.reject'),
				array('action' => 'InstitutionSiteQualityRubric', 'remove'),
				array(
					'class' => 'divider',
					'onclick' => 'return jsForm.confirmDelete(this)',
					'data-title' => __('Reject Confirmation'),
					'data-content' => __('You are about to reject this quality rubric.<br><br>Are you sure you want to do this?'),
					'data-button-text' => $this->Label->get('general.reject')
				)
			);
		}
	}
$this->end();

$this->start('contentBody');
echo $this->element($tabsElement, array(), array());
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('No.') ?></th>
				<th><?php echo $this->Label->get('RubricSection.name'); ?></th>
				<th>
					<?php echo $this->Label->get('RubricSection.no_of_criterias'); ?>
					<?php
						if ($selectedAction == 1) {
							echo " (".__('Answered').")";
						}
					?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php $named = $this->request->params['named']; ?>
			<?php foreach ($data as $key => $obj) : ?>
				<tr>
					<td><?php echo $obj['RubricSection']['order']; ?></td>
					<td>
						<?php
							$actionUrl = array('action' => $model, 'edit', $obj['RubricSection']['id']);
							echo $this->Html->link($obj['RubricSection']['name'], array_merge($actionUrl, $named));
						?>
					</td>
					<td>
						<?php echo $obj['RubricSection']['no_of_criterias']; ?>
						<?php
							if ($selectedAction == 1) {
								echo " (".$obj['RubricSection']['no_of_answers'].")";
							}
						?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
