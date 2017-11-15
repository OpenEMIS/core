$(document).ready(function(){
	var form = $("#settingEnvironment");
	//var databaseName = $("#databaseName");
	//var databaseNameInfo = $("#databaseNameInfo");
	var username = $("#username");
	var usernameInfo = $("#usernameInfo");
	var password = $("#password");
	var passwordInfo = $("#passwordInfo");
			
	//databaseName.blur(validateDatabasename);
	//databaseName.keyup(validateDatabasename);
	username.blur(validateUsername);
	username.keyup(validateUsername);
	password.blur(validatePassword);
	password.keyup(validatePassword);
			
	form.submit(function(){
		if(validateDatabasename() & validateUsername() & validatePassword()) {
			return true
		} else {
			return false
		}
	});
			
	//function validateDatabasename(){
	//	if(databaseName.val().length < 1){
	//		databaseName.addClass("error");
	//		databaseNameInfo.text("Please enter the database name.");
	//		databaseNameInfo.addClass("error");
	//		return false;
	//	} else {
	//		databaseName.removeClass("error");
	//		databaseNameInfo.text("OpenEmis School database name. Existing tables will be dropped!");
	//		databaseNameInfo.removeClass("error");
	//		return true;
	//	}
	//}
			
	function validateUsername(){
		if(username.val().length < 1){
			username.addClass("error");
			usernameInfo.text("Please enter the username.");
			usernameInfo.addClass("error");
			return false;
		} else {
			username.removeClass("error");
			usernameInfo.text("Database Privileged Username.");
			usernameInfo.removeClass("error");
			return true;
		}
	}
			
	function validatePassword(){
		if(password.val().length < 1){
			password.addClass("error");
			passwordInfo.text("Please enter the password.");
			passwordInfo.addClass("error");
			return false;
		} else {
			password.removeClass("error");
			passwordInfo.text("Database Privileged Password.");
			passwordInfo.removeClass("error");
			return true;
		}
	}
});