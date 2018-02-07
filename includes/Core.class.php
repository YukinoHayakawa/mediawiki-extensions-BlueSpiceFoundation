<?php

/**
 * This file contains the BsCore class.
 *
 * The BsCore class is the main class of the BlueSpice framework.
 * It controlls the whole life sequence of the framework.
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
 * For further information visit http://bluespice.com
 *
 * @author     Sebastian Ulbricht <sebastian.ulbricht@dragon-design.hk>
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Core
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */
use MediaWiki\MediaWikiServices;

/**
 * The BsCore
 * @package BlueSpice_Core
 * @subpackage Core
 */
class BsCore {

	public $aBehaviorSwitches = array();

	/**
	 *
	 * @var array
	 * @deprecated since version 2.22
	 */
	protected $aEditButtons = array();

	/**
	 *
	 * @var array
	 * @deprecated since version 2.22
	 */
	protected $aEditButtonRanking = array();

	/**
	 * Array of illegal chars in article title
	 * @var array
	 */
	protected static $prForbiddenCharsInArticleTitle = array( '#', '<', '>', '[', ']', '|', '{', '}' );
	/**
	 * an array of adapter instances
	 * @var array
	 */
	protected static $oInstance = null;
	/**
	 * a state flag if ExtJs is already loaded
	 * @var bool
	 * @deprecated since version 2.22
	 */
	protected static $bExtJsLoaded = false;
	/**
	 * holds the requested URI after the first time, the method getRequestURI was running
	 * @var string
	 */
	protected static $prRequestUri = null;
	/**
	 * a state flag if the requested URL is encodet
	 * @var bool
	 */
	protected static $prUrlIsEncoded = false;
	/**
	 * Local Parser
	 * @var object
	 */
	protected static $oLocalParser = false;
	/**
	 * Local Parser
	 * @var object
	 */
	protected static $oLocalParserOptions = false;
	/**
	 * Current User Object
	 * @var object
	 */
	protected static $prCurrentUser = null;
	/**
	 * Simple caching mechanism for UserMiniProfiles
	 * @var array
	 */
	protected static $aUserMiniProfiles = array();

	protected static $bHtmlFormClassLoaded = false;

	public static function getForbiddenCharsInArticleTitle() {
		return self::$prForbiddenCharsInArticleTitle;
	}

	/**
	 * Used to access the singleton BlueSpice object.
	 * @return BsCore Singleton instance of BlueSpice object.
	 */
	public static function getInstance() {
		if ( self::$oInstance === null ) {
			self::$oInstance = new BsCore();
		}
		return self::$oInstance;
	}

