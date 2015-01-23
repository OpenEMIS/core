<script type="text/javascript">
$(document).ready(function() {
	Chosen.init();
	FuelUX.init();
});

var Chosen = {
	init: function() {
		if ($('.chosen-select').length>0) {
			$('.chosen-select').chosen({allow_single_deselect:true});
		}
		//resize the chosen on window resize
		
		$(window)
		.off('resize.chosen')
		.on('resize.chosen', function() {
			$('.chosen-select').each(function() {
				 var $this = $(this);
				 $this.next().css({'width': $this.parent().width()});
			})
		}).trigger('resize.chosen');
	}
};

var FuelUX = {
	init: function() {
		this.initWizard();
	},

	initWizard: function() {
		$('.fuelux .wizard').on('finished.fu.wizard', function(evt, data) {
			$(this).find('form').submit();
		});
	}
}

</script>
