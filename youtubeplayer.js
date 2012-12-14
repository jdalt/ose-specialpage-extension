// youtubeplayer.js

var tag = document.createElement('script');
tag.src = "//www.youtube.com/iframe_api";
console.log(tag.src);
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var player;
function onYouTubeIframeAPIReady() {
	$j(document).ready(function() {
		var h = parseInt($j('#video-viewer').css('height'));
		var w = parseInt($j('#video-viewer').css('width'));
		player = new YT.Player('video-viewer', {
			height: h,
	    	width: w,
	    	events: {
				'onReady': onPlayerReady,
				'onStateChange': onPlayerStateChange
			}
	  	});
	});
}

function onPlayerReady(event)
{
	$j(document).ready(function () {
		var videoIdArray = new Array();
		$j('#playlist li').each(function(index){
			$j(this).click(function() {
				console.log(index);
				player.playVideoAt(index);
			});
			videoIdArray.push($j(this).find('span').html());	
		});
		console.log(videoIdArray);
		player.cuePlaylist(videoIdArray);
	});
}

function onPlayerStateChange(event)
{
	console.log(event);
}