	/**
	 * When retrieving data from sources different from the BsCore::getParam()
	 * method, use this interface to sanitize the value. For security reasons it
	 * is strongly recommended to use this method!
	 * @param mixed $handover The value that has to be sanitized.
	 * @param mixed $default A default value that gets returned, if the
	 * submitted value is not valid or does not match the requested BsPARAMTYPE.
	 * @param BsPARAMTYPE $options Sets the type of the expected return value.
	 * This information is used for proper sanitizing.
	 * @return mixed Depending on the BsPARAMTYPE sumbitted in $options the
	 * sanitized $handover or in case of invalidity of $handover, the $default
	 * value.
	 */
	public static function sanitize($handover, $default = false, $options = BsPARAMTYPE::STRING) {
		// TODO MRG20100725: Ist die Reihenfolge hier Ã¼berlegt? Was ist, wenn ich BsPARAMTYPE::INT & BsPARAMTYPE::STRING angebe?
		// TODO MRG20100725: Kann man das nicht mit getParam zusammenschalten, so dass diese Funktion sanitize verwendet?
		// TODO MRG20100725: Sollte $default nicht auch durch den sanitizer?
		/* Lilu:
		 * Die Reihenfolge ist meiner Meinung nach unerheblich, da immer nur der erste BsPARAMTYPE, der einen Treffer landet,
		 * zurÃ¼ckgegeben wird.
		 * Eine Trennung zwischen getParam und sanitize besteht, da man bei getParam angeben kann, ob man im Fehlerfall
		 * Default-Werte verwenden mÃ¶chte oder versucht werden soll, die Daten mit sanitize zu bereinigen.
		 * Ich denke, das jeder Programmierer seinen Extensions passende Default-Werte liefern sollte.
		 * Beim Sanitizen der Default-Werte entsteht sonst ggf. das Problem, das wir keinen gÃ¼ltigen Wert zurÃ¼ckgeben kÃ¶nnen. (null?)
		 * Das wÃ¼rde einen groÃŸen Vorteil des Sanitizers (die nicht mehr benÃ¶tigte GÃ¼ltigkeitsprÃ¼fung) wieder aushebeln.
		 */
		if ( $options & BsPARAMTYPE::RAW ) {
			return $handover;
		}
		if ( $options & BsPARAMTYPE::ARRAY_MIXED ) {
			if ( is_array( $handover ) ) {
				return $handover;
			}
			return array( $handover );
		}
		if ( $options & BsPARAMTYPE::NUMERIC ) {
			if ( is_numeric( $handover ) ) {
				return $handover;
			}
			return floatval( $handover );
		}
		if ( $options & BsPARAMTYPE::INT ) {
			if ( is_int( $handover ) ) {
				return $handover;
			}
			return intval( $handover );
		}
		if ( $options & BsPARAMTYPE::FLOAT ) {
			if ( is_float( $handover ) ) {
				return $handover;
			}
			return floatval( $handover );
		}
		if ( $options & BsPARAMTYPE::BOOL ) {
			if ( $handover == 'false' || $handover == '0' || $handover == '' ) {
				$handover = false;
			}
			if ( $handover == 'true' || $handover == '1' ) {
				$handover = true;
			}
			if ( is_bool( $handover ) ) {
				return $handover;
			}
			return (bool)$handover;
		}
		if ( $options & BsPARAMTYPE::STRING ) {
			if ( is_string( $handover ) ) {
				if ( $options & BsPARAMOPTION::CLEANUP_STRING ) {
					return addslashes( strip_tags( $handover ) );
				}
				return $handover;
			}
		}
		if ( $options & BsPARAMTYPE::SQL_STRING ) {
			if ( is_string( $handover ) ) {
				$oDb = wfGetDB( DB_REPLICA );
				// Use database specific escape methods
				$handover = $oDb->strencode( $handover );

				return $handover;
			}
		}
		if ( $options & BsPARAMTYPE::ARRAY_NUMERIC && is_array( $handover ) ) {
			foreach ( $handover as $k => $v ) {
				if ( !is_numeric( $v ) ) {
					$handover[$k] = null;
				}
			}
			return $handover;
		}
		if ( $options & BsPARAMTYPE::ARRAY_INT && is_array( $handover ) ) {
			foreach ( $handover as $key => $v ) {
				if ( !is_int( $v ) ) {
					$handover[$key] = null;
				}
			}
			return $handover;
		}
		if ( $options & BsPARAMTYPE::ARRAY_FLOAT && is_array( $handover ) ) {
			foreach ( $handover as $key => $v ) {
				if ( !is_float( $v ) ) {
					$handover[$key] = null;
				}
			}
			return $handover;
		}
		if ( $options & BsPARAMTYPE::ARRAY_BOOL && is_array( $handover ) ) {
			foreach ($handover as $key => $v) {
				if (!is_bool($v)) {
					$handover[$key] = null;
				}
			}
			return $handover;
		}
		if ( $options & BsPARAMTYPE::ARRAY_STRING && is_array( $handover ) ) {
			foreach ( $handover as $key => $v ) {
				if ( !is_string( $v ) ) {
					$handover[$key] = null;
				}
			}
			return $handover;
		}
		// TODO MRG20100725: Ich halte eine Option TRIM / TRIMRIGHT / TRIMLEFT fÃ¼r sinnvoll.
		// TODO MRG20100725: Ebenso HTMLENTITIES etc, wie unten beschrieben.
		return $default;
		/*
		 * Development Notes:
		 * further functions to think about:
		 * - htmlentieties() um die HTML Eingaben abzufangen
		 *    => html_entity_decode() um die Umwandlung rÃ¼ckgÃ¤ngig zu machen
		 * - htmlspecialchars() - Wandelt Sonderzeichen in HTML-Codes um
		 * ==> neither htmlentities() nor htmlspecialchars() are used in directory bluespice-mw or beyond (exc. GeSHi)
		 * - escapeshellcmd()
		 * - escapeshellarg()
		 * ==> Only used in Rss/extlib/Snoopy.class.inc and GeSHi
		 *
		 * Alternate options
		 * HTML Purifier : http://htmlpurifier.org/
		 * Popoon: http://svn.bitflux.ch/repos/public/popoon/trunk/classes/externalinput.php
		 */
	}

