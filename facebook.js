
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

	// ? is this necessary ?
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
				$j('#friend-selector-holder').css('display', 'none');
			}
		});

		$j('#CancelFriendSelect').click(friendSelector.hideFriendSelector);
		$j('#TDFriendSelector_buttonClose').click(function(){console.log('clicked close button for friendselector'); $j('#friend-selector-holder').css('display','none');});

		$j("#facebook-button").click(function (e) {
			$j('#friend-selector-holder').css('display', 'block');
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
		$j('#trueFanForm').submit(function(e) {
			e.preventDefault();
			console.log('Posting to feed. Hold on to your butts.');
			console.log(TDFriendSelector);
			postFacebookFeed(TDFriendSelector.getFriends());
			return false;
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

function postFacebookFeed(friendArray)
{
	FB.login(function(response) {
		console.log(response);
		if (response.authResponse) {
			console.log('got publish stream permissions');

			var messageText = $j('#ose-truefan-friends-message').val();

			for(var i=0; i<friendArray.length; i++) {
				postFriend = friendArray[i];
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
					console.log('Attempting to submit form.');
					$j('#trueFanForm').submit();
				});
			}

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
