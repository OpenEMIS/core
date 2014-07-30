<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', array('inline' => false));
echo $this->Html->script('security.group', array('inline' => false));

//echo $this->Html->script('security', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Groups'));
$this->start('contentActions');
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'add'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('name');
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('Area.name') ?></label>
	<div class="col-md-9">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('Area.name') ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/0" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('Area.name') ?>
			</a>
		</div>
	</div>
</div>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('Institution.name') ?></label>
	<div class="col-md-9">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('Institution.name') ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/1" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('Institution.name') ?>
			</a>
		</div>
	</div>
</div>

<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $model)));
echo $this->Form->end();
$this->end();
?>
