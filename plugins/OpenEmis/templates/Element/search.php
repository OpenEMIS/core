<div class="search">
	<div class="input-group">
	<?php
		$session = $this->getRequest()->getSession();
		// show value in search input
		$alias = $this->request->getParam('plugin'). '.' .$this->request->getParam('action');
		$alias = $session->check('search.search_alias') ? $session->read('search.search_alias') : $alias;
		$search = $session->check($alias.'.search.key') ? $session->read($alias.'.search.key') : '';
		$howManyStudents = $session->check('is_any_student') ? $session->read('is_any_student') : 1;
		if ($howManyStudents >= 0) {
	?>
		<?=
		$this->Form->input('Search.searchField', [
			'label' => false,
			'class' => 'form-control search-input focus',
			'data-input-name' => 'Search[searchField]',
			'placeholder' => __('Search'),
			'onkeypress' => 'if (event.keyCode == 13) jsForm.submit()',
			'value' => $search
		]);

		$this->Form->create();
		$this->Form->unlockField('Search.searchField');
		?>

		<span class="input-group-btn">
			<button class="btn btn-xs btn-reset" type="button" onclick="$('.search-input').val('');jsForm.submit()"><i class="fa fa-close"></i></button>
			<button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="<?= __('Search') ?>" type="button" onclick="jsForm.submit()">
				<i class="fa fa-search"></i>
			</button>

			<?php
            if (
                isset($indexElements['advanced_search']) || // v3
                isset($advanced_search) // v4
            ) : ?>
			<button id="search-toggle" class="btn btn-default btn-xs" ng-class="selectedState" data-toggle="tooltip" data-placement="bottom" title="<?= __('Advanced Search') ?>" type="button" ng-click="toggleAdvancedSearch()">
				<i class="fa fa-search-plus"></i>
			</button>
			<?php endif ?>
		</span>

	<?php } ?>
	</div>
</div>
