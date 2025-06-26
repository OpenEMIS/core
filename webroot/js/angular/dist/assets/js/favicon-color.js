function getTheme() {
	var theme = document.body.className;
	switch(theme) {
		case "openemis-styleguide": return "#000000";
		case "openemis-analyzer": return "#CC3300";
		case "openemis-community": return "#00CCFF";
		case "openemis-connect": return "#666699";
		case "openemis-core": return "#6699CC";
		case "openemis-datamanager": return "#993366";
		case "openemis-dashboard": return "#990000";
		case "openemis-exams": return "#003366";
		case "openemis-identity": return "#6666FF";
		case "openemis-insight": return "#9966CC";
		case "openemis-integrator": return "#CC6699";
		case "openemis-learning": return "#0099FF";
		case "openemis-logistics": return "#003399";
		case "openemis-modelling": return "#FF3366";
		case "openemis-monitoring": return "#663399";
		case "openemis-school": return "#3366CC";
	}
}

function onImgLoad(img, link, shortLink) {
	var canvas = document.createElement("canvas");
	canvas.width = 48;
	canvas.height = 48;
	var context = canvas.getContext("2d");
	context.drawImage(img, 0, 0);
	context.globalAlpha = 1;
	context.globalCompositeOperation = "source-atop";
	context.fillStyle = getTheme();
	context.fillRect(0, 0, 48, 48);
	context.fill();

	link.type = "image/x-icon";
	link.href = canvas.toDataURL();
	shortLink.type = "image/x-icon";
	shortLink.href = canvas.toDataURL();

	canvas.toBlob()
}

document.addEventListener("DOMContentLoaded", function(event) { 

	var link = document.querySelector("link[rel='icon']");
	if (!link) {
		var link = document.createElement("link");
		link.setAttribute("rel", "icon");
		document.head.appendChild(link);
	}
	var faviconUrl = link.href || window.location.origin + "/favicon.ico";

	var shortLink = document.querySelector("link[rel='shortcut icon']");
	// var shortLink = document.createElement("link");
	if (!shortLink) {
		var shortLink = document.createElement("link");
		shortLink.setAttribute("rel", "icon");
		document.head.appendChild(shortLink);
	}

	link.setAttribute("rel", "icon");
	shortLink.setAttribute("rel", "shortcut icon");

	document.head.appendChild(link);
	document.head.appendChild(shortLink);

	var img = document.createElement("img");
	img.src = faviconUrl;
});