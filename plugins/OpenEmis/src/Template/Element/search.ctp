<div class="search">
	<div class="input-group">
		<?=
		$this->Form->input('Search.searchField', [
			'label' => false,
			'class' => 'form-control search-input focus',
			'data-input-name' => 'Search[searchField]',
			'placeholder' => __('Search'),
			'onkeypress' => 'if (event.keyCode == 13) jsForm.submit()'
		]);
		$this->Form->unlockField('Search.searchField');
		?>
		<span class="input-group-btn">
			<button class="btn btn-xs btn-reset" type="button" onclick="$('.search-input').val('');jsForm.submit()"><i class="fa fa-close"></i></button>
			<button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="<?= __('Search') ?>" type="button" onclick="jsForm.submit()">
				<i class="fa fa-search"></i>
			</button>

			<?php
            if (
                array_key_exists('advanced_search', $indexElements) || // v3
                isset($advanced_search) // v4
            ) : ?>
			<button id="search-toggle" class="btn btn-default btn-xs" ng-class="selectedState" data-toggle="tooltip" data-placement="bottom" title="<?= __('Advanced Search') ?>" type="button" ng-click="toggleAdvancedSearch()">
				<i class="fa fa-search-plus"></i>
			</button>
			<?php endif ?>
		</span>

	</div>
</div>
