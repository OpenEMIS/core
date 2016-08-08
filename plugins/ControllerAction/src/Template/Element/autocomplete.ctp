<script>
$(function() {
	Autocomplete.init();
});

var Autocomplete = {
	loadingImg: '',
	uiItems: {},

	init: function() {
		var submitBtn = $('button[name=submit]');
		submitBtn.text('Create New');
		this.attachAutoComplete('.autocomplete', Autocomplete.select);
		loadingImg = $('.loading_img');
		loadingImg.hide();
	},

	keyup: function() {
		if($('#searchInput').val() == ""){
			var submitBtn = $('button[name=submit]');
			submitBtn.text('Save');
		}

		var val = Autocomplete.uiItems;
		for(var i in val) {
			target = $("input[autocomplete='"+i+"']");
			if( (typeof target !== 'string')  && (JSON.stringify(target.get(0)) !== '{}')){
				if(target.get(0).tagName.toUpperCase() === 'INPUT') {
					target.val('');
				} else {
					target.html('');
				}
			}
		}
	},
	
	select: function(event, ui) {
		var val = ui.item.value;
		for(var i in val) {
			element = $("input[autocomplete='"+i+"']");
			if (element.length > 0) {
				if(element.get(0).tagName.toUpperCase() === 'INPUT') {
					element.val(val[i]);
				} else {
					element.html(val[i]);
				}
			}
		}
		$("#hiddenSearchField").val(ui.item.value);
		this.value = ui.item.label;
		Autocomplete.uiItems = val;
		var submitBtn = $('button[name=submit]');
		submitBtn.text('Save');
		return false;
	},
	
	focus: function( event, ui ) {
		$("#hiddenSearchField").val(ui.item.value);
		this.value = ui.item.label;
		Autocomplete.select(event, ui);
		event.preventDefault();
	},
			
	searchComplete: function( event, ui ) {
		if(loadingImg.length === 1){
			loadingImg.hide();
			var recordsCount = ui.content.length;
			var submitBtn = $('button[name=submit]');
			if(recordsCount === 0){
				submitBtn.text('Create New');
			} else {
				submitBtn.text('Save');
			}
		}
	},
			
	beforeSearch: function( event, ui ) {
		if(loadingImg.length === 1){
			loadingImg.show();
		}
	},

	attachAutoComplete: function( element, callback ) {
		var url = $(element).attr('url');
		var length = $(element).attr('length');
		
		if (length === undefined) {
			length = 2;
		}

		$(element).autocomplete({
			source: url,
			minLength: length,
			select: callback,
			focus: Autocomplete.focus,
			response: Autocomplete.searchComplete,
			search: Autocomplete.beforeSearch
		}).on( 'keyup', Autocomplete.keyup );
	}

}
</script>
<?php
$loadingImg =  $this->Html->image('OpenEmis.loader.gif', ['plugin' => 'false']);
?>
<div class="input text">
	<label for="<?= $attr['field'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<input type="text" name="searchField" url="<?= $options['url'] ?>" class="<?= $options['class'] ?>" placeholder="<?= $options['placeholder'] ?>" id="searchInput">
	<input type="hidden" name="<?= $attr['model'] ?>[<?= $attr['field'] ?>]" value="" id="hiddenSearchField"/>
	<span class="loading_img"><?= $loadingImg ?></span>
</div>
