/* 
	elements with a "load" attribute are iteratively "loaded" by a ajax load 
	call to the url stored as the value of the "load" attribute. This is 
	potentially performed recurively if the loaded content contains more
	elements with a "load" attribute.
	By adding a "priority" attribute, you can ensure certain elements are 
	loaded first.
*/
function loadContent() {
	$("[load]").append("<img src='./images/loading.gif'  />");
	$("[load][priority]").each(
		function() { 
			$(this).load($(this).attr('load'), function() {loadContent();});
			$(this).attr('loaded', $(this).attr('load'));
			$(this).removeAttr('load');
		});
	$("[load]:not([priority])").each(
		function() { 
			$(this).load($(this).attr('load'), function() {loadContent();});
			$(this).attr('loaded', $(this).attr('load'));
			$(this).removeAttr('load');
		});
}

function SaveSetting(target){
	var $url = "services.php?type=setValue";
	var $opt = $(target).find("option:selected");
	var $params = $(target).attr("config")  + "=" + $opt.val();
	$url += "&params=" + $params;
	$.ajax({
		url: $url,
        dataType: 'json'
	}).done(
		function(data) { 
			if(!data.success)
				$(target).val(data.value);
			HighlightSavedSetting($(target), data.success, data.value);
		});
	$(target).parents('div.form-group:first').css('background-color', '#CEF'); 
}

function HighlightSavedSetting(target, success) {
	if(success && target.is('[sm]'))
		location.reload(); //if shooting mode "sm" has been updated, we should reload the page as the other settings are dependant on it
	//Set the background of the updated setting to green for a short period of time, before returning to white
	target.parents('div.form-group:first').css('background-color', success ? '#DFD' : '#FDD'); 
	setTimeout(function() {target.parents('div.form-group:first').css('background-color', '#FFF');}, 500);
	
}

/* Start a timelapse and then reload the page (to get the progress bar at the top) */
function startTimelapse() {
	var $url = "services.php?type=startTimelapse";
	var $params="";
	$("select[config]").each(
		function() {
			var $opt = $(this).find("option:selected");
			if($(this).attr('current') != $opt.val() ){
				if($params != "")
					$params+="|";
				$params+= $(this).attr("config")  + "=" + $opt.val()
				
			}
		});
		
	$.post(
			$url, 
			{ 
				name: $("#timelapsename").val(), 
				shotdelay: $("#shotdelay").val(), 
				shotcount:  $("#shotcount").val(),
				saveToCamera:  $("#saveToCamera").prop('checked'), 
				saveToServer: $("#saveToServer").prop('checked'), 
				saveLocation:  $("#saveLocation").val(), 
				params: $params 
			},
			null,
			'json'
		)
		.done(
			function(data) { 
				location.reload();
			})
		.fail(
			function(jqXHR, textStatus, errorThrown) {
				alert( errorThrown );
			});
}

function getStartTimelapseUrl() {
	var $url = "services.php?type=startTimelapse"
		+ "&name=" + $("#timelapsename").val()
		+ "&shotdelay=" + $("#shotdelay").val()
		+ "&shotcount=" + $("#shotcount").val()
		+ "&saveToCamera=" + $("#saveToCamera").prop('checked')
		+ "&saveToServer=" + $("#saveToServer").prop('checked')
		+ "&saveLocation=" + $("#saveLocation").val()
		+ "&params=";
	return $url;
}

function getCurrentSettingsUrl() {
	var $url = "services.php?type=setValues";
	var $params="";
	$("select[config]").each(
		function() {
			var $opt = $(this).find("option:selected");
			
			if($params != "")
				$params+="|";
			$params+= $(this).attr("config")  + "=" + $opt.val();
			
		});
	return $url + "&params=" + $params;
}

