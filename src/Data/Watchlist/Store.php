<?php

namespace BlueSpice\Data\Watchlist;

use BlueSpice\Data\NoWriterException;
use BlueSpice\Services;

class Store implements \BlueSpice\Data\IStore {

	/**
	 *
	 * @var \IContextSource
	 */
	protected $context = null;

	/**
	 *
	 * @param \IContextSource $context
	 */
	public function __construct( $context ) {
		$this->context = $context;
	}

	/**
	 *
	 * @return Reader
	 */
	public function getReader() {
		return new Reader(
			Services::getInstance()->getDBLoadBalancer(),
			$this->context
		);
	}

	/**
	 *
	 * @throws NoWriterException
	 */
	public function getWriter() {
		throw new NoWriterException();
	}
}
