#TrueFans#
This extension is to be used for Open Source Ecology's True Fans microfunding platform. This code is to be tested on a LAMP stack development environment running MediaWiki 1.20.


##Installation##
To use this extension clone this git project in the '/extensions' directory of MediaWiki. The project should be in the directory TrueFans (git clone will probably create a folder based on the projects github name--change it to TrueFans).

Add the following lines to LocalSettings.php:

	require_once( "$IP/extensions/TrueFans/TrueFans.php" );
	$wgDebugLogFile = "$IP/logs/debug_log.log";
	$wgDebugLogGroups = array(
		'TrueFans'     => "$IP/extensions/TrueFans/logs/true_fans.log",
	);

Then navigate to your wiki /maintenance directory and run update.php to create database tables. 