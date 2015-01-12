<?php
echo $this->Html->script('/Surveys/js/survey.question', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formParams = array('plugin' => 'Surveys', 'controller' => $this->params['controller'], 'action' => 'moveOrder', $id);
echo $this->Form->create('SurveyQuestion', array('id' => 'SurveyQuestionMoveForm', 'url' => $formParams));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
echo $this->Form->end();
?>
<?php echo $this->element('alert'); ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th class="cell-order"><?php echo __('Order'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$index = 1;
				foreach ($data as $obj) : 
			?>
				<tr row-id="<?php echo $obj['SurveyQuestion']['id']; ?>">
					<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['SurveyQuestion']['visible']==1); ?></td>
					<td><?php echo $this->Html->link($obj['SurveyQuestion']['name'], array('action' => 'view', $obj['SurveyQuestion']['id'])); ?></td>
					<td class="action">
						<?php
						$size = count($data);
						echo $this->element('Surveys.question_reorder', compact('index', 'size'));
						$index++;
						?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>