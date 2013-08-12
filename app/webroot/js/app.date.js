/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright � 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

$(document).ready(function() {
	jsDate.init();
});

var jsDate = {
	init: function() {
		jsDate.initDatepicker();
	},
	
	initDatepicker: function(p) {
		p = p==undefined ? '.datepicker' : p;
		$(p).each(function() {
			var obj = $(this);
			jsDate.updateDay(obj.find('.datepicker_month'));
			if(obj.attr('start') != undefined && obj.attr('end') != undefined) {
				$(obj.attr('end')).attr('start', obj.attr('start'));
				jsDate.validateEndDate(obj.find('.datepicker_day'));
			}
		});
	},
	
	daysInMonth: function(y, m) {
		return new Date(y, m, 0).getDate();
	},
	
	getDateObjFromDatepicker: function(p) {
		var y = $(p).find('.datepicker_year').val().toInt();
		var m = $(p).find('.datepicker_month').val().toInt();
		var d = $(p).find('.datepicker_day').val().toInt();

		return y==0 || m==0 || d==0 ? false : new Date(y,m-1,d);
	},
	
	updateDatepickerValue: function(parent, date) {
		var day = date.getDate();
		var mth = date.getMonth()+1;
		var yr = date.getFullYear();
		if(mth < 10) {
			mth = '0' + mth;
		}
		$(parent).find('.datepicker_day').val(day);
		$(parent).find('.datepicker_month').val(mth);
		$(parent).find('.datepicker_year').val(yr);
		$(parent).find('.datepicker_date').val(yr + '-' + mth + '-' + day);
	},
	
	updateDay: function(obj) {
		var parent = $(obj).parent();
		var dayObj = parent.find('.datepicker_day');
		var monthObj = parent.find('.datepicker_month');
		var yearObj = parent.find('.datepicker_year');
		var dateObj = parent.parent().find('.datepicker_date');
        var dayVal = dayObj.val().toInt();
		var monthVal = monthObj.val().toInt();
		var yearVal = yearObj.val().toInt();
		
		if(dayObj.val().toInt() != 0 && monthVal != 0 && yearVal != 0) {
			var noOfDays = jsDate.daysInMonth(yearVal, monthVal);
			var dayOptions = dayObj.find('option');
			if(parent.attr('start') == undefined && parent.attr('end') == undefined) {
				dayObj.find('option').css('display', 'block');
			} else {
				if('#' + parent.attr('id') === parent.attr('start')) {
					$(parent.attr('start')).find('.datepicker_day > option').css('display', 'block');
				}
			}
			if(dayObj.val() > noOfDays) {
				dayObj.val(noOfDays);
			}
			dayOptions.each(function() {
				var day = $(this).val().toInt();
				if(noOfDays < day) {
					$(this).css('display', 'none');
				}
			});
			var dayVal = dayObj.val();
			dateObj.val(yearVal + '-' + monthVal + '-' + dayVal);
		}else{
            var selection = $(obj).attr('class');
            var myflag = false;
            if(selection=='datepicker_day'){
                if(dayVal==0){
                    myflag = true;
                }
            }
            if(selection=='datepicker_month'){
                if(monthVal==0){
                    myflag = true;
                }
            }
            if(selection=='datepicker_year'){
                if(yearVal==0){
                    myflag = true;
                }
            }
            if(myflag){
                dayObj.val(0);
                monthObj.val(0);
                yearObj.val(0);
            }
			dateObj.val('0000-00-00');
        }
	},
	
	datepickerUpdate: function() {
		var parent = $(this).parent();
		var dayValue = 1;
		var dayObj = parent.find('.datepicker_day');
		var monthValue = parent.find('.datepicker_month').val();
		var yearValue = parent.find('.datepicker_year').val();
		var dateObj = parent.parent().find('.datepicker_date');
		if(monthValue !=0 && yearValue !=0) {
			if(dayObj.length>0 && dayObj.val() > 0) {
				dayValue = dayObj.val();
			}
			var dateObj1 = new Date(yearValue, monthValue, 0);
			var dateObj2 = new Date(yearValue, monthValue-1, dayValue);
			if(dateObj1.getMonth() != dateObj2.getMonth()) {
				dayValue = dateObj1.getDate();
				dayObj.val(dayValue);
			}
			dateObj.val(yearValue + '-' + monthValue + '-' + dayValue);
		} else {
			dateObj.val(dateObj.attr('default'));
		}
	},
	
	datepickerUpdateSelector: function() {
        $('.datepicker').each(function(i, o){
            var dateOpenDatepicker = $(o);
            var hiddenDate = dateOpenDatepicker.siblings('input[type="text"].datepicker_date');
            if(hiddenDate.val() !== ''){
                var dateOpenValue = hiddenDate.val();

                if(typeof dateOpenValue !== "undefined"){
                    var splitDate = dateOpenValue.split('-');
					
                    if(dateOpenDatepicker.find('.datepicker_day').length > 0){
                        dateOpenDatepicker.find('.datepicker_day').val(function(){
                            return ('0' + splitDate.pop()).slice(-2);
                        });

                    }else{
                        splitDate.pop();
                    }

                    dateOpenDatepicker.find('.datepicker_month').val(splitDate.pop());
                    dateOpenDatepicker.find('.datepicker_year').val(splitDate.pop());
                }
            }
        });
    },
	
	validateEndDate: function(obj) {
		var parent = $(obj).parent();
		var start = parent.attr('start');
		var end = parent.attr('end');
		var startDate = jsDate.getDateObjFromDatepicker(start);		
		var endDate = jsDate.getDateObjFromDatepicker(end);
		
		if(endDate !== false) {
			var startYear = startDate.getFullYear();
			var startMonth = startDate.getMonth();
			var startDay = startDate.getDate();
			var endYear = endDate.getFullYear();
			var endMonth = endDate.getMonth();
			var endDay = endDate.getDate();
			var yearObj = $(end).find('select.datepicker_year');
			var monthObj = $(end).find('select.datepicker_month');
			var dayObj = $(end).find('select.datepicker_day');
			
			// start date is later then end date, set the end date to one day after start date
			if(startDate.getTime() >= endDate.getTime()) {
				var date = new Date(startYear, startMonth, startDay+1);
				var y = date.getFullYear();
				var m = date.getMonth()+1;	m = m < 10 ? '0' + m : m;
				var d = date.getDate();		d = d < 10 ? '0' + d : d;
				
				$(end).find('select.datepicker_year').val(y);
				$(end).find('select.datepicker_month').val(m);
				$(end).find('select.datepicker_day').val(d);
				jsDate.updateOptions(end);
			} else {
				if(startYear == endYear) {
					monthObj.find('> option').each(function() {
						var val = $(this).val().toInt();
						$(this).css('display', val < startMonth && val != 0 ? 'none' : 'block');
					});
					if(startMonth == endMonth) {
						// show only days after start day
						dayObj.find('> option').each(function() {
							var val = $(this).val().toInt();
							$(this).css('display', val <= startDay && val != 0 ? 'none' : 'block');
						});
					} else if(startMonth < endMonth) {
						dayObj.find('> option').css('display', 'block');
					}
				} else { // start year is less than end year
					// show all months and days
					monthObj.find('> option').css('display', 'block');
					dayObj.find('> option').css('display', 'block');
				}
				yearObj.find('> option').each(function() {
					var val = $(this).val().toInt();
					$(this).css('display', val < startYear && val != 0 ? 'none' : 'block');
				});
			}
			startDate = jsDate.getDateObjFromDatepicker(start);
			endDate = jsDate.getDateObjFromDatepicker(end);
			$(start).siblings('.datepicker_date').val(startDate.getFullYear() + '-' + (startDate.getMonth()+1) + '-' + startDate.getDate());
			$(end).siblings('.datepicker_date').val(endDate.getFullYear() + '-' + (endDate.getMonth()+1) + '-' + endDate.getDate());
		}
	},
	
	updateOptions: function(end) { // hide all date options that are earlier than selected date
		var yearObj = $(end).find('select.datepicker_year');
		var monthObj = $(end).find('select.datepicker_month');
		var dayObj = $(end).find('select.datepicker_day');
		var y = yearObj.val();
		var m = monthObj.val();
		var d = dayObj.val();
		
		yearObj.find('> option').each(function() {
			var val = $(this).val().toInt();
			$(this).css('display', val < y && val != 0 ? 'none' : 'block');
		});
		monthObj.find('> option').each(function() {
			var val = $(this).val().toInt();
			$(this).css('display', val < m && val != 0 ? 'none' : 'block');
		});
		dayObj.find('> option').each(function() {
			var val = $(this).val().toInt();
			$(this).css('display', val < d && val != 0 ? 'none' : 'block');
		});
	},

    checkValidDateClosed : function(){
        var yearVal = $('#date_closed').find('select.datepicker_year').val().toInt();;
        var monthVal = $('#date_closed').find('select.datepicker_month').val().toInt();;
        var dayVal = $('#date_closed').find('select.datepicker_day').val().toInt();;

        var element = $('#date_closed').parent().parent().find(".error-message");
        var bool = false;
        if(dayVal == 0 && monthVal ==0 && yearVal==0){
            bool = true;
        }else{
            if(dayVal == 0 || monthVal ==0 || yearVal==0){
                if(element.length > 0){
                    element.html('Please enter a valid date');
                }else{
                    $('#date_closed').parent().parent().append("<div class='error-message'>Please enter a valid date</div>");
                }
            }else{
                bool = true;
            }
        }
        if(bool){
            if(element.length > 0){
                element.hide();
            }
        }

        return bool;
    }
};