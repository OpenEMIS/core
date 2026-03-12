<?php
error_log('bulk_actions_js template called - ' . __METHOD__);
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
        // Inject master checkbox into header BEFORE initializing tableCheckable
        // Must match the row checkbox structure: wrapper + input + label
        $table.find('thead th.checkbox-column').first().html(
            '<div class="selection-wrapper"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/><label></label></div>'
        );

        // Initialize tableCheckable after checkbox is in place
        $table.tableCheckable();

        // Enable/disable delete button based on selection
        function toggleDeleteButton() {
            var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
            $('#delete-selected-btn').prop('disabled', checked === 0);
        }

        // Disable/enable table body clicks when checkboxes are selected
        function toggleTableClickability() {
            var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
            var $tbody = $table.find('tbody');

            if (checked > 0) {
                // When checkboxes are selected, disable all pointer events on tbody
                $tbody.css('pointer-events', 'none');
                // But re-enable pointer events on checkbox cells specifically
                $tbody.find('.checkbox-column').css('pointer-events', 'auto');
            } else {
                $tbody.css('pointer-events', '');
                $tbody.find('.checkbox-column').css('pointer-events', '');
            }
        }

        // Hook into tableCheckable events
        $table.on('slaveChecked masterChecked', function() {
            toggleDeleteButton();
            toggleTableClickability();
        });
        $table.on('slaveUnchecked masterUnchecked', function() {
            toggleDeleteButton();
            toggleTableClickability();
        });

        // Fallback: also listen for checkbox changes (more reliable)
        $table.on('change', 'tbody .checkbox-column input[type="checkbox"]', function() {
            toggleDeleteButton();
            toggleTableClickability();
        });

        // Additional fallback: set up a mutation observer to detect checkbox state changes
        // This catches changes made by tableCheckable that don't trigger events
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'checked') {
                        toggleDeleteButton();
                        toggleTableClickability();
                    }
                });
            });
            // Observe all checkboxes for attribute changes
            const checkboxes = $table.find('tbody .checkbox-column input[type="checkbox"]');
            checkboxes.each(function() {
                observer.observe(this, { attributes: true });
            });
        }

        // Final fallback: poll for checkbox state every 200ms
        // Only active when at least one checkbox is checked
        let pollInterval;
        function startPolling() {
            if (pollInterval) return;
            pollInterval = setInterval(function() {
                const checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
                const $btn = $('#delete-selected-btn');
                const shouldBeDisabled = checked === 0;
                if ($btn.prop('disabled') !== shouldBeDisabled) {
                    $btn.prop('disabled', shouldBeDisabled);
                }
            }, 200);
        }
        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        // Start polling when any checkbox is clicked, stop when all unchecked
        $table.on('click', 'tbody .checkbox-column input[type="checkbox"]', function() {
            setTimeout(function() {
                const checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
                if (checked > 0) {
                    startPolling();
                } else {
                    stopPolling();
                }
            }, 50);
        });

        // CRITICAL: Prevent row clicks from navigating when checkboxes are selected
        // This overrides tableCheckable's row click handler
        $table.on('click', 'tbody tr', function(e) {
            var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
            if (checked > 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });

        // Also prevent action view links/buttons
        $table.on('click', 'tbody .action-view a, tbody .action-view button', function(e) {
            var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
            if (checked > 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });

        // For checkbox clicks specifically, we need to prevent the row handler
        // Use capture phase at the table level to intercept before tableCheckable
        $table[0].addEventListener('click', function(e) {
            var target = e.target;
            // Check if click is on a checkbox or inside a checkbox cell
            if (target.type === 'checkbox' || $(target).closest('.checkbox-column').length) {
                var checked = $table.find('tbody .checkbox-column :checkbox:checked').length;
                if (checked > 0) {
                    e.stopImmediatePropagation();
                    // Do NOT prevent default - let checkbox toggle
                }
            }
        }, true); // Use capture phase to intercept before jQuery handlers

        // Initial state check
        toggleDeleteButton();
        toggleTableClickability();

        // Delete selected handler (no confirmation)
        $('#delete-selected-btn').click(function(e) {
            e.preventDefault();
            var selected = $table.find('tbody .checkbox-column :checkbox:checked');
            if (selected.length === 0) {
                alert('<?= __d('Alert', 'Please select at least one record') ?>');
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
// No block end needed
?>
