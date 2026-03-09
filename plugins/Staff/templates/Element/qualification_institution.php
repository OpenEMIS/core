<script>
$(document).ready(function () {
    /* ================================
        POCOR-9531
       ================================ */
    var $select = $('#qualifications-qualification-institution-ids');

    $select.chosen({
        no_results_text: "No results match"
    });

    /* ================================
       FORCE SINGLE SELECTION. user select only one institution at a time
       ================================ */
    $select.on('change', function (evt, params) {

        // If something was selected
        if (params && params.selected) {

            // Deselect everything first
            $select.find('option').prop('selected', false);

            // Select ONLY the latest clicked/added value
            $select.find('option[value="' + params.selected + '"]')
                   .prop('selected', true);

            // Update chosen UI
            $select.trigger('chosen:updated');
        }
    });

    /* ================================
       ADD NEW VALUE BY TYPING
       ================================ */
    var inputField = $select.next('.chosen-container').find('input');

    inputField.on('keyup blur', function (e) {

        if (e.type === 'keyup' && e.key !== 'Enter') return;

        var inputValue = $(this).val().trim();
        if (!inputValue) return;

        // Remove existing selections
        $select.find('option:selected').prop('selected', false);

        var $existingOption = $select.find('option').filter(function () {
            return $(this).text().toLowerCase() === inputValue.toLowerCase();
        });

        if ($existingOption.length) {
            $existingOption.prop('selected', true);
        } else {
            var newValue = inputValue.toLowerCase().replace(/\s+/g, ' ');
            var newOption = new Option(inputValue, newValue, true, true);
            $select.append(newOption);
        }

        $select.trigger('chosen:updated');
        $(this).val('');
    });
});
</script>
