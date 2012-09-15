<?php
# This is the setup file for the ShareOSE extension

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/ShareOSE/ShareOSE.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
        'path' => __FILE__,
        'name' => 'ShareOSE',
        'author' => 'Brad Lewis',
        'url' => 'https://www.mediawiki.org/wiki/Extension:MyExtension',
        'descriptionmsg' => 'This extension provides several pages designed for sharing personalized video messages and directing friends/colleagues to an introductory webpage',
        'version' => '0.0.1',
);
 
$dir = dirname(__FILE__) . '/';
 
$wgAutoloadClasses['SpecialShareOSE'] = $dir . 'SpecialShareOSE.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['ShareOSE'] = $dir . 'ShareOSE.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['ShareOSEAlias'] = $dir . 'ShareOSE.alias.php'; # Location of an aliases file (Tell MediaWiki to load this file)
$wgSpecialPages['ShareOSE'] = 'SpecialShareOSE'; # Tell MediaWiki about the new special page 
$wgSpecialPageGroups['ShareOSE'] = 'other'; # Tell MediaWiki this page is in the  Other category for Special Page types