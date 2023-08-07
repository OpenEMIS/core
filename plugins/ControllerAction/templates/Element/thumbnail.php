<?php
	$maxWidth = isset($attr['maxWidth']) ? $attr['maxWidth'] : 60;
?>

<?php if (isset($attr['src'])) : ?>
	<div class="table-thumb">
		<img src="<?= $attr['src'] ?>" data-holder-rendered="true" data-toggle="modal" data-target="#myModal" style="max-width:<?= $maxWidth ?>px;" />
	</div>

	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<?php if (isset($attr['title'])) : ?>
						<h4 class="modal-title" id="myModalLabel"><?= __($attr['title']) ?></h4>
					<?php endif ?>
				</div>

				<div class="modal-body">
					<img src="<?= $attr['src'] ?>" data-holder-rendered="true" />
				</div>
			</div>
		</div>
	</div>
<?php endif ?>
