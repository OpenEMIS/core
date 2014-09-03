/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
	$.expr[":"].containsNoCase = function(el, i, m) {
		var search = m[3];
		if (!search)
			return false;
		return new RegExp(search, "i").test($(el).text());
	};
	
	var xhr;
	var startSearch = false;
	
	if (window.location.hash) {
		var hashStr = window.location.hash;
		var searchStr = hashStr.substr(hashStr.indexOf('#') + 1, hashStr.length);
		
		if ($('#search').length > 0) {
			Visualizer.searchWithinHTML(searchStr);
		}
		
		if ($('#searchDB').length > 0) {
			$('#searchDB').val(searchStr);
			startSearch = true;
			xhr = Visualizer.searchThroughDB($('#searchDB'));
		}
		
	}
	
	
	$('#search').keypress(function(event) {
		if (event.which == 13) {
			event.preventDefault();
		}
	}).keyup(function() {
		var search = $(this).val();
		Visualizer.searchWithinHTML(search);
	});

	
	$('#searchDB').keypress(function(event) {
		if (event.which == 13) {
			event.preventDefault();
		}

	}).keyup(function(event) {
		if (xhr && xhr.readystate != 4) {
			xhr.abort();
		}

		var searchStr = $.trim($(this).val());
		if (searchStr.length > 1) {
			startSearch = true;
			xhr = Visualizer.searchThroughDB($(this));
//			xhr = $.ajax({
//				type: "POST",
//				dataType: 'json',
//				url: getRootURL() + $(this).attr('url'),
//				data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
//				success: function(data) {
//					//	alert(data);
//					$('#visualizer tbody').html(data['rows']);
//					$('#pagination').html(data['pages']);
//					$('#visualizer tbody td.checkbox-column input[type="checkbox"]').iCheck({
//						checkboxClass: 'icheckbox_minimal-blue'
//					}).on('ifChanged', function(e) {
//						$(e.currentTarget).trigger('change');
//					});
//
//					if (data['pages'] == '' || typeof data['pages'] === "undefined") {
//						$('#pagination').empty();
//					}
//				}
//			});
		} else {
			if (searchStr.length <= 1) {
				xhr = $.ajax({
					type: "POST",
					url: getRootURL() + $(this).attr('reseturl'),
					data: {searchStr: $(this).val()}
				});
			}
			if (startSearch) {
				window.location.href = getRootURL() + $('#areaLevel').attr('url') + '/' + $('#areaLevel').val();
			}
		}
	});

});

var Visualizer = {
	searchWithinHTML: function(search) {
		var row = $(".data-list").parent();
		row.show();
		if (search) {
			$(".data-list").not(":containsNoCase(" + search + ")").parent().hide();
		}

		$(".visualizer-list-table tr:visible:even td").css("background-color", "#f9f9f9");
		$(".visualizer-list-table tr:visible:odd td").css("background-color", "#ffffff");
		$('#search').val(search);
	},
	searchThroughDB : function(searchFDObj){
		var xhr = $.ajax({
				type: "POST",
				dataType: 'json',
				url: getRootURL() + searchFDObj.attr('url'),
				data: {searchStr: searchFDObj.val(), areaLvl: $('#areaLevel').val()},
				success: function(data) {
					//	alert(data);
					$('#visualizer tbody').html(data['rows']);
					$('#pagination').html(data['pages']);
					$('#visualizer tbody td.checkbox-column input[type="checkbox"]').iCheck({
						checkboxClass: 'icheckbox_minimal-blue'
					}).on('ifChanged', function(e) {
						$(e.currentTarget).trigger('change');
					});

					if (data['pages'] == '' || typeof data['pages'] === "undefined") {
						$('#pagination').empty();
					}
				}
			});
			
			return xhr;
	},
	formSubmit: function() {
		$('#visualizer form').submit();
	},
	dropdownChange: function(obj) {
		var searchStr = '';
		if ($('#search').length > 0) {
			if($('#search').val() != ''){
				searchStr = '#' + $('#search').val();
			}
		}
		if ($('#searchDB').length > 0) {
			if($('#searchDB').val() != ''){
				searchStr = '#' + $('#searchDB').val();
			}
		}
		window.location.href = getRootURL() + $(obj).attr('url') + '/' + $(obj).val() + searchStr;
	},
	radioChange: function(obj) {
		$.ajax({
			type: "POST",
			url: getRootURL() + $(obj).attr('url'),
			data: {sectionType: $(obj).attr('sectionType'), value: $(obj).val()},
			success: function(data) {
				location.reload();
			}
		});
	},
	checkboxChange: function(obj) {
		var checked = $(obj).parent().hasClass('checked') ? 'unchecked' : 'checked';

		$.ajax({
			type: "POST",
			url: getRootURL() + $(obj).attr('url'),
			data: {sectionType: $(obj).attr('sectionType'), value: $(obj).val(), checked: checked},
			success: function(data) {
				//location.reload();
			}
		});
	},
	checkboxChangeAll: function(obj) {
		//alert($(obj).attr('class'));
		var masterCheckbox = $(obj).find('div.icheck-input');
		var checkAll = masterCheckbox.hasClass('checked') ? 'unchecked' : 'checked';
		var tableBody = $(obj).closest("table").find("tbody tr");

		tableBody.each(function() {
			if ($(this).is(":visible")) {
				var singleCheckbox = $(this).find('input[type="checkbox"].icheck-input');
				$.ajax({
					type: "POST",
					url: getRootURL() + singleCheckbox.attr('url'),
					data: {sectionType: singleCheckbox.attr('sectionType'), value: singleCheckbox.val(), checked: checkAll},
					success: function(data) {
						//location.reload();
					}
				});
			}
		});
	},
	visualizeData: function(obj) {
		window.open(getRootURL() + $(obj).attr('url') + '/' + new Date().getTime(), '_blank');
	},
	sortData: function(obj) {
		var direction = 'up';

		if ($(obj).attr('class') == 'icon_sort_up') {
			direction = 'down';
		}
		$.ajax({
			type: "POST",
			url: getRootURL() + $('.table-responsive').attr('sorturl'),
			data: {col: $(obj).attr('col'), direction: direction},
			success: function(data) {
				location.reload();
			}
		});
	},
	legendShowHide: function(obj) {
		//alert($(obj).next( "div" ).attr('id'));

		$($(obj).next("div")).on('shown.bs.collapse', function() {
			$(obj).children("i").addClass('fa-minus').removeClass('fa-plus');
			//  $(".servicedrop").addClass('glyphicon-chevron-up').removeClass('glyphicon-chevron-down');
		});

		$($(obj).next("div")).on('hidden.bs.collapse', function() {
			$(obj).children("i").addClass('fa-plus').removeClass('fa-minus');
			//  $(".servicedrop").addClass('glyphicon-chevron-down').removeClass('glyphicon-chevron-up');
		});

	}
}