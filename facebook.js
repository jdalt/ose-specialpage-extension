
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
		channelUrl : '//test.opensourceecology.org/w/extensions/TrueFans/lib/channel.php',
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
};

function postToMyFeed()
{
	executeOnAuth(function(response) {
		var messageText = $j('#ose-truefan-friends-message').val();
		var userLink = $j('#user-video-link').val();
		var userName = $j('#user-name').val();
		var postData =
		{
			message: messageText,
			name: userName + ' - OSE True Fan Story',
			link: userLink,
			picture: 'http://www.opensourceecology.org/w/ose-logo.png',
			description: 'A Network of Farmers, Engineers, and Supporters Building the Global Village Construction Set',
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
				$j('#trueFanForm').submit();
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
