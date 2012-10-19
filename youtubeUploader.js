/*
 *  youtubeUploader.js
 */

// !! The following is based on sample code from Google. !!

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
  	widget = new YT.UploadWidget('widget', {
   	width: 500,
   	events: {
   		'onUploadSuccess': onUploadSuccess,
    		'onProcessingComplete': onProcessingComplete,
			'onApiReady': onApiReady
   	}
	});
}				

function onUploadSuccess(event) {
  alert('Video ID ' + event.data.videoId + ' was uploaded and is currently being processed.');
}

function onProcessingComplete(event) {
	$j('#ose-truefan-url').val(event.data.videoId);
	player = new YT.Player('player', {
		height: 390,
    	width: 640,
    	videoId: event.data.videoId,
    	events: {}
  });
  widget.destroy();
}

function onApiReady()
{
	widget.setVideoPrivacy('unlisted');
	widget.setVideoDescription('OSE True Fan Test');
}
