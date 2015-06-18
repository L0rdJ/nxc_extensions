<?php
/**
 * @package nxcExtensions
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    17 Mar 2010
 **/

$http   = eZHTTPTool::instance();
$module = $Params['Module'];

require_once( "kernel/common/template.php" );
$tpl = templateInit();

$warnings = array();

$siteINI = eZINI::instance();
$siteINI->load();

$selectedExtensionList = array_merge(
	$siteINI->variable( 'ExtensionSettings', 'ActiveExtensions' ),
	$siteINI->variable( 'ExtensionSettings', 'ActiveAccessExtensions' )
);
$selectedExtensionList = array_unique( $selectedExtensionList );
$orders                = $siteINI->variable( 'ExtensionSettings', 'ActiveExtensionsOrder' );

$availableExtensions    = array();
$extensionDir           = eZExtension::baseDirectory();
$availableExtensionList = eZDir::findSubItems( $extensionDir, 'dl' );
foreach( $availableExtensionList as $extensionName ) {
	$extension = new nxcExtension( $extensionName );

	if( in_array( $extension->attribute( 'name' ), $selectedExtensionList ) ){
		$extension->setAttribute( 'is_activated', true );
	}

	if( isset( $orders[ $extensionName ] ) ) {
		$extension->setAttribute( 'order', $orders[ $extensionName ] );
	}

	$availableExtensions[ $extension->attribute( 'name' ) ] = $extension;
}
uasort( $availableExtensions, 'compareExtensionsOrder' );

if ( $module->isCurrentAction( 'ActivateExtensions' ) ) {
	$ini		= eZINI::instance( 'module.ini' );
	$oldModules = $ini->variable( 'ModuleSettings', 'ModuleList' );

	if ( $http->hasPostVariable( 'ActiveExtensionList' ) ) {
		$selectedExtensionArray = $http->postVariable( 'ActiveExtensionList' );
		if ( !is_array( $selectedExtensionArray ) ) {
			$selectedExtensionArray = array( $selectedExtensionArray );
		}

		$orders = $http->hasPostVariable( 'ExtensionOrders' ) ? $http->postVariable( 'ExtensionOrders' ) : array();
		foreach( $availableExtensions as $extension ) {
			if( isset( $orders[ $extension->attribute( 'name' ) ] ) ) {
				$extension->setAttribute( 'order', (int) $orders[ $extension->attribute( 'name' ) ] );
			}

			if( in_array( $extension->attribute( 'name' ), $selectedExtensionArray ) ) {
				if( $extension->attribute( 'is_activated' ) === false ) {
					$result = $extension->checkDependencies( $availableExtensions );
					if( $result['canBeActivated'] === true ) {
						$extension->setAttribute( 'is_activated', true );
						$extension->activate();
					} else {
						$warnings = array_merge( $warnings, $result['warnings'] );
					}
				}
			} else {
				if( $extension->attribute( 'is_activated' ) === true ) {
					$extension->setAttribute( 'is_activated', false );
					$extension->deactivate();
					$extension->deactivateDependent( $availableExtensions, $selectedExtensionArray );
				}
			}
		}
	}

	// open settings/override/site.ini.append[.php] for writing
	$writeSiteINI = eZINI::instance( 'site.ini.append', 'settings/override', null, null, false, true );

	uasort( $availableExtensions, 'compareExtensionsOrder' );
	$activeExtensionsList        = array();
	$activeExntesionsOrderList   = array();
	foreach( $availableExtensions as $extension ) {
		if( $extension->attribute( 'is_activated' ) ) {
			$activeExtensionsList[] = $extension->attribute( 'name' );
		}
		$activeExntesionsOrderList[ $extension->attribute( 'name' ) ] = $extension->attribute( 'order' );
	}

	$writeSiteINI->setVariable( 'ExtensionSettings', 'ActiveExtensions', $activeExtensionsList );
	$writeSiteINI->setVariable( 'ExtensionSettings', 'ActiveExtensionsOrder', $activeExntesionsOrderList );
	$writeSiteINI->save( 'site.ini.append', '.php', false, false );
	eZCache::clearByTag( 'ini' );

	eZSiteAccess::reInitialise();

	$ini = eZINI::instance( 'module.ini' );
	$currentModules = $ini->variable( 'ModuleSettings', 'ModuleList' );
	if ( $currentModules != $oldModules ) {
		// ensure that evaluated policy wildcards in the user info cache
		// will be up to date with the currently activated modules
		eZCache::clearByID( 'user_info_cache' );
	}

	updateAutoload( $tpl, $warnings );
}

if ( $module->isCurrentAction( 'GenerateAutoloadArrays' ) ) {
	updateAutoload( $tpl, $warnings );
}

$availableExtensions = array_reverse( $availableExtensions );
$tpl->setVariable( 'available_extensions', $availableExtensions );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:setup/nxc_extensions.tpl' );
$Result['path']    = array(
	array(
		'url' => false,
		'text' => ezi18n( 'kernel/setup', 'Extension configuration' )
	)
);

function updateAutoload( $tpl = null, $existingWarnings = null ) {
	if( is_null( $existingWarnings ) ) {
		$existingWarnings = array();
	}

	$autoloadGenerator = new eZAutoloadGenerator();
	try	{
		$autoloadGenerator->buildAutoloadArrays();

		$messages = $autoloadGenerator->getMessages();
		foreach( $messages as $message ) {
			eZDebug::writeNotice( $message, 'eZAutoloadGenerator' );
		}

		$warnings = $autoloadGenerator->getWarnings();
		foreach ( $warnings as &$warning ) {
			eZDebug::writeWarning( $warning, "eZAutoloadGenerator" );

			// For web output we want to mark some of the important parts of
			// the message
			$pattern = '@^Class\s+(\w+)\s+.* file\s(.+\.php).*\n(.+\.php)\s@';
			preg_match( $pattern, $warning, $m );

			$warning = str_replace( $m[1], '<strong>'.$m[1].'</strong>', $warning );
			$warning = str_replace( $m[2], '<em>'.$m[2].'</em>', $warning );
			$warning = str_replace( $m[3], '<em>'.$m[3].'</em>', $warning );
		}

		if ( $tpl !== null ) {
			$tpl->setVariable( 'warning_messages', array_merge( $existingWarnings, $warnings ) );
		}
	} catch ( Exception $e ) {
		eZDebug::writeError( $e->getMessage() );
	}
}

function compareExtensionsOrder( $a, $b ) {
	if( $a->attribute( 'order' ) == $b->attribute( 'order' ) ) {
		return strcmp( strtolower( $b->attribute( 'name' ) ), strtolower( $a->attribute( 'name' ) ) );
	}
	return ( $a->attribute( 'order' ) > $b->attribute( 'order' ) ) ? -1 : 1;
}

?>
