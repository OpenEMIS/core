/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function() {
	Config.init();
	Config.applyRule(); 
});

var Config = {
	init : function(){
		this.getAllowedChars();
		
	}
	,err : []
	,validationRule : {}
	,getAllowedChars : function(){
		
		$.get(getRootURL()+'Config/getAllowedChar', function(data) {
			
			$('.custom_validation').keyup(function() {
				if (this.value.match("[^AN_ ()-"+data+"]",'g')) {
					var re = new RegExp("[^AN_ ()-"+data+"]","g");
					this.value = this.value.replace(re, '');
				}
			});
		});
	},
	applyRule : function(){
		
		$.get(getRootURL()+'Config/getAllRules', function(data) {
			
			Config.validationRule = $.parseJSON(data);
			//$("input[validate=postal]").length
			/*$('.custom_validation').keyup(function() {
				if (this.value.match("[^NC"+data+"]",'g')) {
					var re = new RegExp("[^NC"+data+"]","g");
					this.value = this.value.replace(re, '');
				}
			});*/
		});
		
	},
	
	checkValidate : function(){
		Config.err = {};
		var sizectr = 0;
		// Unmask this if need to use this format    
		var bool = true;
		
		for(k in Config.validationRule){
			if(k == 'special_charaters') continue;
			try{
				if($("input:[id*=validate_"+k+"]").val().length > 0){
					$("input:[id*=validate_"+k+"]").each(function(){
						var p = Config.validationRule[k];
						var regexObj = new RegExp("^"+p+"$");
						console.log(this.value);
						if (!regexObj.test(this.value)) {
							var myStr = k.replace(/_/g, ' ');
							var element = $("input:[id*=validate_"+k+"]").parent().parent().find(".error-message");
							if(element.length > 0){
								element.html('Please enter a valid ' + myStr);
							}else{
								$("input:[id*=validate_"+k+"]").parent().parent().append("<div class='error-message'>Please enter a valid " + myStr + "</div>");
							}
							bool = false
						}
					})
				}
			}catch(e){
				
			}
		}
		
		return bool;
	}
	
}

function updateHiddenField(myform, updateform) {
  document.getElementById(updateform).value = document.getElementById(myform.id).value  
}


