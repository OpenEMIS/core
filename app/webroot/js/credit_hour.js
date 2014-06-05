function setCreditHourName(index){
   var concat = $('#SetupVariables'+index+'Min').val() + ' - ' + $('#SetupVariables'+index+'Max').val();
   $('#SetupVariables'+index+'Name').val(concat);
}