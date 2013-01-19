<?php
# This is the setup file for the TrueFans extension

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/TrueFans/TrueFans.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TrueFans',
	'author' => 'Jacob Dalton',
	'url' => 'https://github.com/jdalt/ose-specialpage-extension',
	'descriptionmsg' => 'This extension provides a mechanism for share and disseminating videos about the Open Source Ecology project.',
	'version' => '0.1.0',
);

// !! This hook will not work in MediaWiki 1.16 !! The hook was only added in MW 1.17  :( 
$wgHooks['UnitTestsList'][] = 'eTrueFansRegisterUnitTests';
function eTrueFansRegisterUnitTests( &$files ) {
	$files[] = dirname( __FILE__ ) . '/TrueFansTest.php';
	return true;
}

# Schema updates for update.php
$wgHooks['LoadExtensionSchemaUpdates'][] = 'schemaPartyHook';
function schemaPartyHook( DatabaseUpdater $updater ) {
	$updater->addExtensionTable( 'true_fans',
    	dirname( __FILE__ ) . '/patches/truefans_table.sql', true );
	return true;
}

// this variable can't be used within hooks because dirname will return a different value within a hook
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['SpecialTrueFans'] = $dir . 'SpecialTrueFans.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['TrueFansDb'] = $dir . 'class.TrueFansDb.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['TrueFans'] = $dir . 'TrueFans.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['TrueFansAlias'] = $dir . 'TrueFans.alias.php'; # Location of an aliases file (Tell MediaWiki to load this file)
$wgSpecialPages['TrueFans'] = 'SpecialTrueFans'; # Tell MediaWiki about the new special page 
$wgSpecialPageGroups['TrueFans'] = 'other'; # Tell MediaWiki this page is in the  Other category for Special Page types
