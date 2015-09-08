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
		if ($.fn.iCheck) {
			$('.icheck-input').iCheck({
				checkboxClass: 'icheckbox_minimal-grey',
				radioClass: 'iradio_minimal-grey',
				inheritClass: true
			}).on ('ifChanged', function (e) {
				$(e.currentTarget).trigger ('change');
			});
		}
	},
	
	initTableCheckable: function() {
		if ($.fn.tableCheckable) {
			$('.table-checkable')
		        .tableCheckable ()
			        .on ('masterChecked', function (event, master, slaves) { 
			            if ($.fn.iCheck) { 
			            	$(slaves).each(function(){
			            		if(! $( this ).is(':disabled') ){
			            			$( this ).iCheck( 'update' );
			            		}
			            	});
			            }
			        })
			        .on ('slaveChecked', function (event, master, slave) {
			            if ($.fn.iCheck) { $(master).iCheck ('update'); }
			        });
		}
	}
};
</script>
