<?php //POCOR-9870: the add form reloads itself by POST (dropdown changes); turn its history entry into a GET
// so going back to it after Start Scheduling does not show "Confirm Form Resubmission" ?>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, '', window.location.href);
    }
</script>
