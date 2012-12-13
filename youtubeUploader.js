/*
 *  youtubeUploader.js
 */

var submitButtonIsLocked = false;
var isUploaded = false;

var tag = document.createElement('script');
tag.src = "//www.youtube.com/iframe_api";
console.log(tag.src);
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

console.log(firstScriptTag);

//!!! Consider - how do we know jquery and page is ready before processing...perhaps wait to call this via a setTimeout...?
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
		'onStateChange': onStateChange,
   	}
	});
	$j('.mw-htmlform-submit[value="Save Video"]').click(function(e){
		if(submitButtonIsLocked) {
			console.log('Attempted to submit while button is locked.'); 
			//TODO: interface to override this if there are processing errors; better yet be able to be able to 
			//CONSIDER: on click launch a popup blurb that allows you to override but warns user that video might not submit correctly due to processing occuring at youtube
			$j('#error-container').css('visibility', 'visible');
			e.preventDefault();
		} else {
			console.log($j('#ose-truefan-url').val());
			if($j('#ose-truefan-url').val() == '') {
				console.log('No input.');
				$j('#modal-error-message').html('No video has been uploaded or manually input.');
				$j('#error-container').css('visibility', 'visible');
				e.preventDefault();
			} else {
				return true;
			}
		}
	});
}				

function onStateChange(event) {
	switch(event.data.state) {
		case YT.UploadWidgetState.IDLE:
			setSubmitButtonLocked(false);
			console.log('idle');
			break;
		case YT.UploadWidgetState.PENDING:
			setSubmitButtonLocked(true, 'You have recorded a video. You must upload the video or cancel it.');
			console.log('pending');
			break;
		case YT.UploadWidgetState.ERROR:
			$j('#error-container').css('visibility', 'visible');
			$j('#modal-error-message').html('<p>There was an error with the Youtube Uploader. There may be a problem with your webcam.</p>'+
											'<p>It may be that another application is using your webcam or you did not give the True Fans App permission to use it.</p>');
			console.log('An error occurred: ' + YT.UploadWidgetState.ERROR);
			break;
		case YT.UploadWidgetState.PLAYBACK:
			// no lock status change
			console.log('playback');
			break;
		case YT.UploadWidgetState.RECORDING:
			setSubmitButtonLocked(true, 'You cannot submit a video while recording. Finish recording then upload or cancel the video.');
			console.log('recording');
			break;
		case YT.UploadWidgetState.STOPPED:
			if(!isUploaded) {
				setSubmitButtonLocked(false);
			}
			console.log('stopped');
			break;
	}
}

function onUploadSuccess(event) {
	setSubmitButtonLocked(true, 'You must wait until processing has completed.');
 	$j('.gear').addClass('animate');
	$j('#status').addClass('overlay');
	$j('#status-text').html('<p>The video is currently being processed on Youtube servers.</p> <p>You must wait until processing completes in order to save your video.</p>');
	isUploaded = true;
}

function onProcessingComplete(event) {
	setSubmitButtonLocked(false);
	widget.destroy();

	var h = parseInt($j('#video-viewer').css('height'));
	var w = parseInt($j('#video-viewer').css('width'));
	$j('#ose-truefan-url').val(event.data.videoId);

	console.log($j('#ose-truefan-url').val(event.data.videoId));
	player = new YT.Player('video-viewer', {
		height: h,
    	width: w,
    	videoId: event.data.videoId,
    	events: {}
  	});
	//$j('#status').html('Your video was uploaded successfully and has been added to this form.');
 	$j('.gear').removeClass('animate');
	$j('#status').removeClass('overlay');
	$j('#status-text').html('');
}

function onApiReady()
{
	widget.setVideoPrivacy('unlisted');
	// TODO: Consider - is jQuery guaranteed to be loaded at this time?
	var name = $j('#ose-truefan-name').val();
	widget.setVideoTitle(name + ' - Open Source Ecology True Fan Video');
	widget.setVideoDescription('OSE True Fan Test');
}

function setSubmitButtonLocked(isLocked, msg)
{
	if(isLocked){
		console.log('locked');
		submitButtonIsLocked = true;
		$j('#modal-error-message').html(msg);
		$j('.mw-htmlform-submit[value="Save Video"]').addClass('deactivated');
	} else {
		console.log('unlocked');
		submitButtonIsLocked = false;
		$j('.mw-htmlform-submit[value="Save Video"]').removeClass('deactivated');
	}
}

console.log('new update');
