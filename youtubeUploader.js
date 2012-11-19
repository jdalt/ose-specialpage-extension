/*
 *  youtubeUploader.js
 */

// !! The following is based on sample code from Google. !!

// TODO: Handle processing error from google.

var tag = document.createElement('script');
tag.src = "//www.youtube.com/iframe_api";
console.log(tag.src);
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

console.log(firstScriptTag);

// Define global variables for the widget and the player.
var widget;
var player;
function onYouTubeIframeAPIReady() {
  	widget = new YT.UploadWidget('video-viewer', {
   	width: parseInt($j('#video-viewer').css('width')),
   	events: {
   		'onUploadSuccess': onUploadSuccess,
    		'onProcessingComplete': onProcessingComplete,
			'onApiReady': onApiReady,
			'onStateChange': onStateChange
   	}
	});
}				

function onStateChange(event) {
	console.log(event.data.state);
	if(event.data.state == YT.UploadWidgetState.ERROR) {
		alert('Error Occurred');
	}
}

function onUploadSuccess(event) {
	$j('#status').html('Video ID ' + event.data.videoId + ' was uploaded and is currently being processed.');
 	$j('.gear').addClass('animate');
}

function onProcessingComplete(event) {
 	widget.destroy();
	$j('#ose-truefan-url').val(event.data.videoId);
	player = new YT.Player('vieo-viewer', {
		height: parseInt($j('#video-viewer').css('height')),
    	width: parseInt($j('.#video-viewer').css('width')),
    	videoId: event.data.videoId,
    	events: {}
  });
	$j('#status').html('Your video was uploaded successfully and has been added to this form.');
 	$j('.gear').removeClass('animate');
}

function onApiReady()
{
	widget.setVideoPrivacy('unlisted');
	// TODO: Consider - is jQuery guaranteed to be loaded at this time?
	var name = $j('#ose-truefan-name').val();
	widget.setVideoTitle(name + ' - Open Source Ecology True Fan Video');
	widget.setVideoDescription('OSE True Fan Test');
}
