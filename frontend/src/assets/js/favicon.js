var link = document.createElement("link");
var shortLink = document.createElement("link");
link.rel = "icon";
link.type = "image/x-icon";
shortLink.rel = "shortcut icon";
shortLink.tyoe = "image/x-icon";

function getTheme(_theme) {
	var tempFileName = _theme.replace("openemis-", "");
	return tempFileName + ".ico";
	// switch(_theme) {
	// 	case "openemis-analyzer": return "analyzer.ico";
	// 	case "openemis-community": return "community.ico";
	// 	case "openemis-connect": return "connect.ico";
	// 	case "openemis-construction": return "construction.ico";
	// 	case "openemis-core": return "core.ico";
	// 	case "openemis-datamanager": return "datamanager.ico";
	// 	case "openemis-dashboard": return "dashboard.ico";
	// 	case "openemis-identity": return "identity.ico";
	// 	case "openemis-insight": return "insight.ico";
	// 	case "openemis-integrator": return "integrator.ico";
	// 	case "openemis-learning": return "learning.ico";
	// 	case "openemis-logistics": return "logistics.ico";
	// 	case "openemis-modelling": return "modelling.ico";
	// 	case "openemis-monitoring": return "monitoring.ico";
	// 	case "openemis-school": return "school.ico";
	// 	default: return "styleguide.ico" 
	// }
}

function captializeFirstChar(_string) {
	return _string.charAt(0).toUpperCase() + _string.slice(1);
}

function updateTitle(_theme) {
	var tempTitle = _theme.split("-");

	for (var i = 0; i < tempTitle.length; ++i) {
		if (tempTitle[i] == "openemis") {
			tempTitle[i] = "OpenEMIS";
		}

		else if (tempTitle[i].length > 0) {
			tempTitle[i] = captializeFirstChar(tempTitle[i]);
		}
	}

	return tempTitle.join(" ");
}

function updateOpenEMISThemes() {
	var theme = document.body.className;
	console.log('theme loaded > ', theme);
	var getPath = "./assets/img/favicon/favicon_" + getTheme(theme);
	link.href = getPath;
	shortLink.href = getPath;
	
	document.head.appendChild(link);
	document.head.appendChild(shortLink);	
	document.title = updateTitle(theme);
}

document.addEventListener("DOMContentLoaded", updateOpenEMISThemes);
