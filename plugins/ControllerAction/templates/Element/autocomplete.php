<?php
/**
 * ControllerAction autocomplete element.
 *
 * POCOR-9771: this element can now be rendered more than once on the same form
 * (e.g. Counsellings has both Counselor and Requester). Previously it used the
 * fixed ids "searchInput" / "hiddenSearchField", so a pick in one field was
 * written into whichever hidden input came first in the DOM. Now:
 *  - ids are suffixed per field,
 *  - the hidden value input is resolved relative to the text input being used,
 *  - each input is bound once with its own url,
 *  - the saved value is rendered on edit (text label + hidden id), so saving
 *    without re-searching keeps the stored value.
 */
$fieldKey = preg_replace('/[^A-Za-z0-9_]/', '_', $attr['model'] . '_' . $attr['field']);
$searchInputId = 'searchInput_' . $fieldKey;
$hiddenFieldId = 'hiddenSearchField_' . $fieldKey;

// Text shown in the search box (e.g. "OpenEMIS ID - Name"), set by the table's onUpdateField.
$displayValue = isset($options['value']) && !is_array($options['value']) ? (string)$options['value'] : '';

// Stored id for the hidden input: posted data / entity value.
$storedValue = '';
if (isset($entity) && $entity instanceof \Cake\Datasource\EntityInterface) {
    $rawValue = $entity->get($attr['field']);
    if (!is_null($rawValue) && !is_array($rawValue) && !is_object($rawValue)) {
        $storedValue = (string)$rawValue;
    }
}
// Never show a label without the id behind it (or vice versa).
if ($storedValue === '') {
    $displayValue = '';
}

$loadingImg = $this->Html->image('OpenEmis.loader.gif', ['plugin' => 'false']);
?>
<script>
    $(function() {
        Autocomplete.init();
    });

    var Autocomplete = {
        uiItems: {},

        // POCOR-9771: hidden value input belonging to the given text input
        hiddenFor: function(input) {
            return $(input).closest('.input').find('input[type=hidden][data-autocomplete-hidden]');
        },

        // POCOR-9873: spinner belonging to the given text input, so one field's search
        // does not toggle every other field's spinner on a multi-autocomplete form
        spinnerFor: function(input) {
            return $(input).closest('.input').find('.loading_img');
        },

        // POCOR-9873: read per input instead of latching one shared flag, so a field that
        // opts in does not disable Save for unrelated fields on the same form
        blocksSaveOnNoResults: function(input) {
            return $(input).data('onnoresultsblocksave') === true;
        },

        init: function() {
            var submitBtn = $('button[name=submit]');
            // POCOR-9771: the element may be rendered (and init called) once per field; bind each input only once
            var unbound = $('input.autocomplete[data-autocomplete-hidden-id]').filter(function() {
                return !$(this).data('caAutocompleteBound');
            });
            if (unbound.length === 0) {
                return;
            }
            // keep the original "Create New" label only for an empty add form
            var hasValue = $('input[data-autocomplete-hidden]').filter(function() { return $(this).val() !== ''; }).length > 0;
            if (!hasValue) {
                submitBtn.text('Create New');
            }
            unbound.each(function() {
                $(this).data('caAutocompleteBound', true);
                Autocomplete.attachAutoComplete(this, Autocomplete.select);
            });
            $('.loading_img').hide();
            // POCOR-8128 / POCOR-9873: disable at init only when an input actually opts in
            if ($('input.autocomplete[data-onnoresultsblocksave="true"]').length > 0) {
                submitBtn.prop('disabled', true);
            }
        },

        keyup: function() {
            if ($(this).val().trim() === "") { // POCOR-8128 / POCOR-9771: check this input, not a global id
                Autocomplete.hiddenFor(this).val(''); // POCOR-9771: clearing the text clears the selected value
                var submitBtn = $('button[name=submit]');
                submitBtn.text('Save');
                // POCOR-8128 start

                // If blocking save on no results is enabled, disable the button
                if (Autocomplete.onNoResultsBlockSave) {
                    submitBtn.prop('disabled', true);
                }
                // POCOR-8128 end
            }

            var val = Autocomplete.uiItems;
            for (var i in val) {
                var target = $("input[autocomplete='" + i + "']"); // POCOR-8128
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
            Autocomplete.hiddenFor(event.target).val(ui.item.value); // POCOR-9771
            $(event.target).val(ui.item.label);
            Autocomplete.uiItems = val;

            var submitBtn = $('button[name=submit]');
            submitBtn.text('Save').prop('disabled', false);  // Re-enable button when valid selection is made // POCOR-8128
            return false;
        },

        focus: function(event, ui) {
            Autocomplete.hiddenFor(event.target).val(ui.item.value); // POCOR-9771
            $(event.target).val(ui.item.label);
            Autocomplete.select(event, ui);
            event.preventDefault();
        },

        searchComplete: function(event, ui) {
            Autocomplete.spinnerFor(event.target).hide(); // POCOR-9873
            var recordsCount = ui.content.length;
            var submitBtn = $('button[name=submit]');

            if (recordsCount === 0) {
                submitBtn.text('Create New');
                if (Autocomplete.blocksSaveOnNoResults(event.target)) { // POCOR-8128 / POCOR-9873
                    submitBtn.prop('disabled', true);  // Disable "Save" if no records are found and this input opts in
                }
            } else {
                submitBtn.text('Save').prop('disabled', false); // POCOR-8128
            }
        },

        beforeSearch: function(event, ui) {
            Autocomplete.spinnerFor(event.target).show(); // POCOR-9873
        },

        attachAutoComplete: function(element, callback) {
            // POCOR-9771: read url/length from this input (not the first .autocomplete on the page)
            var $el = $(element);
            var url = $el.attr('url');
            var length = $el.attr('length');
            if (length === undefined) {
                length = 2;
            }

            $el.autocomplete({
                source: url,
                minLength: length,
                select: callback,
                focus: Autocomplete.focus,
                response: Autocomplete.searchComplete,
                search: Autocomplete.beforeSearch
            }).on('keyup', Autocomplete.keyup);
        }
    } // POCOR-8128

</script>
<div class="input text">
    <label for="<?= h($searchInputId) ?>"><?= isset($attr['label']) ? $attr['label'] : h($attr['field']) ?></label>
    <input
        type="text"
        name="searchField"
        url="<?= h($options['url'] ?? '') ?>"
        class="<?= h($options['class'] ?? '') ?> autocomplete"
        placeholder="<?= h($options['placeholder'] ?? '') ?>"
        id="<?= h($searchInputId) ?>"
        value="<?= h($displayValue) ?>"
        data-autocomplete-hidden-id="<?= h($hiddenFieldId) ?>"
        data-onnoresultsblocksave="<?= isset($attr['onNoResultsBlockSave']) ? 'true' : 'false' ?>"
    >
    <input type="hidden" name="<?= h($attr['model']) ?>[<?= h($attr['field']) ?>]" value="<?= h($storedValue) ?>" id="<?= h($hiddenFieldId) ?>" data-autocomplete-hidden="1"/>
    <span class="loading_img"><?= $loadingImg ?></span>
</div>
