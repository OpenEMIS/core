<?php
// Ensure required scripts are loaded
$this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);
$this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]);

// Get CSRF token
$csrfToken = $this->request->getAttribute('csrfToken');
?>
<script>
$(function() {
    var $table = $('table.table-checkable');
    if ($table.length) {
        $table.tableCheckable();

        // Inject master checkbox into empty header cell
        $table.find('thead th.checkbox-column').first().html(
            '<input type="checkbox" class="no-selection-label" kd-checkbox-radio/>'
        );

        // Enable/disable delete button based on selection
        function toggleDeleteButton() {
            var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
            $('#delete-selected-btn').prop('disabled', checked === 0);
        }

        $table.on('slaveChecked', toggleDeleteButton);
        $table.on('masterChecked', toggleDeleteButton);

        // Delete selected handler
        $('#delete-selected-btn').click(function(e) {
            e.preventDefault();
            var selected = $table.find('tbody .checkbox-column :checkbox:checked');
            if (selected.length === 0) {
                alert('<?= __d('Alert', 'Please select at least one record') ?>');
                return;
            }
            if (!confirm('<?= __d('Alert', 'Delete selected records? This cannot be undone.') ?>')) {
                return;
            }
            // Build form and submit
            var $form = $('<form>', {
                method: 'post',
                action: '<?= $deleteUrl ?>'
            });
            // Add CSRF token
            var csrfToken = '<?= h($csrfToken) ?>';
            $form.append($('<input>', {type: 'hidden', name: '_csrfToken', value: csrfToken}));
            // Add selected IDs
            selected.each(function() {
                $form.append($('<input>', {type: 'hidden', name: 'selected_ids[]', value: $(this).val()}));
            });
            // Show spinner on button
            $('#delete-selected-btn').html('<i class="fa fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);
            $form.appendTo('body').submit();
        });
    }
});
</script>
<?php
$this->end();
?>
