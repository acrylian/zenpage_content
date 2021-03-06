<?php
/**
 * A Zenphoto plugin to provide various content macros for Zenpage CMS items.
 *
 * For content/extra content of a Zenpage page or news article:
 * [PAGECONTENT <titlelink> <publish true|false> <title true|false> <header string>]
 * [PAGEEXTRACONTENT <titlelink> <publish true|false>]
 * [NEWSCONTENT <titlelink> <publish true|false>  <title true|false> <header string>]
 * [NEWSEXTRACONTENT <titlelink> <publish true|false>]
 *
 * Excerpts of the direct subpages (1 level) of the current Zenpage page:
 * [SUBPAGES <headline> <excerpt length> <readmore text> <shortenindicator text>]
 * All are optional, set to empty quotes ('') if you only want to set the last one for example.
 * This generates the following html:
 * <div class='pageexcerpt'>
 * 	<h3>page title</h3>
 * 	<p>page content excerpt</p>
 * 	<p>read more</p>
 * </div>
 *
 * @author Malte Müller (acrylian) <info@maltem.de> with from inspiration by Vincent Bourganel (vincent3569)
 * @copyright 2014 Malte Müller
 * @license GPL v3 or later
 * @package plugins
 * @subpackage misc
 */

$plugin_is_filter = 9|THEME_PLUGIN|ADMIN_PLUGIN;
$plugin_description = gettext('A Zenphoto plugin to provide various content macros for Zenpage CMS items.');
$plugin_author = 'Malte Müller (acrylian) with from inspiration by Vincent Bourganel (vincent3569)';
$plugin_version = '1.0.1';

zp_register_filter('content_macro', 'zenpageMacros::zenpage_macros');

class zenpageMacros {

