<script type="text/javascript">
    $(document).ready(function() {
    $('textarea[name]').css({
        'height': '15pc',
        'width': '40% !important'
    });
    //POCOR-9131[START]
    // For table <input> fields
    $('table input').css({
            'min-width': '90px'
        });

    // For table .input-select-wrapper if needed
    $('.input-select-wrapper').css({
        'min-width': '90px'
    });
    //POCOR-9131[END]
});
</script>