#TrueFans#
This extension is to be used for Open Source Ecology's True Fans microfunding platform. This code is to be tested on a LAMP stack development environment running MediaWiki 1.20. This extension also assumes the installation of the OpenId exenstion,

##Installing the Environment##
In your environment of choice you will need to install Apache, MySQL, and PhP. For ubunt systems - sudo taskel install lamp-server - works quite nicely (you'll probably have to install 'tasksel' as well. 

Next you'll need to download [http://download.wikimedia.org/mediawiki/1.20/mediawiki-1.20.2.tar.gz MediaWiki 1.20]. You can follow the [http://www.mediawiki.org/wiki/Manual:Installation_guide distribution specific] installation guide or the [http://www.mediawiki.org/wiki/Manual:Installing_MediaWiki general] installation to get everything up and running. Next add any extensions you want to your installation. I suggest installing the  [http://www.mediawiki.org/wiki/Extension:OpenID OpenId], you can download it [http://www.mediawiki.org/wiki/Special:ExtensionDistributor/OpenID here], and then cloning this extension (instructions below). Be sure to read the [http://www.mediawiki.org/wiki/Extension:OpenID#Installation OpenId installation instructions] (you need to run 'make' to download some extra libraries). Although you can run it once for both OpenId and TrueFans, the script 'php maintenance/install.php' must be run to create tables for extensions that need them.

##Installing True Fans##
To use the TrueFans extension clone this git project in the '/extensions' directory of MediaWiki. The project should be in the directory TrueFans (git clone will probably create a folder based on the projects github name--change it to TrueFans).

Add the following lines to LocalSettings.php:

	require_once( "$IP/extensions/TrueFans/TrueFans.php" );
	$wgDebugLogFile = "$IP/logs/debug_log.log";
	$wgDebugLogGroups = array(
		'TrueFans'     => "$IP/extensions/TrueFans/logs/true_fans.log",
	);

Then navigate to your wiki /maintenance directory and run 'php update.php' to create the necessary database tables. 