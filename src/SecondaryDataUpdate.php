<?php

namespace BlueSpice;

use Content;
use Exception;
use Status;
use Title;
use WikiPage;

abstract class SecondaryDataUpdate implements ISecondaryDataUpdate {
	/**
	 * @return ISecondaryDataUpdate
	 */
	public static function factory() {
		return new static;
	}

	/**
	 * @param Title $title
	 * @return Status
	 */
	public function run( Title $title ) {
		try {
			$wikiPage = WikiPage::factory( $title );
			$content = $wikiPage->getContent();
			if ( !$content ) {
				return Status::newFatal( 'WikiPage does not have a content' );
			}
			$status = $this->doRun( $title, $wikiPage, $content );
		} catch ( Exception $e ) {
			return Status::newFatal( $e->getMessage() );
		}
		return $status;
	}

	/**
	 * @param Title $title
	 * @param WikiPage $wikiPage
	 * @param type $content
	 * @return Status
	 */
	abstract protected function doRun( Title $title, WikiPage $wikiPage, Content $content );
}
