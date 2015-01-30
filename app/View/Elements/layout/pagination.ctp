<?php
if (!isset($displayCount)) {
	$displayCount = true;
}
?>

<div class="row pagination-wrapper">
	<div class="col-md-8 col-left">
		<?php
		$totalPages = $this->Paginator->counter('{:pages}');

		if ($totalPages > 1) :
		?>
		<ul class="pagination">
			<?php
			echo $this->Paginator->prev(
				'«',
				//'<i class="fa fa-step-backward"></i>',
				array('tag' => 'li', 'escape' => false),
				null,
				array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a', 'escape' => false)
			);

			echo $this->Paginator->numbers(array(
				'tag' => 'li', 
				'currentTag' => 'a', 
				'currentClass' => 'active', 
				'separator' => '', 
				'modulus' => 4, 
				'first' => 2,
				'last' => 2,
				'ellipsis' => '<li><a>...</a></li>'
			));

			echo $this->Paginator->next(
				'»',
				//'<i class="fa fa-step-forward"></i>',
				array('tag' => 'li', 'escape' => false),
				null,
				array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a', 'escape' => false)
			);
			?>
		</ul>
		<?php endif ?>
		
		<?php if ($displayCount) : ?>
		<div class="counter <?php echo $totalPages==1 ? 'no-paging' : '' ?>">
			<?php echo $this->Paginator->counter('Showing {:start} to {:end} of {:count} records'); ?>
		</div>
		<?php endif ?>
	</div>

	<?php if ($displayCount) : ?>
	<div class="col-md-4 displayLimit">
		<label style="font-weight: normal"><?php echo __('Display') ?></label>
		<?php
		echo $this->Form->input('limit', array(
			'label' => false,
			'div' => false,
			'options' => $pageOptions,
			'onchange' => "$(this).closest('form').submit()"
		));
		echo __('records');
		?>
	</div>
	<?php endif ?>
</div>
