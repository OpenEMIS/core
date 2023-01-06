//Multiple Image Uploader v.1.0.0
//Reference: http://www.dropzonejs.com/#usage

var ImageUploader = {
	init: function() {
		this.setPreviewNode();
    },

    setPreviewNode: function() {
    	// Dropzone.autoDiscover = false;
    	// Get the template HTML and remove it from the document
		var previewNode = document.querySelector("#template");
		if (previewNode != null) {
			previewNode.id = "";
			var previewTemplate = previewNode.parentNode.innerHTML;
			previewNode.parentNode.removeChild(previewNode);	

			var myDropzone = new Dropzone(document.body, { // Make the whole body a dropzone
				url: "/", // Set the url
				parallelUploads: 20,
				previewTemplate: previewTemplate,
				previewsContainer: "#previews", // Define the container to display the previews
				clickable: ".fileinput-button" // Define the element that should be used as click trigger to select files.
			});
			var previewsId = document.getElementById("previews");

			if (document.getElementById("previews").hasChildNodes() == true) {
			    previewsId.className = "files";
			} 

			myDropzone.on("addedfile", function(file) {
			    previewsId.className = "files box";
			});

			myDropzone.on("removedfile", function(file) {
				if (document.getElementById("previews").children.length == 0) {
				    previewsId.className = "files";
				} 
			});
		}
    }
}