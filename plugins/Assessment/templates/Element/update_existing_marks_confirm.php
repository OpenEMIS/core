<?php
// POCOR-9759: confirmation warning before saving when Update Existing Marks = Yes.
// Scoped to this one element/page rather than the shared Add/Edit template.
?>
<script type="text/javascript">
(function ($) {
    $(document).ready(function () {
        var $form = $('#content-main-form');
        if ($form.length === 0) {
            return;
        }
        // Only on Edit - a new record has no existing marks to recalculate.
        var isEdit = $form.find('input[name$="[id]"]').length > 0;
        if (!isEdit) {
            return;
        }

        var $select = $form.find('select[name$="[update_existing_marks]"]');
        if ($select.length === 0) {
            return;
        }

        // POCOR-9759: confirm (or revert) at the moment the dropdown is changed to
        // Yes, rather than intercepting the Save button's click. The previous
        // approach prevented the click, showed the confirm, then re-triggered a
        // synthetic click on the same button to resubmit - but this form's own
        // onSubmit handler (ControllerActionHelper::getFormOptions()) rebinds a
        // fresh click handler on every submit, and a jQuery-triggered click on a
        // submit button does not reliably reproduce a trusted native submission.
        // In practice this meant the confirmed "Yes" sometimes never actually
        // submitted, and a subsequent real click went through with whatever the
        // field held at that point - not necessarily "Yes" - since the "already
        // confirmed" flag persisted. Handling this on 'change' means by the time
        // the user actually clicks Save, the field's value is already settled and
        // Save needs no special handling at all.
        function confirmRecalculation() {
            return window.confirm(<?= json_encode(__('Saving this change will recalculate and overwrite existing students\' assessment marks for this assessment period. This action cannot be undone.')) ?>);
        }

        var previousValue = $select.val();
        $select.on('change', function () {
            if ($select.val() == '1') {
                if (!confirmRecalculation()) {
                    $select.val(previousValue);
                    return;
                }
            }
            previousValue = $select.val();
        });

        // Code review fix: update_existing_marks is saved on the record, so an Edit
        // opened after a prior "Yes" save already shows the dropdown at Yes - its own
        // 'change' event above never fires just because the user edits the weight
        // instead, so a weight change went through silently with no warning even
        // though it still triggers the same mass recalculation. Catch that case here:
        // only while the flag is already Yes (isEdit is already guaranteed by the
        // early return above - a new record has nothing to recalculate anyway).
        var $weight = $form.find('[name$="[weight]"]');
        if ($weight.length > 0) {
            var previousWeight = $weight.val();
            $weight.on('change', function () {
                if ($select.val() == '1' && $weight.val() !== previousWeight) {
                    if (!confirmRecalculation()) {
                        $weight.val(previousWeight);
                        return;
                    }
                }
                previousWeight = $weight.val();
            });
        }
    });
})(jQuery);
</script>
