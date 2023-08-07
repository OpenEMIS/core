<div class="font-toggle btn-group">
	<button id='small' class="btn btn-default btn-xs" type="button"><span>A</span></button>
	<button id='medium' class="btn btn-default btn-xs" type="button"><span>A</span></button>
	<button id='large' class="btn btn-default btn-xs" type="button"><span>A</span></button>
</div>

<script type="text/javascript">
	$('#small').click(function(){
    console.log('clicked');
	    $('td').removeClass('medium').addClass('small');
	});

	$('#small').click(function(){
    console.log('clicked');
	    $('td').removeClass('large').addClass('small');
	});

	$('#medium').click(function(){
	    $('td').removeClass('small').addClass('medium');
	});

	$('#medium').click(function(){
    console.log('clicked');
	    $('td').removeClass('large').addClass('medium');
	});

	$('#large').click(function(){
	    $('td').removeClass('medium').addClass('large');
	});

</script>