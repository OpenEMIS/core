<?php
echo $this->Html->css('../js/plugins/fuelux/css/fuelux.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/fuelux/js/fuelux.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Generate Reports'));

$this->start('contentBody');
?>

<div class="fuelux">
	<div class="wizard" data-initialize="wizard">
		<ul class="steps">
			<?php 
			$li = '<li data-step="%d" %s><span class="badge">%d</span>%s<span class="chevron"></span></li>';
			$i=0;
			$feature = $features[$selectedFeature];

			foreach ($steps as $key => $step) {
				if (!array_key_exists($key, $feature) || (array_key_exists($key, $feature) && $feature[$key] !== false)) {
					$class = $i++==0 ? 'class="active"' : '';
					echo sprintf($li, $i, $class, $i, $step);
				}
			}
			$i=1;
			?>
		</ul>

		<div class="actions">
			<button class="btn btn-default btn-prev"><span class="glyphicon glyphicon-arrow-left"></span></button>
			<button class="btn btn-default btn-next" data-last="Generate"><span class="glyphicon glyphicon-arrow-right"></span></button>
		</div>

		<?php
		echo $this->Form->create('Report', array('class' => 'form-horizontal'));
		?>
			<div class="step-content">
				
				<div class="step-pane active alert" data-step="<?php echo $i++ ?>">
					<div class="row">
						<div class="col-md-1"><label class="control-label"><?php echo __('Feature'); ?></label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('feature', array(
							'div' => false, 
							'label' => false, 
							'class' => 'form-control', 
							'options' => $features,
							'onchange' => 'jsForm.change(this)',
							'url' => $this->params['controller'] . '/' . $this->action
						));
						?>
						</div>
					</div>
					
				</div>

				<?php if (isset($feature['survey_template'])) : ?>
					<?php if (!array_key_exists('survey_template', $feature) || (array_key_exists('survey_template', $feature) && $feature['survey_template'] !== false)) : ?>

					<div class="step-pane alert" data-step="<?php echo $i++ ?>">
						<div class="row">
							<div class="col-md-1"><label class="control-label"><?php echo __('Templates'); ?></label></div>
							<div class="col-md-5">
							<?php
							echo $this->Form->input('survey_template', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => $templateOptions));
							?>
							</div>
						</div>
					</div>

					<?php endif ?>
				<?php endif ?>

				<?php if (!array_key_exists('period', $feature) || (array_key_exists('period', $feature) && $feature['period'] !== false)) : ?>

				<div class="step-pane alert" data-step="<?php echo $i++ ?>">
					<div class="row">
						<div class="col-md-1"><label class="control-label"><?php echo __('Period'); ?></label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('period', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => $periodOptions));
						?>
						</div>
					</div>
				</div>

				<?php endif ?>

				<div class="step-pane alert" data-step="<?php echo $i++ ?>">
					<div class="row">
						<div class="col-md-1"><label class="control-label"><?php echo __('Format'); ?></label></div>
						<div class="col-md-5">
						<?php
						echo $this->Form->input('format', array('div' => false, 'label' => false, 'class' => 'form-control', 'options' => $formatOptions));
						?>
						</div>
					</div>
				</div>
				
			</div>
		<?php
		echo $this->Form->end();
		?>
	</div>
</div>
<?php
$this->end();
?>
