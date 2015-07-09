<?php if (isset($modal['id'])) : ?>

<div class="modal fade" id="<?= $modal['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<?php
			echo $this->Form->create($model, $modal['formOptions']);
			if (isset($modal['fields'])) {
				foreach ($modal['fields'] as $name => $attr) {
					echo $this->Form->input($name, $attr);
				}
			}
			?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel"><?= $modal['title'] ?></h4>
			</div>

			<div class="modal-body"><?= $modal['content'] ?></div>

			<div class="modal-footer">
				<?php
				if (!empty($modal['buttons'])) {
					foreach ($modal['buttons'] as $button) {
						echo $button;
					}
				}
				?>
				<button type="button" class="btn btn-outline" data-dismiss="modal"><?= __('Cancel') ?></button>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>

<?php endif ?>