function takePicture() {
	var $url = "services.php?type=capture";
	var $params="";
	$("select[config]").each(
		function() {
			var $opt = $(this).find("option:selected");
			if($(this).attr('current') != $opt.val() ){
				if($params != "")
					$params+="|";
				$params+= $(this).attr("config")  + "=" + $opt.val()
				
			}
		});
	
	showPhoto("images/loading.gif", true);
	
	$.post(
			$url,
			{ 
				saveToCamera:  $("#saveToCamera").prop('checked'), 
				saveToServer: $("#saveToServer").prop('checked'), 
				saveLocation:  $("#saveLocation").val(), 
				params: $params 
			},
			null,
			'json'
		)
		.done(
			function(data) { 
				showLatest(true);
			})
		.fail(
			function(jqXHR, textStatus, errorThrown) {
				$("#photopopupmodal").modal("hide");
				alert( errorThrown );
			});
}
				
function setTimelapseProgress(done, count) {
	if(done == 0 && count == 0) {
		$("#timelapseProgress").hide();
		location.reload();
	}else {
		$("#timelapseProgress").show();
		var donePercent = (done * 100.0 / count);
		$("#timelapseProgress div.progress-bar").attr('aria-valuenow', donePercent.toString() );
		$("#timelapseProgress div.progress-bar").css('width', donePercent.toString() + '%');
		$("#timelapseProgress div.progress-bar span").text(done.toString() + ' / ' + count.toString()+ ' images taken');
		setTimeout(getTimelapseProgress, 2000);
	}
}

function getTimelapseProgress() {
	$.ajax({
		url: "services.php?type=tlProgress",
        dataType: 'json'
	}).done(function( data ) {
		setTimelapseProgress(data.done, data.count);
	  });
}

var thumbplaceholderimg = 'images/thumbplaceholder.gif';
function getFilesInFolder(folder) {
	$("#filesInFolder").html($("<img>").attr("src",'./images/loading.gif'))
	$.ajax({
		url: "services.php?type=files&folder="+folder,
        dataType: 'json'
	}).done(function( data ) {
		$("#filesInFolder").html('');
		$.each(data, 
			function(i, item) {
				$("#filesInFolder").append("<div class='browsefile' data-idx='"+(i+1).toString()+"'><img src='"+thumbplaceholderimg+"' /><br/><label>" +item.File + "</label></div>");
				
			});

		//set click events to get thumbnail
		$(".browsefile img").mouseover(
			function() {
				if($(this).attr("src") == thumbplaceholderimg)
					showThumbnail($(this), $(this).parent().attr('data-idx'));
			});
		$(".browsefile img").click(
			function() {
				grabPhoto($(this).parent().attr('data-idx'));
			});
		/*$(".browsefile label").click(
			function() {
				grabPhotoExif($(this).parent().attr('data-idx'));
			});*/
	  });
	return false;
}

var loadingThumbnail = false;
function showThumbnail($target, idx) {
	if(!loadingThumbnail) {
		loadingThumbnail = true;
		$target.find("img").removeAttr("src").attr("src", "images/loading.gif");
		$.ajax({
			url: "services.php?type=thumbnail&idx="+idx,
			dataType: 'json'
		}).done(
			function(data) { 
				$target.find("img").removeAttr("src").attr("src", data.thumbnailfile+'?'+ (new Date().getTime()));
				loadingThumbnail = false;
			}
		);
	}
}

function grabPhoto(idx) {
	showPhoto("images/loading.gif", true);
	$.ajax({
			url: "services.php?type=grab&idx="+idx,
			dataType: 'json'
		}).done(
			function(data) { 
				showPhoto(data.file);
			}
		);
}

function showPhoto(url, show) {
	$("#photopopup").removeAttr("src").attr("src", url+'?'+ (new Date().getTime()));
	if(show)
		$("#photopopupmodal").modal("show");
}

function showLatest(show) {
	showPhoto("latest.jpg", show);
}

function toggleDisabled(id) {
	$("#"+id).prop('disabled', function(i, v) { return !v; });
}

