<div class="search">
	<?= $this->Form->create(NULL, ['url' => $url]) ?>
		<div class="input-group">
			<?= 
			$this->Form->input('Search.searchField', [
				'label' => false, 
				'class' => 'form-control search-input focus',
				'data-form-name' => 'Search[searchField]',
				'placeholder' => __('Search')
			]);
			?>
			<span class="input-group-btn">
				<button class="btn btn-xs btn-reset" type="button" onclick="$('.search-input').val('');$(this).closest('form').submit()"><i class="fa fa-close"></i></button>
				<button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="Search" type="button" onclick="$(this).closest('form').submit()"><i class="fa fa-search"></i></button>
				
				<?php if (array_key_exists('advanced_search', $indexElements)) : ?>
				<button id="search-toggle" class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="<?= __('Advanced Search') ?>" type="button"><i class="fa fa-search-plus"></i></button>
				<?php endif ?>
			</span>

		</div>
	<?= $this->Form->end() ?>
</div>
