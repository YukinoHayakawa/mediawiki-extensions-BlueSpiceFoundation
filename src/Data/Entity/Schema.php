<?php

namespace BlueSpice\Data\Entity;

use MediaWiki\MediaWikiServices;
use BlueSpice\Data\FieldType;
use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\Social\EntityConfig;

class Schema extends \BlueSpice\Data\Schema {
	const STORABLE = 'storeable';
	const INDEXABLE = 'indexable';

	protected function getDefaultFieldDefinition() {
		return [
			self::FILTERABLE => true,
			self::SORTABLE => true,
			self::TYPE => FieldType::STRING,
			self::STORABLE => true,
			self::INDEXABLE => true,
		];
	}

	protected function fillMissingWithDefaults( $fieldDefinition ) {
		foreach ( $this->getDefaultFieldDefinition() as $key => $defaultVal ) {
			if ( array_key_exists( $key, $fieldDefinition ) ) {
				continue;
			}
			$fieldDefinition[$key] = $defaultVal;
		}
		return $fieldDefinition;
	}

	/**
	 *
	 * @return EntityConfig[]
	 */
	protected function getEntityConfigs() {
		$entityConfigs = [];
		$registry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceFoundationEntityRegistry'
		);
		$configFactory = MediaWikiServices::getInstance()->getService(
			'BSEntityConfigFactory'
		);
		foreach ( $registry->getAllKeys() as $type ) {
			if ( !$entityConfig = $configFactory->newFromType( $type ) ) {
				continue;
			}
			$entityConfigs[] = $entityConfig;
		}
		return $entityConfigs;
	}

	public function __construct() {
		$scheme = [];
		foreach ( $this->getEntityConfigs() as $entityConfig ) {
			$definitions = $entityConfig->get( 'AttributeDefinitions' );
			foreach ( $definitions as $key => $definition ) {
				$definitions[$key] = $this->fillMissingWithDefaults(
					$definition
				);
			}
			$scheme = array_merge( $scheme, $definitions );
		}
		parent::__construct( $scheme );
	}

	/**
	 * @return string[]
	 */
	public function getIndexableFields() {
		return $this->filterFields( self::INDEXABLE, true );
	}

	/**
	 * @return string[]
	 */
	public function getStorableFields() {
		return $this->filterFields( self::STORABLE, true );
	}

	/**
	 * @return string[]
	 */
	public function getUnindexableFields() {
		return $this->filterFields( self::INDEXABLE, false );
	}

	/**
	 * @return string[]
	 */
	public function getUnstorableFields() {
		return $this->filterFields( self::STORABLE, false );
	}
}
