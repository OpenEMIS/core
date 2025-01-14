<script>
    $(function() {
        Autocomplete.init();
    });

    var Autocomplete = {
        loadingImg: '',
        uiItems: {},
        onNoResultsBlockSave: false,  // Default behavior is false

        init: function() {
            var submitBtn = $('button[name=submit]');
            submitBtn.text('Create New');
            this.attachAutoComplete('.autocomplete', Autocomplete.select);
            loadingImg = $('.loading_img');
            loadingImg.hide();
            if (Autocomplete.onNoResultsBlockSave) {
                submitBtn.prop('disabled', true);  // Disable button at init
            }
        },

        keyup: function() {
            if ($('#searchInput').val().trim() === "") {
                var submitBtn = $('button[name=submit]');
                submitBtn.text('Save');

                // If blocking save on no results is enabled, disable the button
                if (Autocomplete.onNoResultsBlockSave) {
                    submitBtn.prop('disabled', true);
                }
            }

            var val = Autocomplete.uiItems;
            for (var i in val) {
                var target = $("input[autocomplete='" + i + "']");
                if ((typeof target !== 'string') && (JSON.stringify(target.get(0)) !== '{}')) {
                    if (target.get(0).tagName.toUpperCase() === 'INPUT') {
                        target.val('');
                    } else {
                        target.html('');
                    }
                }
            }
        },

        select: function(event, ui) {
            var val = ui.item.value;
            for (var i in val) {
                var element = $("input[autocomplete='" + i + "']");
                if (element.length > 0) {
                    if (element.get(0).tagName.toUpperCase() === 'INPUT') {
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
            submitBtn.text('Save').prop('disabled', false);  // Re-enable button when valid selection is made
            return false;
        },

        focus: function(event, ui) {
            $("#hiddenSearchField").val(ui.item.value);
            this.value = ui.item.label;
            Autocomplete.select(event, ui);
            event.preventDefault();
        },

        searchComplete: function(event, ui) {
            if (loadingImg.length === 1) {
                loadingImg.hide();
                var recordsCount = ui.content.length;
                var submitBtn = $('button[name=submit]');

                if (recordsCount === 0) {
                    submitBtn.text('Create New');
                    if (Autocomplete.onNoResultsBlockSave) {
                        submitBtn.prop('disabled', true);  // Disable "Save" if no records are found and onNoResultsBlockSave is true
                    }
                } else {
                    submitBtn.text('Save').prop('disabled', false);
                }
            }
        },

        beforeSearch: function(event, ui) {
            if (loadingImg.length === 1) {
                loadingImg.show();
            }
        },

        attachAutoComplete: function(element, callback) {
            var url = $(element).attr('url');
            var length = $(element).attr('length');
            var onNoResultsBlockSave = $(element).data('onnoresultsblocksave');  // Get the attribute value from HTML
            console.log(onNoResultsBlockSave);
            if (onNoResultsBlockSave !== undefined && onNoResultsBlockSave === true) {
                Autocomplete.onNoResultsBlockSave = true;  // Set the custom flag to block save on no results
            }

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
            }).on('keyup', Autocomplete.keyup);
        }
    }

</script>
<?php
$loadingImg =  $this->Html->image('OpenEmis.loader.gif', ['plugin' => 'false']);
?>
<div class="input text">
    <label for="<?= $attr['field'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
    <input
        type="text"
        name="searchField"
        url="<?= $options['url'] ?>"
        class="<?= $options['class'] ?> autocomplete"
        placeholder="<?= $options['placeholder'] ?>"
        id="searchInput"
        data-onnoresultsblocksave="<?= isset($attr['onNoResultsBlockSave']) ? 'true' : 'false' ?>"
    >
    <input type="hidden" name="<?= $attr['model'] ?>[<?= $attr['field'] ?>]" value="" id="hiddenSearchField"/>
    <span class="loading_img"><?= $loadingImg ?></span>
</div>