	function __construct() {
	}

/* Gets the content of a page
 * @param string $titlelink The item to get
 * @param bool $published If published or not
 * @param bool $title if page title have to be added or not
 * @param string $header what <hx> tags have to be used for the title
 */
	static function getPageContent($titlelink, $published = true, $title = true, $header) {
		return self::getZenpageContent($titlelink, $published, 'content', 'page', $title, $header);
	}

/* Gets the extra content of a page
 * @param string $titlelink The item to get
 * @param bool $published If published or not
 */
	static function getPageExtraContent($titlelink, $published = true) {
		return self::getZenpageContent($titlelink, $published, 'extracontent', 'page');
	}

/* Gets the content of a news
 * @param string $titlelink The item to get
 * @param bool $published If published or not
 * @param bool $title if news title have to be added or not
 * @param string $header what <hx> tags have to be used for the title
 */
	static function getArticleContent($titlelink, $published = true, $title = true, $header) {
		return self::getZenpageContent($titlelink, $published, 'content', 'news', $title, $header);
	}

/* Gets the extra content of a news
 * @param string $titlelink The item to get
 * @param bool $published If published or not
 */
	static function getArticleExtraContent($titlelink, $published = true) {
		return self::getZenpageContent($titlelink, $published, 'extracontent', 'news');
	}

/* getZenpageContent main function 
 */
	static function getZenpageContent($titlelink, $published = true, $contenttype = 'content', $itemtype = 'page', $title = true, $headline = 'h4') {
		if(!empty($titlelink)) {
			switch($itemtype) {
				case 'page':
					$obj = new ZenpagePage($titlelink);
					break;
				case 'news':
					$obj = new ZenpageNews($titlelink);
					break;
			}
			if (($obj->getShow()) || ((!$obj->getShow()) && (!$published))) {
				$html = '';
				switch($contenttype) {
					case 'content':
						if ($title) {
							$html .= '<' . $headline . '>' . html_encode($obj->getTitle()) . '</' . $headline . '>';
						}
						$html .= html_encodeTagged($obj->getContent());
						return $html;
						break;
					case 'extracontent':
						$html .= html_encodeTagged($obj->getExtraContent());
						return $html;
						break;
				} 
			}
		}
	}

/* Gets the html setup for the subpage list
 * @param string $header What to use as headline (h1 - h6)
 * @param string $excerptlength The length of the page content, if nothing specifically set, the plugin option value for 'news article text length' is used
 * @param string $readmore The text for the link to the full page. If empty the read more setting from the options is used.
 * @param string $shortenindicator The optional placeholder that indicates that the content is shortened, if this is not set the plugin option "news article text shorten indicator" is used.
 * @return string
 */
	static function getSubPagesHTML($header = 'h3', $excerptlength = NULL, $readmore = NULL, $shortenindicator = NULL) {
		global $_zp_current_zenpage_page;
		$html = '';
		if (empty($readmore)) {
			$readmore = get_language_string(ZP_READ_MORE);
		}
		$pages = $_zp_current_zenpage_page->getPages();
		$subcount = 0;
		if (empty($excerptlength)) {
			$excerptlength = ZP_SHORTEN_LENGTH;
		}
		if(in_array($header, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'))) {
			$headline = $header;
		} else {
			$headline = 'h3';
		}
		foreach ($pages as $page) {
			$pageobj = new ZenpagePage($page['titlelink']);
			if ($pageobj->getParentID() == $_zp_current_zenpage_page->getID()) {
				$subcount++;
				$pagetitle = html_encode($pageobj->getTitle());
				$html .= '<div class="pageexcerpt">';
				$html .= '<' . $headline . '><a href="' . html_encode(getPageURL($pageobj->getTitlelink())) . '" title="' . strip_tags($pagetitle) . '">' . $pagetitle . '</a></' . $headline . '>';
				$pagecontent = $pageobj->getContent();
				if ($pageobj->checkAccess()) {
					$html .= getContentShorten($pagecontent, $excerptlength, $shortenindicator, $readmore, getPageURL($pageobj->getTitlelink()));
				} else {
					$html .= '<p><em>' . gettext('This page is password protected') . '</em></p>';
				}
				$html .= '</div>';
			}
		}
		return $html;
	}

/*
 * macro definition
 * @param array $macros
 * return array
 */
	static function zenpage_macros($macros) {
		$macros['PAGECONTENT'] = array(
					'class'=>'function',
					'params'=> array('string', 'bool*', 'bool*', 'string*'),
					'value'=>'zenpageMacros::getPageContent',
					'owner'=>'zenpageMacros',
					'desc'=>gettext('Prints the content of the page with titlelink (%1) being published true|false (%2) with title true|false (%3) and headline h1-h6 to use (%4).')
				);
		$macros['PAGEEXTRACONTENT'] = array(
					'class'=>'function',
					'params'=> array('string', 'bool*'),
					'value'=>'zenpageMacros::getPageExtraContent',
					'owner'=>'zenpageMacros',
					'desc'=>gettext('Prints the extra content of the page with titlelink (%1) being published true|false (%2).')
				);
		$macros['NEWSCONTENT'] = array(
					'class'=>'function',
					'params'=> array('string', 'bool*', 'bool*', 'string*'),
					'value'=>'zenpageMacros::getArticleContent',
					'owner'=>'zenpageMacros',
					'desc'=>gettext('Prints the content of the news article with titlelink (%1) being published true|false (%2) with title true|false (%3) and headline h1-h6 to use (%4).')
				);
		$macros['NEWSEXTRACONTENT'] = array(
					'class'=>'function',
					'params'=> array('string', 'bool*'),
					'value'=>'zenpageMacros::getArticleExtraContent',
					'owner'=>'zenpageMacros',
					'desc'=>gettext('Prints the extra content of the news article with titlelink (%1) being published true|false (%2).')
				);
		$macros['SUBPAGES'] = array(
					'class'=>'function',
					'params'=> array('string*', 'string*', 'string*', 'string*'), 
					'value'=>'zenpageMacros::getSubPagesHTML',
					'owner'=>'zenpageMacros',
					'desc'=>gettext('Prints subpages of a Zenpage: Headline h1-h6 to use (%1), excerpt lenght (%2), readmore text (%3), shorten indicator text (%4). All optional, leave empty with empty quotes of you only need to set the last ones')
				);
		return $macros;
	}
} // class end