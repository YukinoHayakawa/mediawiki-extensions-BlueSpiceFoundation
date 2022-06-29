<?php

namespace BlueSpice\Data\Categories;

use MWStake\MediaWiki\Component\DataStore\Record as RecordBase;

class Record extends RecordBase {
	public const CAT_ID = 'cat_id';
	public const CAT_TITLE = 'cat_title';
	public const CAT_PAGES = 'cat_pages';
	public const CAT_SUBCATS = 'cat_subcats';
	public const CAT_FILES = 'cat_files';
	public const CAT_LINK = 'cat_link';
}
