$( document ).ready( function() {
	Reorder.init();
});

var Reorder = {
	init: function() {
		var currentOrder = Reorder.getOrder("td","data-row-id");
		var originalOrder = currentOrder;

		var preventCollapse = function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		};

		// Sortable only when mouse over the arrows
		$( "#sortable tbody" ).on("mousedown", "td.sorter", function() {
		//$( "td.sorter").mousedown(function() {
			// Sortable on tbody
			var url = $(event.target).closest('table').attr('url');
			var tbody = $(this).closest('tbody');
			tbody.sortable({
				forcePlaceholderSize: true,	
				helper: preventCollapse,
				cursor: "none",
				axis: "y",
				stop: function(event, ui){
					if (url) {
						currentOrder = Reorder.getOrder("td","data-row-id");
						if(! Reorder.compare(currentOrder,originalOrder)){
							$.ajax({
								cache: false,
								url: url,
								type: "POST",
								data: {
									ids: JSON.stringify(currentOrder)
								},
								traditional: true,
								success: function(data){
									originalOrder = currentOrder;
								}
							});
						}
					} else {
						Reorder.updateOrder();
						SurveyForm.updateSection();
					}
				}
			});
			
			// Re-enable the sortable if the mouse has already been release
			tbody.sortable('enable');
		})

		// Disable sortable on any other portion of the body if the mouse is move away
		$( document ).on("mouseup", function(){
			$( "#sortable tbody" ).sortable();
			$( "#sortable tbody" ).sortable('disable');
		});
	},

	updateOrder: function(){
		var count = 1;
		$(".order").each(function(){
			$(this).val(count++);
		});
	},

	compare: function(array1, array2) {
		if (array1.length==array2.length) {
			for (i = 0; i<array1.length; i++) {
				if (!(array1[i] == array2[i])) {
					return false;
				}
			}
			return true;
		}
	},

	getOrder: function(htmlTag, attributeName) {
		return $( htmlTag ).map(function(){
			return $(this).attr( attributeName );
		}).get();
	},
};
