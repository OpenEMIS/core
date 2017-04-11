<?php
$params = $this->Paginator->params();
$totalRecords = array_key_exists('count', $params) ? $params['count'] : 0;
?>

<?php if ($totalRecords > 0) : ?>
<div class="pagination-wrapper" ng-class="disableElement">
	<?php
	$totalPages = $params['pageCount'];

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
		<?php
		$defaultLocale = $this->ControllerAction->locale();
		$this->ControllerAction->locale('en_US');
		?>
		<?php
			$paginateCountString = $this->Paginator->counter([
				'format' => '{{start}} {{end}} {{count}}'
			]);

			$paginateCountArray = explode(' ', $paginateCountString);
			$this->ControllerAction->locale($defaultLocale);
			echo sprintf(__('Showing %s to %s of %s records'), $paginateCountArray[0], $paginateCountArray[1], $paginateCountArray[2])
		?>
	</div>
	<div class="display-limit">
		<span><?= __('Display') ?></span>
		<?= $this->ControllerAction->getPageOptions() ?>
		<p><?= __('records') ?></p>
	</div>
</div>
<?php endif ?>
