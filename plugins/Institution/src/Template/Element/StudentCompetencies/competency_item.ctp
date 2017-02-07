<?php
	$competencyItemCount = isset($attr['competencyItemCount']) ? $attr['competencyItemCount'] : 0;
	$competencyItemOptions = isset($attr['competencyItemOptions']) ? $attr['competencyItemOptions'] : [];
	$alias = $ControllerAction['table']->alias();
	$this->Form->unlockField('competency_item');
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php if ($competencyItemCount == 0) : ?>
		<?php echo $this->Label->get('StudentCompetencies.noItem'); ?>
	<?php else : ?>
		<div class="input-selection">
			<?php foreach($competencyItemOptions as $i => $obj) : ?>
				<?php
					$name = $obj['name'];
					$url = $obj['url'];
				?>
				<div class="input">
					<?php if ($obj['checked'] == true) : ?>
						<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_item" checked>
					<?php else : ?>
						<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_item" onclick="window.location.href='<?= $this->Url->build($url); ?>'">
					<?php endif ?>
				</div>
			<?php endforeach ?>
		</div>
	<?php endif ?>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<?php if ($competencyItemCount == 0) : ?>
		<?php
			echo $this->Form->input('competency_item', [
				'class' => 'form-control',
				'disabled' => 'disabled',
				'value' => $this->Label->get('StudentCompetencies.noItem')
			]);
		?>
	<?php else : ?>
		<div class="input">
			<label><?= $attr['label']; ?></label>
			<div class="input-selection">
				<?php foreach($competencyItemOptions as $i => $obj) : ?>
					<?php
						$name = $obj['name'];
						$url = $obj['url'];
					?>
					<div class="input">
						<?php if ($obj['checked'] == true) : ?>
							<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_item" checked>
							<?php
								echo $this->Form->hidden("$alias.competency_item", ['value' => $obj['id']]);
							?>
						<?php else : ?>
							<input kd-checkbox-radio="<?= $name; ?>" type="radio" name="competency_item" onclick="window.location.href='<?= $this->Url->build($url); ?>'">
						<?php endif ?>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
