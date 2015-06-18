<?php
/**
 * @package nxcExtensions
 * @class   nxcExtension
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    17 Mar 2010
 **/

class nxcExtension {

	private $attributes = array(
		'name'         => null,
		'order'        => 0,
		'is_activated' => false,
		'dependencies' => array()
	);

	private $settings = null;

	public function __construct( $name ) {
		$this->setAttribute( 'name', $name );

        $settingsFileName = eZDir::path( array( eZExtension::baseDirectory(), $this->attribute( 'name' ), 'settings.php' ) );
        if ( file_exists( $settingsFileName ) ) {
            include_once( $settingsFileName );
            $className = $this->attribute( 'name' ) . 'Settings';
            if( class_exists( $className ) ) {
            	$settingsObject = new $className( $this->attribute( 'name' ) );
            	if( $settingsObject instanceof nxcExtensionSettings ) {
            		$this->settings = $settingsObject;
            		$this->setAttribute( 'order', $this->settings->defaultOrder );
            		$this->setAttribute( 'dependencies', $this->settings->dependencies );
            	}
            }
        }
	}

	public function activate() {
		return ( $this->settings !== null ) ? $this->settings->activate() : null;
	}

	public function deactivate() {
		return ( $this->settings !== null ) ? $this->settings->deactivate() : null;
	}

	public function deactivateDependent( array $availableExtensions, array &$selectedExtensionArray ) {
		foreach( $availableExtensions as $extension ) {
			if( in_array( $this->attribute( 'name' ), $extension->attribute( 'dependencies' ) ) ) {
				$index = array_search( $extension->attribute( 'name' ), $selectedExtensionArray );
				if( $index !== false ) {
					unset( $selectedExtensionArray[ $index ] );
				}
				$extension->setAttribute( 'is_activated', false );
				$extension->deactivateDependent( $availableExtensions, $selectedExtensionArray );
			}
		}
	}

	public function checkDependencies( array $availableExtensions ) {
		$activeExtensionsList = array();
		foreach( $availableExtensions as $extension ) {
			if( $extension->attribute( 'is_activated' ) === true ) {
				$activeExtensionList[] = $extension->attribute( 'name' );
			}
		}

		$result = array(
			'canBeActivated' => true,
			'warnings'       => array()
		);
		foreach( $this->attribute( 'dependencies' ) as $extensionName ) {
			if( in_array( $extensionName, $activeExtensionList ) === false ) {
				$result['canBeActivated'] = false;
				$result['warnings'][]     = ezi18n(
					'design/admin/setup/extensions',
					'<strong>%extension</strong> cann`t be activated. You should activate <strong>%dependency</strong> at first',
					null,
					array(
						'%extension'  => $this->attribute( 'name' ),
						'%dependency' => $extensionName
					)
				);
			}
		}

		return $result;
	}

	public function attributes() {
		return array_keys( $this->attributes );
	}

	public function hasAttribute( $attr ) {
		return isset( $this->attributes[ $attr ] );
	}

	public function attribute( $attr ) {
		return ( isset( $this->attributes[ $attr ] ) ) ? $this->attributes[ $attr ] : null;
	}

	public function setAttribute( $attr, $value ) {
		$this->attributes[ $attr ] = $value;
	}
}
?>