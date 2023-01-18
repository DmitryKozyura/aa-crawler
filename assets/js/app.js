window.addEventListener("load", (event) => {
	var reportTriggers = document.querySelectorAll(".report-item h5");

	for (var i = 0; i < reportTriggers.length; i++) {
		reportTriggers[i].addEventListener("click", toggleReport);
	}
});

function toggleReport(e) {
	var reportItem = e.currentTarget.parentNode;
	if (reportItem.classList.contains("active")) {
		reportItem.classList.remove("active");
		return;
	}
	reportItem.classList.add("active");
}