<?php
//POCOR-9257: Bulk actions JS for webhook queue/logs — select all + delete selected
if (empty($deleteUrl)) return;
?>
<script>
$(document).ready(function () {
    // Inject master checkbox into the _select column header
    var $th = $('th.checkbox-column');
    if ($th.length) {
        $th.html('<input type="checkbox" id="webhook-select-all">');
    }

    // Toggle all checkboxes when master is clicked
    $(document).on('change', '#webhook-select-all', function () {
        var checked = $(this).is(':checked');
        $('.webhook-row-checkbox').prop('checked', checked);
        toggleDeleteButton();
    });

    // Toggle delete button when any row checkbox changes
    $(document).on('change', '.webhook-row-checkbox', function () {
        toggleDeleteButton();
        // Update master checkbox state
        var total = $('.webhook-row-checkbox').length;
        var selected = $('.webhook-row-checkbox:checked').length;
        $('#webhook-select-all').prop('indeterminate', selected > 0 && selected < total);
        $('#webhook-select-all').prop('checked', selected === total && total > 0);
    });

    function toggleDeleteButton() {
        var hasSelected = $('.webhook-row-checkbox:checked').length > 0;
        $('#webhook-delete-selected-btn').prop('disabled', !hasSelected);
    }

    // Submit hidden form with selected IDs on delete button click
    $('#webhook-delete-selected-btn').on('click', function (e) {
        e.preventDefault();
        var ids = [];
        $('.webhook-row-checkbox:checked').each(function () {
            ids.push($(this).val());
        });
        if (ids.length === 0) return;

        if (!confirm('<?= __('Delete') ?>' + ' ' + ids.length + ' ' + '<?= __('record(s)?') ?>')) return;

        var $form = $('<form>', {
            method: 'POST',
            action: '<?= $deleteUrl ?>'
        });
        $.each(ids, function (i, id) {
            $form.append($('<input>', { type: 'hidden', name: 'selected_ids[]', value: id }));
        });
        $('body').append($form);
        $form.submit();
    });
});
</script>
