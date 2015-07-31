<script type="text/javascript">
$(document).ready(function() {
	Chosen.init();
	Checkable.init();
	$('[data-toggle="tooltip"]').tooltip();
	$('.focus').focus();
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

var Checkable = {
	init: function() {
		this.initICheck();
		this.initTableCheckable();
	},

	initICheck: function() {
		var disabledArray = new Array();
		$('.icheck-input').each(function(){
			if($(this).is(':disabled')){
				disabledArray.push(this);
			}
		});
		if ($.fn.iCheck) {
			$('.icheck-input').iCheck({
				checkboxClass: 'icheckbox_minimal-grey',
				radioClass: 'iradio_minimal-grey',
				inheritClass: true
			}).on ('ifChanged', function (e) {
				$(e.currentTarget).trigger ('change');
				$( disabledArray ).each(function(){
					$(this).iCheck('check');
				});
			});
		}
	},
	
	initTableCheckable: function() {
		if ($.fn.tableCheckable) {
			$('.table-checkable')
		        .tableCheckable ()
			        .on ('masterChecked', function (event, master, slaves) { 
			            if ($.fn.iCheck) { $(slaves).iCheck ('update'); }
			        })
			        .on ('slaveChecked', function (event, master, slave) {
			            if ($.fn.iCheck) { $(master).iCheck ('update'); }
			        });
		}
	}
};

$('.table-responsive').on('show.bs.dropdown', function () {
     $('.table-responsive').css( "overflow", "inherit" );
});

$('.table-responsive').on('hide.bs.dropdown', function () {
     $('.table-responsive').css( "overflow", "auto" );
})

</script>
