<?php
/**
 * Provides the task base class for BlueSpice.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://bluespice.com
 *
 * @author     Patric Wirth
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2019 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

namespace BlueSpice;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Message;
use MessageLocalizer;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use Psr\Log\LoggerInterface;
use Status;
use WikiPage;

abstract class Task implements ITask, IServiceProvider, MessageLocalizer {

	/**
	 *
	 * @var MediaWikiServices
	 */
	protected $services = null;

	/**
	 *
	 * @var Context
	 */
	protected $context = null;

	/**
	 *
	 * @var ActionLogger
	 */
	protected $actionLogger = null;

	/**
	 *
	 * @var LoggerInterface
	 */
	protected $logger = null;

	/**
	 *
	 * @var INotifier
	 */
	protected $notifier = null;

	/**
	 *
	 * @var IPermissionChecker
	 */
	protected $permissionChecker = null;

	/**
	 *
	 * @var array
	 */
	protected $params = [];

	/**
	 * @param MediaWikiServices $services
	 * @param Context $context
	 * @param LoggerInterface $logger
	 * @param ActionLogger $actionLogger
	 * @param INotifier $notifier
	 * @param IPermissionChecker $permissionChecker
	 */
	protected function __construct( MediaWikiServices $services, Context $context,
		LoggerInterface $logger, ActionLogger $actionLogger, INotifier $notifier,
		IPermissionChecker $permissionChecker ) {
		$this->services = $services;
		$this->context = $context;
		$this->actionLogger = $actionLogger;
		$this->notifier = $notifier;
		$this->logger = $logger;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @param MediaWikiServices $services
	 * @param Context $context
	 * @param IPermissionChecker|null $permissionChecker
	 * @return ITask
	 */
	public static function factory( MediaWikiServices $services, Context $context,
		IPermissionChecker $permissionChecker = null ) {
		$actionLogger = new NullLogger();
		$logger = LoggerFactory::getInstance( static::class );
		$notifier = $services->getService( 'BSNotificationManager' )->getNotifier();
		if ( !$permissionChecker ) {
			$permissionChecker = new NullPermissionChecker();
		}
		return new static(
			$services,
			$context,
			$logger,
			$actionLogger,
			$notifier,
			$permissionChecker
		);
	}

	/**
	 *
	 * @return string[]
	 */
	public function getTaskPermissions() {
		return [ 'read' ];
	}

	/**
	 * @param array $params
	 * @param Status|null $status
	 * @return Status
	 */
	public function execute( array $params = [], Status $status = null ) {
		if ( !$status ) {
			$status = Status::newGood();
		} elseif ( !$status->isGood() ) {
			return $status;
		}
		$this->logger->debug( 'starting', [ 'params' => $params ] );
		$this->params = $params;
		try {
			$status->merge( $this->checkTaskPermissions() );
			if ( !$status->isGood() ) {
				return $status;
			}
			$status->merge( $this->doExecute() );
			$this->logger->debug( 'doExecute', [ 'status' => $status ] );
			if ( !$status->isOK() ) {
				return $status;
			}
			if ( $this->shouldRunUpdates() ) {
				$status->merge( $this->runUpdates() );
				$this->logger->debug( 'runUpdates', [ 'status' => $status ] );
			}
		} catch ( \Exception $ex ) {
			$this->logger->debug( 'exception', [ 'message' => $ex->getMessage() ] );
			$status->fatal( $ex->getMessage() );
			return $status;
		}

		$this->logger->debug( 'done', [ 'status' => $status ] );
		return $status;
	}

	/**
	 *
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	protected function getParam( $name, $default = null ) {
		return isset( $this->params[$name] ) ? $this->params[$name] : $default;
	}

	/**
	 * @return Status
	 */
	abstract protected function doExecute();

	/**
	 * @return bool
	 */
	protected function shouldRunUpdates() {
		return false;
	}

	/**
	 *
	 * @return Status
	 */
	protected function runUpdates() {
		$title = $this->context->getTitle();
		if ( $title && $title->getNamespace() >= NS_MAIN ) {
			$wikiPage = WikiPage::factory( $title );
			$content = $wikiPage->getContent();
			if ( $content instanceof \Content ) {
				$updates = $content->getSecondaryDataUpdates( $title );
				\DataUpdate::runUpdates( $updates );
			}
		}
		return Status::newGood();
	}

	/**
	 *
	 * @return Services
	 */
	public function getServices() {
		return $this->services;
	}

	/**
	 * @param string|string[]|MessageSpecifier $key Message key, or array of keys,
	 *   or a MessageSpecifier.
	 * @param mixed ...$params Normal message parameters
	 * @return Message
	 */
	public function msg( $key, ...$params ) {
		return $this->getContext()->msg( $key, ...$params );
	}

	/**
	 *
	 * @return Context
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 *
	 * @return Status
	 */
	protected function checkTaskPermissions() {
		$status = Status::newGood();
		foreach ( $this->getTaskPermissions() as $permission ) {
			$res = $this->permissionChecker->userCan(
				$this->context->getUser(),
				$permission,
				$this->context
			);
			if ( $res ) {
				continue;
			}
			$this->logger->debug(
				'permissionserrors',
				[ 'permission' => $permission ]
			);
			$status->fatal( $this->msg( 'bs-permissionerror' ) );
			break;
		}

		return $status;
	}
}
