<?php
/**
 * @package nxcExtensions
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    17 Mar 2010
 **/

$Module = array(
	'name'               => 'NXC Extensions',
 	'variable_params'    => true,
	'ui_component_match' => 'view'
);

$ViewList = array();
$ViewList['extensions'] = array(
	'functions'               => array( 'setup' ),
	'ui_context'              => 'administration',
	'default_navigation_part' => 'ezsetupnavigationpart',
	'script'                  => 'extensions.php',
	'single_post_actions'     => array(
		'ActivateExtensionsButton'     => 'ActivateExtensions',
		'GenerateAutoloadArraysButton' => 'GenerateAutoloadArrays'
	),
 	'params'                  => array()
);

$FunctionList          = array();
$FunctionList['setup'] = array();
?>