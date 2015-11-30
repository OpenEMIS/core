<script type="text/javascript">
$(document).ready(function() {
	Chosen.init();
	Checkable.init();
	$('[data-toggle="tooltip"]').tooltip();
	$('.focus').focus();

	$('.table-responsive').on('show.bs.dropdown', function () {
		$('.table-responsive').css( "overflow-x", "inherit" );
	});

	$('.table-responsive').on('hide.bs.dropdown', function () {
	    $('.table-responsive').css( "overflow-x", "auto" );
	})

	addScrollbarShadow('.table-responsive');
	addScrollbarShadow('.table-in-view');

});


//fade out any datepicker/timepicker when it reached the top header when scrolling
var t ;
$( document ).on('DOMMouseScroll mousewheel scroll', function() {
    $('.dropdown-menu').each(function () {
        if (($(this).offset().top - $(window).scrollTop()) < 5) {
            $(this).stop().fadeOut(30);
        } else {
            $(this).stop().fadeIn(0);
            $(".time").click(function(){
			    $(".bootstrap-timepicker-widget").css("display","inline-block", "important");
			});
        }
    });       
});

jQuery(window).load(function(){
	jQuery('.load-content').delay(500).fadeOut();
});

var Chosen = {
	init: function() {
		if ($('.chosen-select').length>0) {
			$('.chosen-select').chosen({allow_single_deselect: true});
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
			            if ($.fn.iCheck) { $(slaves).iCheck ('update'); }
			        })
			        .on ('slaveChecked', function (event, master, slave) {
			            if ($.fn.iCheck) { $(master).iCheck ('update'); }
			        });
		}
	}
};

function addScrollbarShadow(selector) {
	$(selector).each(function(index) {
		horzScrollbarDetect(selector, index);
		$(this).on('scroll', function() {
			horzScrollbarDetect(selector, index);
		});
	});

	$(window).resize(function() {
		$(selector).each(function(index) {
			horzScrollbarDetect(selector, index);
		});
	});
}

function horzScrollbarDetect(selector, index) {
	var $scrollable = $(selector);
	if ($scrollable.get(index)) {
		var element = $scrollable.get(index);
		var totalWidth = element.scrollWidth;
		var leftPosition = element.scrollLeft;
		var viewWidth = element.offsetWidth;

		var rightPosition = totalWidth-(leftPosition+viewWidth);
		$wrapper = $(selector).eq(index).parent();

		if (rightPosition > 0) {
			$wrapper.addClass('shadow-right');	
		} else {
			$wrapper.removeClass('shadow-right');
		}
		if (leftPosition > 0) {
			$wrapper.addClass('shadow-left');	
		} else {
			$wrapper.removeClass('shadow-left');
		}
	}
}

</script>
