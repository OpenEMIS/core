<?php if (isset($modals)) : ?>
	<?php foreach ($modals as $id => $modal) : ?>
	<?php
		$title = isset($modal['title']) ? $modal['title'] : '';
		$content = isset($modal['content']) ? $modal['content'] : '';
	?>
	<div class="modal fade" id="<?= $id ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<?php
				if (isset($modal['form']) && $modal['form']) {
					$template = $this->ControllerAction->getFormTemplate();
					$this->Form->templates($template);
					echo $this->Form->create($modal['form']['model'], $modal['form']['formOptions']);
					if (isset($modal['form']['fields'])) {
						foreach ($modal['form']['fields'] as $name => $attr) {
							if (isset($attr['unlockField'])) {
								if ($attr['unlockField']) {
									$this->Form->unlockField($name);
								}
								unset($attr['unlockField']);
							}
							echo $this->Form->input($name, $attr);
						}
					}
				}
				?>
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?= $title ?></h4>
				</div>

				<div class="modal-body">
					<?php
					if (isset($modal['contentFields'])) {
						$contentFields = $modal['contentFields'];
						foreach ($contentFields as $name => $attr) {
							echo $this->Form->input($name, $attr);
						}
					}
					?>
					<?php
					if (isset($modal['type']) && $modal['type'] == 'element') {
						echo $this->element($content);
					} else {
						echo $content;
					}
					?>
				</div>

				<div class="modal-footer">
					<?php
					if (!empty($modal['buttons'])) {
						foreach ($modal['buttons'] as $button) {
							echo $button;
						}
					}
					?>
					<?php if (isset($modal['cancelButton']) && $modal['cancelButton']) : ?>
					<button type="button" class="btn btn-outline" data-dismiss="modal"><?= __('Cancel') ?></button>
					<?php endif ?>
				</div>

				<?php if (isset($modal['form']) && $modal['form']) : ?>
				<?= $this->Form->end() ?>
				<?php endif ?>
			</div>
		</div>
	</div>

	<?php endforeach ?>
<?php endif ?>
