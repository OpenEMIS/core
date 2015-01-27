/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var Survey = {
	filterXML : function(){
		var maskId;
		var sy = $("#schoolYear :selected").text();
		var st = $("#siteType :selected").text().replace(" ","-");
		var sc = $("#category :selected").text().replace(" ","-");
		var resp = (($("#pageType").val() == 'import')?'import':'');
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'Survey/filter/'+resp,
			data: {schoolYear:sy,siteType:st,category:sc},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '#divSurvey', text: i18n.General.textLoading});
			},
			success: function (data, textStatus) {
				var callback = function() {
					$("#results").html(data);
					jsForm.attachHoverOnClickEvent();
				};
				$.unmask({id: maskId, callback: callback});
			}	
		});
	},
	
	siteTypeChange : function(){
		var maskId;
		var id = document.getElementById("siteTypes").value;
		var catid = document.getElementById("category").value;
		if(catid!=0){
			$('.SiteTypeDDL').addClass('hide');
		}else{
			$('.SiteTypeDDL').removeClass('hide');
		}
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'Survey/sitetypechange/',
			data: {siteId: id, catId: catid},
			beforeSend: function (jqXHR) { $("#questionOps").empty(); maskId = $.mask({parent: '#divSurvey'}); },
			success: function (data, textStatus) {
				var callback = function() {
					$("#questionOps").html(data);
			   		//jsForm.attachHoverOnClickEvent();
				};
				$.unmask({id: maskId, callback: callback});
			}	
		});
	},
	
	toggleSelect: function(obj) {
		var table = $(obj).closest('.table');
		table.find('.table_body input[type="checkbox"]').each(function() {
			var row = $(this).closest('.table_row');
			if(obj.checked) {
				document.getElementById($(this).attr("id")).checked = true;
				if($(this).attr('disabled') == undefined){
					$(this).attr('checked','checked');
					if(row.hasClass('inactive')) {
						row.removeClass('inactive');
					}
				}
			} else {
				$(this).removeAttr('checked');
				if(!row.hasClass('inactive')) {
					row.addClass('inactive');
				}
			}
		});
	},
	
	activate: function(obj, opts) {
		var selector = opts==undefined ? '[data-id]' : opts;
		var li = $(obj).closest(selector);
		if(li.hasClass('inactive')) {
			li.removeClass('inactive');
		} else {
			li.addClass('inactive');
		}
		
		var table = $(obj).closest('.table');
		var n = $(obj).attr("id").split("Checked"); 
		
		table.find('.table_body input[type="checkbox"]').each(function() {
			var row = $(this).closest('.table_row');
			if(obj.checked) {
				var y = $(this).attr("id").split("Questions"); 
				if(y[0]==n[0]){
					document.getElementById($(this).attr("id")).checked = true;
					if($(this).attr('disabled') == undefined){
						$(this).attr('checked','checked');
						if(row.hasClass('inactive')) {
							row.removeClass('inactive');
						}
					}
				}
			} else {
				var y = $(this).attr("id").split("Questions"); 
				if(y[0]==n[0]){
					$(this).removeAttr('checked');
					if(!row.hasClass('inactive')) {
						row.addClass('inactive');
					}
				}
			}
		});
	},
	
	activateSync: function(obj) {
		var table = $(obj).closest('.table');
		table.find('.table_body input[type="checkbox"]').each(function() {
			var row = $(this).closest('.table_row');
			if(obj.checked) {
				document.getElementById($(this).attr("id")).checked = true;
				if($(this).attr('disabled') == undefined){
					$(this).attr('checked','checked');
				}
			} else {
				$(this).removeAttr('checked');
			}
		});
	},
	
	activateQuestion: function(obj, opts) {
		var n = $(obj).attr("id").split("Questions"); 
		
		
		if(obj.checked){
			document.getElementById(n[0]+"Checked").checked = true;
			document.getElementById(n[0]+"_row").className = 'table_row';
			var row = $(obj).closest('.table_row');
			if(row.hasClass('inactive')) {
				row.removeClass('inactive');
			}
		}else{
			var table = $(obj).closest('.table');
			var flag = true;
			table.find('.table_body input[type="checkbox"]').each(function() {
				var row = $(this).closest('.table_row');
				if(document.getElementById($(this).attr("id")).checked){
					flag = false;
				}
			});
			if(flag){
				document.getElementById(n[0]+"Checked").checked = false;
				document.getElementById(n[0]+"_row").className = 'table_row inactive';
			}
			var row = $(obj).closest('.table_row');
			if(!row.hasClass('inactive')) {
				row.addClass('inactive');
			}
		}
	},
	
	massDelete:function(obj){

		var allVals = [];
		$('#'+obj+' :checked').each(function(){
		   allVals.push($(this).val());
		});
		if(allVals.length > 0){
			allVals = allVals.join(',');
			location.href=getRootURL()+'Survey/delete/'+$('#'+obj).attr('cat')+'/?file='+allVals
		}else{  
			alert('Please Select a File');
		}
	},
	
	toggleCheckbox : function(a){
		var checkbox = $(a).find('input:checkbox:first')
			checkbox.attr("checked", !checkbox.attr("checked"))
	},
	
	massUpdate:function(obj){
		var allVals = [];
		$('#'+obj+' :checked').each(function(){
		   allVals.push($(this).val());
		});
		if(allVals.length > 0){
			allVals = allVals.join(',');
			location.href=getRootURL()+'Survey/responsefile/?file='+allVals
		}else{  
			alert('Please Select a File');
		}
	},
	
	toggleCheckbox : function(a){
		var checkbox = $(a).find('input:checkbox:first')
			checkbox.attr("checked", !checkbox.attr("checked"))
	},
	
	checkDuplicate: function(){
		var surveyName = document.getElementById('filename').value;
		var category = document.getElementById('category').value;
		var year = document.getElementById('year').value;
		var catName = "Institution";
		if(category==1){ catName = "Student"; }
		if(category==2){ catName = "Staff"; }
		
		if(surveyName==""){
			if(category!=0){
				surveyName = year+'_'+catName+'.json';
			}else{
				var siteType = document.getElementById("newSkill");
				var selectedSiteType = siteType.options[siteType.selectedIndex].text;
				surveyName = year+'_'+catName+'_'+selectedSiteType+'.json';
			}
		}else{
			surveyName = surveyName + '.json';
		}
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'Survey/checksurvey/',
			data: {surveyName: surveyName},
			success: function(data){ 
				if(data!="exist"){
					$("form#submitForm").submit();
				}else{
					var btn = {
						value: 'Overwrite',
						callback: function() { $("form#submitForm").submit(); }
					};
					
					var dlgOpt = {	
						id: 'Duplicate-dialog',
						title: 'Existing survey found',
						content: 'Do you want to overwrite an existing Survey?',
						buttons: [btn]
					};
					
					$.dialog(dlgOpt);
					return false;
				}
			}
		});
		return false;
	}
}

/* For Sorting of Survey List */
$(function() {
    try{ //ask faizal what this is
        $( '#sort-topic' ).sortable();
        $( "#sort-topic" ).disableSelection();
    }catch(e){}
});

$(document).ready(function(){
	//console.log($('#results').find('input:checkbox'));
	$('#results .table_row').click(function(e){
		if (e.target.type == "checkbox") {
			// stop the bubbling to prevent firing the row's click event
			e.stopPropagation();
		} else {
			var $checkbox = $(this).find(':checkbox');
			$checkbox.attr('checked', !$checkbox.attr('checked'));
		}
	});
});