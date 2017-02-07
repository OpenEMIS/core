<?php
	$competencyPeriodCount = isset($attr['competencyPeriodCount']) ? $attr['competencyPeriodCount'] : 0;
	$competencyPeriodOptions = isset($attr['competencyPeriodOptions']) ? $attr['competencyPeriodOptions'] : [];
	$alias = $ControllerAction['table']->alias();
	$this->Form->unlockField('competency_period');
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php if ($competencyPeriodCount == 0) : ?>
		<?php echo $this->Label->get('StudentCompetencies.noPeriod'); ?>
	<?php else : ?>
		<div class="input-selection">
			<?php foreach($competencyPeriodOptions as $i => $obj) : ?>
				<?php
					$name = $obj['code_name'];
					$url = $obj['url'];
				?>
				<div class="input">
					<?php if ($obj['checked'] == true) : ?>
						<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_period" checked>
					<?php else : ?>
						<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_period" onclick="window.location.href='<?= $this->Url->build($url); ?>'">
					<?php endif ?>
				</div>
			<?php endforeach ?>
		</div>
	<?php endif ?>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<?php if ($competencyPeriodCount == 0) : ?>
		<?php
			echo $this->Form->input('competency_period', [
				'class' => 'form-control',
				'disabled' => 'disabled',
				'value' => $this->Label->get('StudentCompetencies.noPeriod')
			]);
		?>
	<?php else : ?>
		<div class="input">
			<label><?= $attr['label']; ?></label>
			<div class="input-selection">
				<?php foreach($competencyPeriodOptions as $i => $obj) : ?>
					<?php
						$name = $obj['code_name'];
						$url = $obj['url'];
					?>
					<div class="input">
						<?php if ($obj['checked'] == true) : ?>
							<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_period" checked>
							<?php
								echo $this->Form->hidden("$alias.competency_period", ['value' => $obj['id']]);
							?>
						<?php else : ?>
							<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_period" onclick="window.location.href='<?= $this->Url->build($url); ?>'">
						<?php endif ?>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
