<?php
error_log('bulk_actions_js template called - ' . __METHOD__);
$this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);
$this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]);

$csrfToken = $this->request->getAttribute('csrfToken');
?>
<script>
$(function() {
    var $table = $('table.table-checkable');
    if (!$table.length) return;

    // STEP 1: Ensure master checkbox exists in thead with proper structure
    // We need to add it server-side cannot be changed here. Let's add it manually if missing.
    var $headerCell = $table.find('thead th.checkbox-column');
    if ($headerCell.length && !$headerCell.find('input[type="checkbox"]').length) {
        // Inject master checkbox with proper wrapper structure
        var masterHtml = '<div class="selection-wrapper"><input type="checkbox" class="no-selection-label" kd-checkbox-radio><label></label></div>';
        $headerCell.html(masterHtml);
    }

    // STEP 2: Initialize tableCheckable - it will bind change handlers to existing checkboxes
    $table.tableCheckable();

    // STEP 3: UI toggles
    function updateDeleteButton() {
        var checked = $table.find('tbody .checkbox-column input:checked').length;
        $('#delete-selected-btn').prop('disabled', checked === 0);
    }

    function updatePointerEvents() {
        var hasChecked = $table.find('tbody .checkbox-column input:checked').length > 0;
        var $tbody = $table.find('tbody');
        if (hasChecked) {
            $tbody.css('pointer-events', 'none');
            $tbody.find('.checkbox-column').css('pointer-events', 'auto');
        } else {
            $tbody.css('pointer-events', '');
            $tbody.find('.checkbox-column').css('pointer-events', '');
        }
    }

    // Listen to both our custom events and tableCheckable's events
    $table.on('change', 'thead .checkbox-column input, tbody .checkbox-column input', function() {
        updateDeleteButton();
        updatePointerEvents();
    });
    $table.on('slaveChecked slaveUnchecked masterChecked masterUnchecked', function() {
        updateDeleteButton();
        updatePointerEvents();
    });

    // Also stop clicks on checkbox cells from bubbling to row handlers
    $table.on('click', 'tbody .checkbox-column', function(e) {
        e.stopImmediatePropagation();
        e.stopPropagation();
    });

    // Initial state
    updateDeleteButton();
    updatePointerEvents();

    // STEP 5: Delete selected handler
    $('#delete-selected-btn').click(function(e) {
        e.preventDefault();
        var selected = $table.find('tbody .checkbox-column input:checked');
        if (selected.length === 0) {
            alert('<?= __d('Alert', 'Please select at least one record') ?>');
            return;
        }
        var $form = $('<form>', {method: 'post', action: '<?= $deleteUrl ?>'});
        $form.append($('<input>', {type: 'hidden', name: '_csrfToken', value: '<?= h($csrfToken) ?>'}));
        selected.each(function() {
            $form.append($('<input>', {type: 'hidden', name: 'selected_ids[]', value: $(this).val()}));
        });
        $('#delete-selected-btn').html('<i class="fa fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);
        $form.appendTo('body').submit();
    });

    // STEP 6: Debug logging
    console.log('bulk_actions_js initialized');
});
</script>
<?php
