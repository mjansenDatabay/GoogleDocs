$(function() {
	$("#iosGdocsIframeContainer").css({
		"width": $("#iosGdocsIframeContainer").width()
	});

	$("#iosGdocsIframeContainer").resizable();

	$("#iosGdocsIframeContainer iframe").css({
		"width": "100%",
		"height": "100%"
	});
});