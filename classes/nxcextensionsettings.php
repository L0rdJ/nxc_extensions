<?php
/**
 * @package nxcExtensions
 * @class   nxcExtensionSettings
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    17 Mar 2010
 **/

abstract class nxcExtensionSettings {

	public $name         = null;
	public $defaultOrder = 0;
	public $dependencies = array();

	public function __construct( $name ) {
		$this->name = $name;
	}

	abstract public function activate();

	abstract public function deactivate();

	protected function executeSQL( $filename ) {
		eZDebug::writeDebug( 'Executing "' . $filename . '"', $this->name );

		$db = eZDB::instance();

		$queries = file_get_contents( 'extension/' . $this->name . '/docs/mysql/' . $filename );
		$queries = explode( ";", $queries );
		foreach( $queries as $query ) {
			$query = trim( $query, "\n\t " );
			if( strlen( $query ) > 0 ) {
				eZDebug::writeDebug( $query, $this->name );
				$db->query( $query );
			}
		}
	}
}
?>