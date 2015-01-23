<?php
echo $this->Html->css('../js/plugins/fuelux/css/fuelux.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/fuelux/js/fuelux.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Generate Reports'));

$this->start('contentBody');
?>

<style type="text/css">
.fuelux .wizard .steps > li { font-size: 14px; }
</style>

<div class="fuelux form-horizontal">
	<div class="wizard" data-initialize="wizard">
		<ul class="steps">
			<li data-step="1" class="active"><span class="badge">1</span>Feature<span class="chevron"></span></li>
			<li data-step="2"><span class="badge">2</span>Academic Period<span class="chevron"></span></li>
			<li data-step="3"><span class="badge">3</span>Generate<span class="chevron"></span></li>
		</ul>

		<div class="actions">
			<button class="btn btn-default btn-prev"><span class="fa fa-arrow-left"></span></button>
			<button class="btn btn-default btn-next" data-last="Complete"><span class="fa fa-arrow-right"></span></button>
		</div>

		<form action="">
			<div class="step-content">
				
				<div class="step-pane active alert" data-step="1">
					<div class="row">
						<div class="col-md-1"><label class="control-label">Feature</label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('module', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => array('Overview', 'Programmes', 'Sections', 'Classes', 'Bank Accounts')));
						?>
						</div>
					</div>
					
				</div>

				<div class="step-pane alert" data-step="2">
					<div class="row">
						<div class="col-md-1"><label class="control-label">Period</label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('period', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => array('2010', '2011', '2012', '2013')));
						?>
						</div>
					</div>
				</div>

				<div class="step-pane alert" data-step="3">
					<div class="row">
						<div class="col-md-1"><label class="control-label">Format</label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('format', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => array('Excel')));
						?>
						</div>
					</div>
				</div>
				
			</div>
		</form>
	</div>
</div>
<?php
$this->end();
?>
