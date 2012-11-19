/*
 *  dynamic.js
 */

//TODO: evaluate utility of these globals...any use out of finish.html template?
var autoReplaceArray, autoInputArray;

$j(document).ready(function() {
	console.log('$j says document ready');
		
	$j('#ose-truefan-message').keyup(function(){$j('#quote-area p').html($j('#ose-truefan-message').val());});

	
	$j('#video-viewer').css('height',parseFloat($j('#video-viewer').css('width')) * 9/16);
	$j(window).resize(function() {
		$j('#video-viewer').css('height',parseFloat($j('#video-viewer').css('width')) * 9/16);
	});

	for (var source in autoReplaceArray) {
  		if (autoReplaceArray.hasOwnProperty(source)) {
			var sourceHtml = $j(source).html();
			$j(autoReplaceArray[source]).html(sourceHtml);
			$j(source).empty();
  		}
	}

	for (var source in autoInputArray) {
  		if (autoInputArray.hasOwnProperty(source)) {
			var sourceText = $j(source).html();
			$j(autoInputArray[source]).val(sourceText);
			$j(source).empty();
  		}
	}

});

function runOnloadHook()
{
	console.log('runonloadhook');
}



