
var gAppID = '130775487070378';

console.log('attempting to load in facebook');

//Initialize the Facebook SDK
window.fbAsyncInit = function() {
	FB.init({
		appId: gAppID,
		channelUrl : '//wwwtest.collaborative-revolution.org/w/extensions/ShareOSE/lib/channel.php',
		status: true,
		cookie: true,
		xfbml: false,
		frictionlessRequests: false,
		useCachedDialogs: true,
		oauth: true
	});

	authUser();

	$j(document).ready(function () {

		/* Friend selector stuff */
		TDFriendSelector.init({debug: true, speed: 25});
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
			}
		});

		$j("#facebook-button").click(function (e) {
			console.log('Yo we should be popping a friend selector...')
			FB.login(function(response) {
				if (response.authResponse) {
					console.log('Welcome!  Fetching your information.... ');
			    	FB.api('/me', function(response) {
						console.log('Good to see you, ' + response.name + '.');
			    	 });
			  	} else {
					console.log('User cancelled login or did not fully authorize.');
			  	}
				friendSelector.showFriendSelector();
			});
			e.preventDefault();
		});
	});
};

// Load the SDK Asynchronously
(function(d){
	var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement('script'); js.id = id; js.async = true;
	js.src = "//connect.facebook.net/en_US/all.js";
	ref.parentNode.insertBefore(js, ref);
}(document));

var user = [];
//Detect when Facebook tells us that the user's session has been returned
function authUser() {
	FB.Event.subscribe('auth.statusChange', function(session) {
	console.log('Got the user\'s session: ', session);

	if (session && session.status != 'not_authorized') {
		if (session.authResponse['accessToken']) {
		document.body.className = 'connected';

		//Fetch user's id, name, and picture
		FB.api('/me', {
			fields: 'name, picture'
			},
			function(response) {
			if (!response.error) {
				user = response;

				console.log('authUser::Got the user\'s name and picture: ', response);

				//Update display of user name and picture
				if (document.getElementById('user-name')) {
					document.getElementById('user-name').innerHTML = user.name;
				}
				if (document.getElementById('user-picture')) {
					document.getElementById('user-picture').src = user.picture.data.url;
				}
			}
			});
		}
	} else if (session === undefined) {
		document.body.className = 'not_connected';
	}
	else if (session && session.status == 'not_authorized') {
		document.body.className = 'not_connected';
	}
	});
}

//Detect when Facebook tells us that the user's session has been returned
function updateAuthElements() {
	FB.Event.subscribe('auth.statusChange', function(session) {
	//If the user isn't logged in, set the body class so that we show/hide the correct elements
	if (session == undefined || session.status == 'not_authorized') {
		if (document.body.className != 'not_connected') {
			document.body.className = 'not_permissioned';
		}
	}
	//The user is logged in, so let's see if they've granted the check-in permission and pre-fetch some data
	//Depending on if they have or haven't, we'll set the body to reflect that so we show/hide the correct elements on the page
	else {
		preFetchData();

		// !!!! this needs to be deleted !!!

		FB.api({method: 'fql.query', query: 'SELECT user_checkins, publish_checkins FROM permissions WHERE uid = ' + session.authResponse['userID']}, function(response) {
		if (document.body.className != 'not_connected') {
			//We couldn't get a check-in for the user, so they haven't granted the permission
			if (response[0].user_checkins == 1) {
			document.body.className = 'permissioned';
			}
			//We were able to get a check-in for the user, so they have granted the permission already
			else {
				document.body.className = 'not_permissioned';
			}
		}
		});
	}
});
}


function postRobotMessage()
{
	FB.login(function(response) {
		console.log(response);
		if (response.authResponse) {
			console.log('got publish stream permissions');

			var friendArray = $j('#RobotMessage').val().split(", ");
			var messageText = $j('#message-text').val();

			for(var i=0; i<friendArray.length; i++) {
				postFriend = TDFriendSelector.getFriendById(friendArray[i]);
				console.log(postFriend);

				var postData =
				{
					to: postFriend.id,
					message: messageText,
					name: 'Open Source Ecology True Fans',
					caption: 'Build yourself.',
					description: 'Moar machines...!!',
					link: 'http://wwwtest.collaborative-revolution.com/wiki/',
					picture: 'http://www.wordpages.org/facebook/lib/ose-logo.png',
				};

				FB.api('/' + postFriend.id + '/feed', 'post', postData, function(response) {
					if (!response || response.error) {
						console.log(response);
						alert('Error occured');
					} else {
						alert('Post ID: ' + response.id);
					}
				});
			}

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
				});
			} else {
				console.log('User cancelled login or did not fully authorize.');
				alert('stop you are not authorized');
			}
	}, {scope: 'publish_stream'});
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