	/**
	 * When retrieving data from sources different from the BsCore::getParam()
	 * method, use this interface to sanitize an array. For security reasons it
	 * is strongly recommended to use this method!
	 * @param array $array The array that has to be sanitized.
	 * @param array $default A default array that gets returned, if the
	 * submitted array is not valid or does not match the requested BsPARAMTYPE.
	 * @param BsPARAMTYPE $options Sets the type of the expected return value.
	 * This information is used for proper sanitizing.
	 * @return array Depending on the BsPARAMTYPE sumbitted in $options the
	 * sanitized $array or in case of invalidity of $array, the $default
	 * array.
	 */
	public static function sanitizeArrayEntry($array, $key, $default = null, $options = null) {
		// TODO MRG20100725: Sollte $default nicht auch durch den sanitizer?
		if (!is_array($array)) {
			return $default;
		}
		if (!isset($array[$key])) {
			return $default;
		}
		return self::sanitize($array[$key], $default, $options);
	}

	public static function doInitialise() {
		self::$oInstance = new BsCore();

		if ( !defined( 'DO_MAINTENANCE' ) ) {
			BsConfig::loadSettings();
		}

		BSNotifications::init();

		$factory = MediaWikiServices::getInstance()->getService(
			'BSExtensionFactory'
		);
		$factory->getExtensions();

		global $wgHooks;
		$wgHooks['ArticleAfterFetchContentObject'][] = array( self::$oInstance, 'behaviorSwitches' );
		$wgHooks['ParserBeforeStrip'][] = array( self::$oInstance, 'hideBehaviorSwitches' );
		$wgHooks['ParserBeforeTidy'][] = array( self::$oInstance, 'recoverBehaviorSwitches' );

		if( !isset( $wgHooks['EditPage::showEditForm:initial'] ) ) {
			$wgHooks['EditPage::showEditForm:initial'] = [];
		}
		array_unshift(
			$wgHooks['EditPage::showEditForm:initial'],
			array( self::$oInstance, 'lastChanceBehaviorSwitches' )
		);

		//TODO: This does not seem to be the right place for stuff like this.
		global $wgFileExtensions;
		$config = MediaWiki\MediaWikiServices::getInstance()
			->getConfigFactory()->makeConfig( 'bsg' );
		$aFileExtensions  = $config->get( 'FileExtensions' );
		$aImageExtensions = $config->get( 'ImageExtensions' );
		$wgFileExtensions = array_merge( $aFileExtensions, $aImageExtensions );
		$wgFileExtensions = array_values( array_unique( $wgFileExtensions ) );

		//Initialize and apply role
		if( $config->get( 'EnableRoleSystem' ) ) {
			$roleManager = \MediaWiki\MediaWikiServices::getInstance()->getService(
				'BSRoleManager'
			);
			$roleManager->applyRoles();
		}
	}

	/* Returns the filesystem path of the core installation
	 * @return String Filesystempath to the core installation
	 */

	public static function getFileSystemPath() {
		return BSROOTDIR;
	}

	/**
	 * Parses WikiText into HTML
	 * @param string $sText WikiText
	 * @param Title $oTitle
	 * @param bool $nocache DISFUNCTIONAL and therefore DEPRECATED. There is no chaching anyway.
	 * @param bool $numberheadings
	 * @return string The HTML result
	 */
	public function parseWikiText( $sText, $oTitle, $nocache = false, $numberheadings = null ) {
		if ( !self::$oLocalParser ) self::$oLocalParser = new Parser();
		if ( !self::$oLocalParserOptions ) self::$oLocalParserOptions = new ParserOptions();

		if ( $numberheadings === false ) {
			self::$oLocalParserOptions->setNumberHeadings( false );
		} elseif ( $numberheadings === true ) {
			self::$oLocalParserOptions->setNumberHeadings( true );
		}

		// TODO MRG20110707: Check it this cannot be unified

		if ( $nocache ) {
			wfDebug( __METHOD__.': Use of $nocache parameter is deprecated. There is no caching anyway.' );
		}

		if ( !( $oTitle instanceof Title ) ) return '';

		$output = self::$oLocalParser->parse( $sText, $oTitle, self::$oLocalParserOptions, true )->getText();

		return $output;
	}

