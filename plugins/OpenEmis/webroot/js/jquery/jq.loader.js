//Loader v.1.0.0

//Load Spinner Fades Out
jQuery(window).load(function() {
	// console.log("windows loaded", $(".load-content")[0]);
	// console.log("nav bar select", $(".navbar-fixed-top")[0]);
	// console.log("icon class", $("#wizard")[0]);
	// console.log("test again", $(".ng-scope.load-content")[0]);
	setTimeout(function(){
    	jQuery('.load-content').fadeOut();
    }, 1000);
});
