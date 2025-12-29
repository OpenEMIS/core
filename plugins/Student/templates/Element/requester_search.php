<!--POCOR-9523 -->
<script>
	$('#counsellings-requester-id option').each(function() {
    let text = $(this).text();

    // Split into main part and last part
    let parts = text.split('ID');
    if (parts.length > 1) {
        let main = parts[0].trim();
        let lastpart = parts[1].trim();
        // Rebuild using <span> for the last part. 
        // No need to display the ID in the dropdown, 
        // but allow users to search using the identity number.
        
        $(this).html(main + ' <span style="font-size:0px;">ID ' + lastpart + '</span>');
    }
});

</script>