 

console.log('youtubeplayer.js');


var firstVideo = 'MIIzogiUHFY';


// 2. This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');
tag.src = "//www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// 3. This function creates an <iframe> (and YouTube player)
//    after the API code downloads.
var player;
var h = parseInt($j('#video-viewer').css('height'));
var w = parseInt($j('#video-viewer').css('width'));
function onYouTubeIframeAPIReady() {
	player = new YT.Player('video-viewer', {
		//height: h,
		width: w,
		videoId: firstVideo,
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
	});
	//player.loadPlaylist({list:['zIsHKrP-66s', 'MIIzogiUHFY', 'ubTNQV8oXVM', 'wbqC8zm7Hyg', 'nV_-ZzYmo3A']})
}

// 4. The API will call this function when the video player is ready.
function onPlayerReady(event) {
  event.target.playVideo();
}

// 5. The API calls this function when the player's state changes.
//    The function indicates that when playing a video (state=1),
//    the player should play for six seconds and then stop.
var done = false;
function onPlayerStateChange(event) {
  if (event.data == YT.PlayerState.PLAYING && !done) {
    setTimeout(stopVideo, 6000);
    done = true;
  }
}
function stopVideo() {
  player.stopVideo();
}