	/**
	 * @deprecated since version 2.23.2
	 * @param User $oUser
	 * @return String
	 */
	public static function getUserDisplayName( $oUser = null ) {
		return BsUserHelper::getUserDisplayName($oUser);
	}

		/**
	 * Determines the request URI for Apache and IIS
	 *
	 * @param bool $getUrlEncoded set to true to get URI url encoded
	 * @return string the requested URI
	 */
	public static function getRequestURI($getUrlEncoded = false) {
		if (self::$prRequestUri === null) {
			$requestUri = '';
			if ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) { // check this first so IIS will catch
				$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
			} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$requestUri = $_SERVER['REQUEST_URI'];
			} elseif ( isset( $_SERVER['ORIG_PATH_INFO'] ) ) { // IIS 5.0, PHP as CGI
				$requestUri = $_SERVER['ORIG_PATH_INFO'];
				if ( !empty( $_SERVER['QUERY_STRING'] ) ) {
					$requestUri .= '?' . $_SERVER['QUERY_STRING'];
				}
			}
			self::$prRequestUri = $requestUri;
			self::$prUrlIsEncoded = ( urldecode( self::$prRequestUri ) != self::$prRequestUri );
		}
		if ( $getUrlEncoded ) {
			return ( self::$prUrlIsEncoded ? self::$prRequestUri : urlencode( self::$prRequestUri ) );
		}
		return ( self::$prUrlIsEncoded ? urldecode( self::$prRequestUri ) : self::$prRequestUri );
	}

	// TODO MRG (09.12.10 11:21): Habe silent im Standard auf true gesetzt. Echo ist ohnehin nicht gut.
	/**
	 *
	 * @param string $sPermission
	 * @param string $sI18NInstanceKey
	 * @param string $sI18NMessageKey
	 * @param bool $bSilent
	 * @return bool
	 */
	public static function checkAccessAdmission( $sPermission = 'read', $sI18NInstanceKey = 'BlueSpice', $sI18NMessageKey = 'not_allowed', $bSilent = true ) {
		// TODO MRG28072010: isAllowed prüft nicht gegen die Artikel. D.H. die Rechte sind nicht per Namespace überprüfbar
		$oUser = self::loadCurrentUser();
		if ( $oUser->isAllowed( $sPermission ) ) {
			return true;
		}
		if ( !$bSilent ) echo wfMessage( 'bs-' . $sI18NMessageKey )->plain();

		return false;
	}

	public static function loadCurrentUser() {
		/* Load current user */
		global $wgUser;

		if ( !$wgUser || is_null( $wgUser->mId ) ) {

			if ( !is_null( self::$prCurrentUser ) ) {
				return self::$prCurrentUser;
			}

			self::$prCurrentUser = User::newFromSession();
			self::$prCurrentUser->load();
			return self::$prCurrentUser;
		}

		return $wgUser;
		// Used to bie like the following code. however, this did not take into account the __session-Cookie, and logged out users were still recognized.
		/* if( isset( $_SESSION['wsUserID'] ) ) {
		  self::$prCurrentUser = User::newFromId( $_SESSION['wsUserID'] ); // object created but not loaded from DB
		  self::$prCurrentUser->loadFromId(); // get from DB or MemCache
		  return self::$prCurrentUser;
		  }
		  return new User(); //anonymous
		 */
	}

	/**
	 * Creates a miniprofile for a user. It consists if the useres profile image
	 * and links to his userpage. In future versions it should also have a
	 * little menu with his mail adress, and other profile information.
	 * @param User $oUser The requested MediaWiki User object
	 * @param array $aParams The settings array for the mini profile view object
	 * @return ViewUserMiniProfile A view with the users mini profile
	 */
	public function getUserMiniProfile( $oUser, $aParams = array() ) {
		$sParamsHash = md5( serialize( $aParams ) );
		$sViewHash = $oUser->getName() . $sParamsHash;

		if ( isset( self::$aUserMiniProfiles[$sViewHash] ) ) {
			return self::$aUserMiniProfiles[$sViewHash];
		}

		$oUserMiniProfileView = new ViewUserMiniProfile();
		$oUserMiniProfileView->setOptions( $aParams );
		$oUserMiniProfileView->setOption( 'user', $oUser );

		Hooks::run( 'BSCoreGetUserMiniProfileBeforeInit', array( &$oUserMiniProfileView, &$oUser, &$aParams ) );

		$oUserMiniProfileView->init();

		self::$aUserMiniProfiles[$sViewHash] = $oUserMiniProfileView;

		return $oUserMiniProfileView;
	}

	/**
	 * Registers a permission with the MediaWiki Framework.
	 * object for proper internationalisation of your permission. Every
	 * permission is granted automatically to the user group 'sysop'. You can
	 * specify additional groups through the third parameter.
	 *
	 * @deprecated since version 3.0 - use BSPermissionRegistry instead
	 *
	 * @param String $sPermissionName I.e. 'myextension-dosomething'
	 * @param Array $aUserGroups User groups that get preinitialized with the new
	 * pemission. I.e. array( 'user', 'bureaucrats' )
	 * @param Array $aConfig set configs for permissions i.e. array('type'=>'global').
	 * The default here is ('type' = 'namespace')
	 * @return void
	 */
	public function registerPermission( $sPermissionName, $aUserGroups = array(), $aConfig = array() ) {
		global $wgGroupPermissions, $wgAvailableRights, $bsgPermissionConfig;
		$wgGroupPermissions['sysop'][$sPermissionName] = true;
		if(!isset($bsgPermissionConfig[$sPermissionName])){
			if ( isset( $aConfig ) ) {
				$bsgPermissionConfig[$sPermissionName] = $aConfig;
			} else {
				$bsgPermissionConfig[$sPermissionName] = array( 'type' => 'namespace' );
			}
		}
		foreach ( $aUserGroups as $sGroup ) {
			if ( !isset( $wgGroupPermissions[$sGroup][$sPermissionName] ) ) {
				$wgGroupPermissions[$sGroup][$sPermissionName] = true;
			}
		}
		$wgAvailableRights[] = $sPermissionName;

		wfDeprecated( __METHOD__, '3.0.0' );
		return true;
	}

	/**
	 * Register a callback for a MagicWord
	 * @param string $sMagicWord The MagicWord in upper case and without
	 * surrounding double underscores. OR: if $callback == null this may be a
	 * lower case identifier that gets written to the page_props table by the
	 * parser.
	 * @param callable $aCallback or null to use MediaWiki page_props mechanism
	 */
	public function registerBehaviorSwitch( $sMagicWord, $aCallback = null ) {
		if ( is_callable( $aCallback ) ) {
			$this->aBehaviorSwitches[$sMagicWord] = $aCallback;
		} elseif ( !in_array( $sMagicWord, MagicWord::$mDoubleUnderscoreIDs ) ) {
			MagicWord::$mDoubleUnderscoreIDs[] = $sMagicWord;
		}
	}

	/**
	 * Hook-handler for "ArticleAfterFetchContentObject"
	 * @param WikiPage $article
	 * @param Content $content
	 * @return boolean Always true to keep hook running
	 */
	public function behaviorSwitches( &$article, &$content ) {
		if ( !isset( $this->aBehaviorSwitches ) ) {
			return true;
		}

		$sNowikistripped = preg_replace( "#<nowiki>.*?<\/nowiki>#si", "", ContentHandler::getContentText( $content ) );
		foreach ( $this->aBehaviorSwitches as $sSwitch => $sCallback ) {
			if ( strstr( $sNowikistripped, '__' . $sSwitch . '__' ) ) {
				call_user_func( $sCallback );
			}
		}
		return true;
	}

	/**
	 * Hook-handler for "ParserBeforeStrip"
	 * @param Parser $parser
	 * @param string $text
	 * @return boolean Always true to keep hook running
	 * @deprecated since version 2.22
	 */
	public function hideBehaviorSwitches( &$parser, &$text ) {
		if ( !isset( $this->aBehaviorSwitches ) ) {
			return true;
		}

		$sNowikistripped = preg_replace( "#<nowiki>.*?<\/nowiki>#si", "", $text );
		foreach ( $this->aBehaviorSwitches as $sSwitch => $sCallback ) {
			if ( strstr( $sNowikistripped, '__' . $sSwitch . '__' ) ) {
				call_user_func( $sCallback );
			}

			$text = preg_replace( "/(<nowiki>.*?)__{$sSwitch}__(.*?<\/nowiki>)/i", "$1@@{$sSwitch}@@$2", $text );
		}
		return true;
	}

	/**
	 * Hook-handler for "ParserBeforeTidy"
	 * @param Parser $parser
	 * @param string $text
	 * @return boolean Always true to keep hook running
	 * @deprecated since version 2.22
	 */
	public function recoverBehaviorSwitches( &$parser, &$text ) {
		if ( !isset( $this->aBehaviorSwitches ) ) {
			return true;
		}

		foreach ( $this->aBehaviorSwitches as $sSwitch => $sCallback ) {
			$text = str_replace( '__' . $sSwitch . '__', "", $text );
			$text = preg_replace( "/@@" . $sSwitch . "@@/", '__' . $sSwitch . '__', $text );
		}
		return true;
	}

	/**
	 * Hook-handler for "EditPage::showEditForm:initial"
	 * Needed for edit and sumbit (preview) mode
	 * @param EditPage $editPage
	 * @return boolean Always true to keep hook running
	 */
	public function lastChanceBehaviorSwitches( $editPage ) {
		// TODO SW(05.01.12 15:39): Profiling
		$sContent = BsPageContentProvider::getInstance()->getContentFromTitle( RequestContext::getMain()->getTitle() );
		if ( !isset( $this->aBehaviorSwitches ) ) return true;

		$sNowikistripped = preg_replace( "#<nowiki>.*?<\/nowiki>#si", "", $sContent );
		foreach ( $this->aBehaviorSwitches as $sSwitch => $sCallback ) {
			if ( strstr( $sNowikistripped, '__' . $sSwitch . '__' ) ) {
				call_user_func( $sCallback );
			}
		}
		// TODO: This note should be displayed when the editor is deactivated
		//$editPage->editFormTextTop = "Der Editor wurde deaktiviert <br/>";
		if ( isset( $editPage->textbox1 ) ) {
			foreach ( $this->aBehaviorSwitches as $sSwitch => $sCallback ) {
				$sNowikistripped = preg_replace( "#<nowiki>.*?<\/nowiki>#si", "", $editPage->textbox1 );
				if ( strstr( $sNowikistripped, '__' . $sSwitch . '__' ) ) {
					call_user_func( $sCallback );
				}
			}
		}
		return true;
	}

	/**
	 * Make the page being parsed have a dependency on $page via the templatelinks table.
	 * http://www.mediawiki.org/wiki/Manual:Tag_extensions#Regenerating_the_page_when_another_page_is_edited
	 * @param Parser $oParser
	 * @param String $sTitle
	 */
	public static function addTemplateLinkDependencyByText($oParser, $sTitle) {
		$oTitle = Title::newFromText( $sTitle );
		static::addTemplateLinkDependency($oParser, $oTitle);
	}

	/**
	 * Make the page being parsed have a dependency on $page via the templatelinks table.
	 * http://www.mediawiki.org/wiki/Manual:Tag_extensions#Regenerating_the_page_when_another_page_is_edited
	 * @param Parser $oParser
	 * @param Title $oTitle
	 */
	public static function addTemplateLinkDependency( $oParser, $oTitle )  {
		$oRevision = Revision::newFromTitle( $oTitle );
		$iPageId = $oRevision ? $oRevision->getPage() : 0;
		$iRevId  = $oRevision ? $oRevision->getId()   : 0;

		$oParser->getOutput()->addTemplate(
			$oTitle,
			$iPageId,
			$iRevId
		); // Register dependency in templatelinks
	}

	/**
	 * Returns the MediaWiki include path variable
	 * @global String $IP MediaWiki include path variable
	 * @return String MediaWiki include path variable
	 */
	public static function getMediaWikiIncludePath() {
		global $IP;
		return str_replace('\\', '/', $IP);
	}

	/**
	 * Returns the filesystempath to the webroot directory in which MediaWiki is installed.
	 * @global String $wgScriptPath The relative path from the webroot for hyperlinks.
	 * @return String Webroot directory in which MediaWiki is installed
	 */
	public static function getMediaWikiWebrootPath() {
		global $wgScriptPath;
		return str_replace($wgScriptPath, '', self::getMediaWikiIncludePath());
	}
}
