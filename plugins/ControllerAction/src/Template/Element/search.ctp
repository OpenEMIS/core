<div class="search">
	<?= $this->Form->create(NULL, ['url' => $_buttons['index']['url']]) ?>
		<div class="input-group">
			<?= 
			$this->Form->input('Search.searchField', [
				'label' => false, 
				'class' => 'form-control search-input focus', 
				'placeholder' => __('Search')
			]);
			?>
			<span class="input-group-btn">
				<button class="btn btn-xs btn-reset" type="button" onclick="$('.search-input').val('');$(this).closest('form').submit()"><i class="fa fa-close"></i></button>
				<button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="Search" type="button" onclick="$(this).closest('form').submit()"><i class="fa fa-search"></i></button>
				<!-- <button id="search-toggle" class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="Advanced Search" type="button" alt="Advanced Search"><i class="fa fa-search-plus"></i></button> -->
			</span>

		</div>
	<?= $this->Form->end() ?>
</div>
