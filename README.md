#ShareOSE#
This extension is to be used for Open Source Ecology's True Fans microfunding platform. This code is to be tested on a LAMP stack development environment running MediaWiki 1.16.


##Installation##
To use this extension clone this git project in the '/extensions' directory of MediaWiki. The project should be in the directory ShareOSE (git clone will probably create a folder based on the projects github name--change it to ShareOSE).

Add the following lines to LocalSettings.php:

require_once( "$IP/extensions/ShareOSE/ShareOSE.php" );
$wgDebugLogFile = "$IP/logs/debug_log.log";
$wgDebugLogGroups = array(
        'ShareOSE'     => "$IP/extensions/ShareOSE/logs/share_ose.log",
);

*List
*Listy
*Lister

Tytho
