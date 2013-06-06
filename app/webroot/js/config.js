/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function() {
	Config.init();
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
				if (this.value.match("[^NC"+data+"]",'g')) {
					var re = new RegExp("[^NC"+data+"]","g");
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
		
	}
	,checkValidate : function(){
		Config.err = {};
		var sizectr = 0;
		for(k in Config.validationRule){
			if(k == 'special_charaters') continue;
			//console.log("input[validate="+k+"] "+ $("input[validate="+k+"]").length) ;
			if($("input[validate="+k+"]").length > 0){
				
				
				$("input[validate="+k+"]").each(function(){
					var p = Config.validationRule[k];
					
					var regexObj = new RegExp("^"+p+"$");
					console.log(this.value);
					if (regexObj.test(this.value)) {
						//valid
					} else {
						// Invalid 
						sizectr++;
						Config.err[k] = this.value;
					}
				})
				
				/*$('.custom_validation').keyup(function() {
						if (this.value.match("[^NC"+data+"]",'g')) {
							var re = new RegExp("[^NC"+data+"]","g");
							this.value = this.value.replace(re, '');
						}
				});*/
			}
		}
		
		if(sizectr > 0){
			
			var  msg = "Invalid Fields \n"; 
			for(p in Config.err){
				msg += p+" : "+Config.err[p]+"\n";
			}
			alert(msg);
			return false
		}else{
			return true;
		}
		//console.log(retval)
		//return retval;
	}
	
}


