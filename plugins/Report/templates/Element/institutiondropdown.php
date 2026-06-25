<script>
$(document).ready(function () {

	var currentUrl = window.location.href;

	//check url
    if (currentUrl.indexOf('/Reports/Staff/add') !== -1) {
    	var $select = $('#staff-institution-id-ids');
    }

    if (currentUrl.indexOf('/Reports/Institutions/add') !== -1) {
    	var $select = $('#institutions-institution-id-ids');
    }

    if (currentUrl.indexOf('/Reports/Students/add') !== -1) {
    	var $select = $('#students-institution-id-ids');
    }
    $select.find('option[value=""]').prop('selected', false);
    $select.trigger('chosen:updated');
    $select.on('change', function () {
        var values = $(this).val();
        if (values && values.includes('0')) {
            $(this).val(['0']);
        }
        $(this).trigger('chosen:updated');
    });

    $('form').on('submit', function () {
        var selected = $select.val();
        if (!selected || selected.length === 0 || selected.includes('')) {
           
        }
    });

});
</script>