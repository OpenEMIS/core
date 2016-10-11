var Authentication = {
	populate: function(obj) {
		console.log(obj);
		$.ajax({
			url: url,
			type: "GET",
			success: function(data){
				console.log(data);
				// objToUpdate.html(data);
				// lastSelectedValue = objToUpdate.find('select').last().val();
				// // Populate hidden value
				// $( objToUpdate ).next().val(lastSelectedValue);
			}
		});
	}
}
