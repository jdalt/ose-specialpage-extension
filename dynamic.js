/*
 *  dynamic.js
 */

//TODO: evaluate utility of these globals...any use out of finish.html template?
var autoReplaceArray, autoInputArray;

$j(document).ready(function() {
	console.log('$j says document ready');

/*
	$j('form').submit(function() {
		$(this).submit(function() {
			return false;
		});
		return true;
	});
*/		
	$j('#ose-truefan-message').keyup(function(){$j('#quote-area p').html($j('#ose-truefan-message').val());});
	
	$j('#video-viewer').css('height',parseFloat($j('#video-viewer').css('width')) * 9/16);
	$j(window).resize(function() {
		$j('#video-viewer').css('height',parseFloat($j('#video-viewer').css('width')) * 9/16);
	});

	// modal window button events
	$j('.modal-button-show').click(function(){
		console.log($j(this).siblings('.modal-overlay-container'));
		$j(this).siblings('.modal-overlay-container').css('visibility', 'visible');
	});
	$j('.modal-close-button').click(function(){
		console.log($j(this).closest('.modal-overlay-container'));
		$j(this).closest('.modal-overlay-container').css('visibility', 'hidden');
	});
	$j('.modal-close-window').click(function(){
		console.log('close win');
		$j(this).closest('.modal-overlay-container').css('visibility', 'hidden');
	});

	$j('.info').click(function(){
		console.log('click');
		$j($j(this).attr('data-target')).css('visibility','visible');
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
