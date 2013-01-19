/*
 *  dynamic.js
 */

//TODO: evaluate utility of these globals...any use out of finish.html template?
var autoReplaceArray, autoInputArray;

$j(document).ready(function() {
	console.log('$j says document ready');

	// only some pages have #video-viewer
	$j('#video-viewer').append('<div id="loading-block"><p>Youtube plugin is loading.</p><img src="/w/extensions/TrueFans/images/loading.gif" /></div>');
	setTimeout(function(){
		$j('#loading-block').append('<p class="warn">You may have to refresh the page to load Youtube plugin.</p>');
	}, 5000);

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

});

function runOnloadHook()
{
	console.log('runonloadhook');
}
