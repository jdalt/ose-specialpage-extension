
var gAppID = '130775487070378';
var facebookSubmit = 0;
var FB_AUTH_STATUS = 'undefined';
console.log('attempting to load in facebook');

// Load the SDK Asynchronously
(function(d){
	var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement('script'); js.id = id; js.async = true;
	js.src = "//connect.facebook.net/en_US/all.js";
	ref.parentNode.insertBefore(js, ref);
}(document));

//Initialize the Facebook SDK
window.fbAsyncInit = function() {
	FB.init({
		appId: gAppID,
		channelUrl : '//wwwtest.collaborative-revolution.org/w/extensions/ShareOSE/lib/channel.php',
		status: true,
		cookie: true,
		xfbml: false,
		frictionlessRequests: true,
		useCachedDialogs: true,
		oauth: true
	});

	FB.Event.subscribe('auth.statusChange', function(session) {
		console.log('Got the user\'s session: ', session);
		console.log(session.status);
	
		if (session && session.status != 'not_authorized') {
			if (session.authResponse['accessToken']) {
				// connected
				FB_AUTH_STATUS = 'connected';
			}
			// FIXME: else condition?
		} else if (session === undefined) {
			// not connected
			FB_AUTH_STATUS = 'not_connected';
		}
		else if (session && session.status == 'not_authorized') {
			// not authorized
			FB_AUTH_STATUS = 'not_authorized';
		}
	});

	// ?? Do I need to auth first -- debug and try no login and no auth...although really only want to auth if user clicks fb button
	$j(document).ready(function () {

		/* Friend selector stuff */
/*		TDFriendSelector.init({debug: true, speed: 25});
		friendSelector = TDFriendSelector.newInstance({
			callbackSubmit: function(selectedFriendIds) {
				console.log("The following friends were selected: " + selectedFriendIds.join(", "));
				var html = '<ul>\n';
				for(var i=0; i < selectedFriendIds.length; i++) {
					console.log(selectedFriendIds[i]);
					var friend = TDFriendSelector.getFriendById(selectedFriendIds[i]);
					console.log(friend);
					html += '<li><img src="//graph.facebook.com/' + friend.id + '/picture?type=square" /><span>' + friend.name + '</span></li>\n'; 
				}
				html += '</ul>\n';
				$j('#share-facebook-preview').html(html);
				$j('#friend-selector-holder').css('visibility', 'hidden');
			}
		});

		$j('#CancelFriendSelect').click(friendSelector.hideFriendSelector);
		$j('#TDFriendSelector_buttonClose').click(function(){console.log('clicked close button for friendselector'); $j('#friend-selector-holder').css('visibility','hidden');});

		$j("#facebook-private").click(function (e) {
			$j('#friend-selector-holder').css('visibility', 'visible');
			console.log('Yo we should be popping a friend selector...')
			executeOnAuth(function(response) {
				console.log('we can has auth');
				friendSelector.showFriendSelector();
			});
		});
*/
	});
};

function postToMyFeed()
{
	executeOnAuth(function(response) {
		var messageText = $j('#ose-truefan-friends-message').val();
		var postData =
		{
			message: messageText,
			name: 'Open Source Ecology True Fans',
			caption: 'Build yourself.',
			description: 'Moar machines...!!',
			link: 'http://wwwtest.collaborative-revolution.com/wiki/',          // !!! change this here !!! //
			picture: 'http://www.wordpages.org/facebook/lib/ose-logo.png',
		};

		FB.api('/me/feed', 'post', postData, function(response) {
			if (!response || response.error) {
				// TODO: Relay this message back UX
				console.log(response);
				console.log('Error occured posting to feed.');
				alert('Error occured');
			} else {
				console.log('Posted to facebook feed.');
				havePostedToFeed = true;
				$j('.mw-htmlform-submit').click(); 
			}
		});
	  /*
		// Now publish an action the user who made the video's wall/timeline
		FB.api('/me/osetruefantest:join', 'post',
		{ cause: 'http://www.wordpages.org/facebook/fb_obj.html' },
			function(response) {
				if (!response || response.error) {
					console.log(response);
					alert('Error occured. Unable to publish action on your timeline.'+response.error);
				} else {
					alert('Join Cause action was successful! Action ID: ' + response.id);
				}
		});*/

	});
}

function uninstallApp() {
FB.api({method: 'auth.revokeAuthorization'},
	function(response) {
		console.log('Revoked authorization.');
		window.location.reload();
	});
}

function logout() {
	FB.logout(function(response) {
		window.location.reload();
	});
}

function executeOnAuth(callback)
{
	console.log(FB_AUTH_STATUS);
	if(FB_AUTH_STATUS == 'connected') {
		callback();
	} else {
		FB.login(function(response) {
			if(response.authResponse) {
				console.log('authed now calling callback');
				callback();
			} else {
				// !! Output do common App debug outlets
				console.log('User did not fully authorize. Callback function did not execute.');
			}
		}, {scope: 'publish_stream'}); 
	}
}
