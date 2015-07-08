<?php
$totalRecords = $this->Paginator->counter('{{count}}');
?>

<?php if ($totalRecords > 0) : ?>
<div class="pagination-wrapper">
	<?php
	$totalPages = $this->Paginator->counter('{{pages}}');

	if ($totalPages > 1) :
	?>
	<ul class="pagination">
		<?php
		echo $this->ControllerAction->getPaginatorButtons('prev');
		echo $this->ControllerAction->getPaginatorNumbers();
		echo $this->ControllerAction->getPaginatorButtons('next');
		?>
	</ul>
	<?php endif ?>
	<div class="counter">
		<?= $this->Paginator->counter([
			'format' => 'Showing {{start}} to {{end}} of {{count}} records'
		]) ?>
	</div>
	<div class="display-limit">
		<span><?= __('Display') ?></span>
		<?= $this->ControllerAction->getPageOptions() ?>
		<p><?= __('records') ?></p>
	</div>
</div>
<?php endif ?>
