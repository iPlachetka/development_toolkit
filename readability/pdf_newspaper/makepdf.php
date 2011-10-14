<?php
// RSS to PDF
// Author: Keyvan Minoukadeh
// License: AGPLv3
// Version: 2.2
// Date: 2010-07-07
// How to use: request this file passing it your feed in the querystring: makepdf.php?feed=http://mysite.org
// To include images in the PDF, add images=true to the querystring: makepdf.php?feed=http://mysite.org&images=true
// For other options, edit config.php

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/     

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
@set_time_limit(120);

// Include OPML parser for OPML support
require_once('libraries/opml/iam_opml_parser.php');
// Include SimplePie for RSS/Atom support
require_once('libraries/simplepie/simplepie.class.php');
require_once('SimplePie_Chronological.php');
// Include HTML Purifier to clean up and filter HTML input
require_once('libraries/htmlpurifier/library/HTMLPurifier.auto.php');
// Include SmartyPants to make pretty, curly quotes
//require_once('libraries/smartypants/smartypants.php');
require_once('libraries/php-typography/php-typography.php');
// Include TCPDF to turn all this into a PDF
require_once('tcpdf_config.php');
require_once('libraries/tcpdf/config/lang/eng.php');
require_once('libraries/tcpdf/tcpdf.php');
// Include NewspaperPDF to let us add stories to our PDF easily
require_once('NewspaperPDF.php');
// Include PHP Hooks to allow plugins to extend functionality (see plugin/ folder)
require_once('libraries/phphooks/phphooks.class.php');
require_once('libraries/phphooks/functions.php');

/////////////////////////////////////
// Initialise hooks and load plugins
/////////////////////////////////////
$hooks = new phphooks();
$hooks->active_plugins = null;
$hooks->set_hooks(array('filter_purified_html_string', 'filter_purified_html_dom', 'filter_image_elements'));
$hooks->load_plugins('plugins/');

class HTMLPurifier_AttrTransform_FilterImageElements extends HTMLPurifier_AttrTransform
{
    public function transform($attr, $config, $context) {
		global $hooks;
		if ($hooks->hook_exist('filter_image_elements')) {
			$result = $hooks->filter_hook('filter_image_elements', $attr);
			if ($result == null) {
				// confiscating the attribute forces HTML Purifier to remove the entire element
				// (the src attribute is required)
				$this->confiscateAttr($attr, 'src');
			} else {
				$attr = $result;
			}
		}
		$this->confiscateAttr($attr, 'width');
		$this->confiscateAttr($attr, 'height');
        return $attr;
    }
}

////////////////////////////////
// Load config file if it exists
////////////////////////////////
// the config values below should be set in config.php (rename config-sample.php if config.php doesn't exist).
// the values below will only be used if config.php doesn't exist.
$options->allow_full_text_option = false;
$options->full_text_service_url = '';
$options->full_text_service_url_with_key = '';
$options->api_keys = array();
if (file_exists(dirname(__FILE__).'/config.php')) {
	require_once(dirname(__FILE__).'/config.php');
}

////////////////////////////////
// Check for feed URL
////////////////////////////////
if (!isset($_GET['feed'])) { 
	die('No URL supplied'); 
}
$url = $_GET['feed'];
if (!preg_match('!^https?://.+!i', $url)) {
	$url = 'http://'.$url;
}
$valid_url = filter_var($url, FILTER_VALIDATE_URL);
if ($valid_url !== false && $valid_url !== null && preg_match('!^https?://!', $valid_url)) {
	$url = filter_var($url, FILTER_SANITIZE_URL);
} else {
	die('Invalid URL supplied');
}

////////////////////////////////
// Redirect to alternative URL?
////////////////////////////////
if (isset($_GET['api_key']) && array_search($_GET['api_key'], $options->api_keys) !== false) {
	$key = array_search($_GET['api_key'], $options->api_keys);
	$redirect = 'makepdf.php?feed='.urlencode($url);
	$redirect .= '&key='.urlencode($key);
	$redirect .= '&hash='.urlencode(sha1($_GET['api_key'].$url));
	if (isset($_GET['title'])) $redirect .= '&title='.urlencode($_GET['title']);
	if (isset($_GET['order'])) $redirect .= '&order='.urlencode($_GET['order']);
	if (isset($_GET['images'])) $redirect .= '&images='.urlencode($_GET['images']);
	if (isset($_GET['fulltext'])) $redirect .= '&fulltext='.urlencode($_GET['fulltext']);
	if (isset($_GET['sub'])) $redirect .= '&sub='.urlencode($_GET['sub']);
	if (isset($_GET['mode'])) $redirect .= '&mode='.urlencode($_GET['mode']);
	if (isset($_GET['date'])) $redirect .= '&date='.urlencode($_GET['date']);
	header("Location: $redirect");
	exit;
}

///////////////////////////////////////////////
// Check if valid key supplied
///////////////////////////////////////////////
$valid_key = false;
if (isset($_GET['key']) && isset($_GET['hash']) && isset($options->api_keys[(int)$_GET['key']])) {
	$valid_key = ($_GET['hash'] == sha1($options->api_keys[(int)$_GET['key']].$_GET['feed']));
}

////////////////////////////////
// Check API version
////////////////////////////////
if (isset($_GET['v']) && $_GET['v'] == '2') {
	$version = 2;
} else {
	$version = 1;
}

////////////////////////////////
// Check if full-text requested
////////////////////////////////
if ($options->allow_full_text_option) {
	if (isset($_GET['fulltext']) && $_GET['fulltext'] == 'true') {
		if ($valid_key) {
			$url = $options->full_text_service_url_with_key . urlencode($url);
		} else {
			$url = $options->full_text_service_url . urlencode($url);
		}
	}
}

////////////////////////////////
// Check for title
////////////////////////////////
if (isset($_GET['title']) && strlen(trim($_GET['title'])) < 100) {
	$title = trim($_GET['title']);
	if (get_magic_quotes_gpc()) $title = stripslashes($title);
} else {
	$title = '';
}

////////////////////////////////
// Check for subheading
////////////////////////////////
if ($valid_key && isset($_GET['sub']) && trim($_GET['sub']) != '' && strlen(trim($_GET['sub'])) < 100) {
	$subheading = '<span style="color: #666">'.htmlspecialchars(trim($_GET['sub'])).'</span>';
	if (get_magic_quotes_gpc()) $subheading = stripslashes($subheading);
} else {
	$subheading = '<a href="http://fivefilters.org" style="color: #666">created using fivefilters.org</a>';
}

////////////////////////////////
// Check item ordering
////////////////////////////////
if (isset($_GET['order']) && $_GET['order'] == 'asc') {
	$order = 'asc';
} else {
	$order = 'desc';
}

////////////////////////////////
// Show date?
////////////////////////////////
$show_date = true;
if ($version >= 2 && (!isset($_GET['date']) || $_GET['date'] == 'false')) {
	$show_date = false;
}

////////////////////////////////
// Check for date range
////////////////////////////////
if (isset($_GET['date_start'])) {
	$date_start = strtotime($_GET['date_start']);
}
if (isset($_GET['date_end'])) {
	$date_end = strtotime($_GET['date_end']);
}

////////////////////////////////
// Check if images should be downloaded
////////////////////////////////
if (isset($_GET['images']) && $_GET['images'] == 'true') {
	$get_images = true;
} else {
	$get_images = false;
}

////////////////////////////////
// Check mode
////////////////////////////////
if (isset($_GET['mode']) && $_GET['mode'] == 'single-story') {
	$mode = 'single-story';
	$title = '';
	$order = 'desc';
} else {
	$mode = 'multi-story';
}

//////////////////////////////////
// Max string length (total feed)
//////////////////////////////////
if ($get_images) {
	$max_strlen = ($valid_key ? 100000 : 30000);
} else {
	$max_strlen = ($valid_key ? 200000 : 100000);
}

//////////////////////////////////
// Check for cached copy
// (URL already indicates whether 
// fulltext is used or not)
//////////////////////////////////
if ($get_images || isset($date_start) || isset($date_end)) {
	$query_md5 = md5($get_images . @$date_start . @$date_end);
	$cache_file = 'cache/'.md5($url.$mode.$order.$title.$valid_key.$subheading.$show_date).'_'.$query_md5.'.pdf';
	unset($query_md5);
} else {
	$cache_file = 'cache/'.md5($url.$mode.$order.$title.$valid_key.$subheading.$show_date).'.pdf';
}
if (file_exists($cache_file)) {
	$cache_mtime = filemtime($cache_file);
	$diff = time() - $cache_mtime;
	$diff = $diff / 60;
	if ($diff < 20) { // cache created less than 20 minutes ago
		header('Content-Type: application/pdf');
		if (headers_sent()) die('Some data has already been output to browser, can\'t send PDF file');
		header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
		header('Pragma: public');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $cache_mtime).' GMT');	
		header('Content-Length: '.filesize($cache_file));
		header('Content-Disposition: inline; filename="news.pdf";');
		readfile($cache_file);
		exit;
	}
}

//////////////////////////////////
// Delete old cached copies
//////////////////////////////////
if (mt_rand(0, 100) < 9) {
	// delete files older than $expire_time minutes
	$expire_time = 20; 
	// find all files of the given file type
	foreach (glob('cache/*.pdf') as $filename) {
		// calculate file age in seconds
		$file_age = time() - @filemtime($filename);
		// is the file older than the given time span?
		if ($file_age > ($expire_time * 60)) {
			@unlink($filename);
		}
	}
	unset($expire_time, $filename, $file_age);
}

////////////////////////////////
// Get RSS/Atom feed
////////////////////////////////
if ($order == 'asc') {
	$feed = new SimplePie_Chronological();
} else {
	$feed = new SimplePie();
}
$feed->set_feed_url($url);
$feed->set_timeout(20);
$feed->enable_cache(false);
$feed->set_stupidly_fast(true);
$feed->enable_order_by_date(true);
$feed->set_url_replacements(array());
$result = $feed->init();
//$feed->handle_content_type();
//$feed->get_title();
if ($result && (!is_array($feed->data) || count($feed->data) == 0)) {
	die('Sorry, no feed items found');
}

//////////////////////////////////////////
// Get feeds from OPML (if URL is not feed)
//////////////////////////////////////////
if (!$result) {
	$opml = new IAM_OPML_Parser();
	$feeds_array = $opml->getFeeds($url);
	print_r($url);  
	if (!is_array($feeds_array) || count($feeds_array) == 0) {
		die('URL must point to a feed or OPML of feeds');
	}
	$feed_urls = array();
	foreach($feeds_array as $feed_item) {
		if (trim($feed_item['feeds']) != '') {
			$feed_urls[] = trim($feed_item['feeds']);
		}
		// limit to 10 URLs in OPML
		if (count($feed_urls) >= 10) break;
	}
	// setup SimplePie again
	if ($order == 'asc') {
		$feed = new SimplePie_Chronological();
	} else {
		$feed = new SimplePie();
	}
	$feed->set_feed_url($feed_urls);
	//$feed->force_feed(true);
	$feed->set_timeout(120);
	$feed->enable_cache(false);
	$feed->set_stupidly_fast(true);
	$feed->enable_order_by_date(true);
	$feed->set_url_replacements(array());
	$result = $feed->init();
	//$feed->handle_content_type();
	//if ($feed->error()) echo $feed->error();exit;
	if (!$result) {
		die('Sorry, no feed items found');
	}
}

/////////////////////////////////////////////////
// Create new PDF document (LETTER/A4)
/////////////////////////////////////////////////
$pdf = new NewspaperPDF('P', 'mm', 'A4', true, 'UTF-8', false);
//$pdf = new NewspaperPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setHooks($hooks);

// set document information
$pdf->SetCreator('http://fivefilters.org/pdf-newspaper/ (free software)');
$pdf->SetAuthor('fivefilters.org');
if ($title != '') {
	$pdf->SetTitle($title);
} else {
	$pdf->SetTitle('Five Filters');
}
//$pdf->SetSubject('Non-corporate news');
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');


//$pdf->setPrintHeader(false); 
//$pdf->setPrintFooter(false); 

// set cover image
//$pdf->setCoverImage('images/cover.jpg');

// set default header data
if ($mode == 'single-story') {
	$pdf->SetHeaderData('', 0, '', '<span style="color: #666">'.date('j F, Y').' | </span>'.$subheading);
} elseif ($title == '') {
	$pdf->SetHeaderData('images/five_filters.jpg', 85, '', '');
} else {
	$pdf->SetHeaderData('', 0, $title, '<span style="color: #666">'.date('j F, Y').' | </span>'.$subheading);
}
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array('linlibertinecaps', '', 44.5));
//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array('helveticab', 'B', 9));
//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

//set margins
$pdf->SetMargins(14, 16, 14, true);
//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(10);
//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(14);
//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->setCellHeightRatio(1.5);

$pdf->SetFont('dejavuserifcondensed');

// dejavusans appears to have support for languages such as farsi
// see example 18 on this page: http://www.tecnick.com/public/code/cp_dpage.php?aiocp_dp=tcpdf_examples
//$pdf->SetFont('dejavusans');
//$pdf->SetFont('zarbold');

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);
//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
//$pdf->setImageScale(2);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  // 4

$pdf->SetDisplayMode('default', 'continuous');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set some language-dependent strings
$pdf->setLanguageArray($l); 

// Black links with no underlining
$pdf->setHtmlLinksStyle(array(0, 0, 0), '');

// Define vertical spacing for various HTML elements
$tagvs = array(
			'blockquote' => array(0 => array('h' => '', 'n' => 0), 1 => array('h' => '', 'n' => 0)),
			'img' => array(0 => array('h' => '', 'n' => 0), 1 => array('h' => '', 'n' => 0)),
			'p' => array(0 => array('h' => '', 'n' => 3.3), 1 => array('h' => '', 'n' => 3.3)),
			'h1' => array(0 => array('h' => '', 'n' => 1), 1 => array('h' => '', 'n' => 1.5)),
			'h2' => array(0 => array('h' => '', 'n' => 2), 1 => array('h' => '', 'n' => 1)),
			'h3' => array(0 => array('h' => '', 'n' => 1), 1 => array('h' => '', 'n' => 1)),
			'h4' => array(0 => array('h' => '', 'n' => 1), 1 => array('h' => '', 'n' => 1)),
			'h5' => array(0 => array('h' => '', 'n' => 1), 1 => array('h' => '', 'n' => 1)),
			'h6' => array(0 => array('h' => '', 'n' => 1), 1 => array('h' => '', 'n' => 1)),	
			'ul' => array(0 => array('h' => '', 'n' => 0), 1 => array('h' => '', 'n' => 1.5)),
			'ol' => array(0 => array('h' => '', 'n' => 0), 1 => array('h' => '', 'n' => 1.5)),			
			'li' => array(0 => array('h' => '', 'n' => 5.5))
			);
$pdf->setHtmlVSpace($tagvs);

//$pdf->addPage();


///////////////////////////////////////
// Set up HTML Purifier, HTML Tidy
///////////////////////////////////////
$purifier = new HTMLPurifier();

// do tidy stuff, see http://tidy.sourceforge.net/docs/quickref.html
$tidy_config = array(
	 'clean' => true,
	 'output-xhtml' => true,
	 'logical-emphasis' => true,
	 'show-body-only' => true,
	 'wrap' => 0,
	 'drop-empty-paras' => true,
	 'drop-proprietary-attributes' => true,
	 'enclose-text' => true,
	 'enclose-block-text' => true,
	 'merge-divs' => true,
	 'merge-spans' => true,
	 'char-encoding' => 'utf8'
);

////////////////////////////////////////////
// Loop through feed items
////////////////////////////////////////////
if ($mode == 'single-story') {
	$items = $feed->get_items(0, 1);
} else {
	$items = $feed->get_items();
}
$strlen = 0;
$typo = new phpTypography();
$typo->set_url_wrap(true);
$typo->set_dewidow(true);
$typo->set_style_caps(false);
$typo->set_style_numbers(false);
$typo->set_style_initial_quotes(false);
$typo->set_style_ampersands(false);
$typo->set_dash_spacing(false);
$typo->set_smart_ellipses(true);
$typo->set_hyphenation(false);
//$typo->set_hyphenation_language("en-GB");
$typo->set_smart_dashes(true);
$typo->set_smart_fractions(false);
//$typo->set_smart_ordinal_suffix(false);
foreach ($items as $item) {  
	$author = $item->get_author();
	// skip items which fall outside date range
	if (isset($date_start) && (int)$item->get_date('U') < $date_start) continue;
	if (isset($date_end) && (int)$item->get_date('U') > $date_end) continue;
	
	$config = HTMLPurifier_Config::createDefault();
	//$config->set('Core.LexerImpl', 'DirectLex');
	// these are the HTML elements/attributes that will be preserved
	if ($get_images) {
		$config->set('HTML.Allowed', 'div,p,b,strong,em,a[href],i,ul,li,ol,blockquote,br,h1,h2,h3,h4,h5,h6,code,pre,sub,sup,del,img[src|width|height]');
	} else {
		$config->set('HTML.Allowed', 'div,p,b,strong,em,a[href],i,ul,li,ol,blockquote,br,h1,h2,h3,h4,h5,h6,code,pre,sub,sup,del');
	}
	// Attempt to autoparagraph when 2 linebreaks are detected -- we use feature after we run HTML through Tidy and replace double <br>s with linebreaks (\n\n)
	$config->set('AutoFormat.AutoParagraph', true);
	// Remove empty elements - TCPDF still applies padding/vertical spacing rules to empty elements
	$config->set('AutoFormat.RemoveEmpty', true);
	// HTML Purifier caching
	// to disable caching, uncomment line below
	//$config->set('Cache.DefinitionImpl', null);
	// cache path
	$config->set('Cache.SerializerPath', dirname(__FILE__).'/cache');
	//$config->set('Output.TidyFormat', false);
	//$config->set('HTML.TidyLevel', 'heavy');
	$config->set('URI.Base', $item->get_permalink());
	$config->set('URI.MakeAbsolute', true);
	$config->set('HTML.DefinitionID', 'extra-transforms');
	$config->set('HTML.DefinitionRev', 1);
	$def = $config->getHTMLDefinition(true);
	// Change <div> elements to <p> elements - We don't want <div><p>Bla bla bla</p></div> (makes it easier for TCPDF)
	$def->info_tag_transform['div'] = new HTMLPurifier_TagTransform_Simple('p');
	// <h1> elements are treated as story headlines so we downgrade any that appear to <h2>
	// <h2> to <h6> elements are treated the same (made bold but kept the same size)
	$def->info_tag_transform['h1'] = new HTMLPurifier_TagTransform_Simple('h2');
	$def->info_tag_transform['h3'] = new HTMLPurifier_TagTransform_Simple('h2');
	$def->info_tag_transform['h4'] = new HTMLPurifier_TagTransform_Simple('h2');
	$def->info_tag_transform['h5'] = new HTMLPurifier_TagTransform_Simple('h2');
	$def->info_tag_transform['h6'] = new HTMLPurifier_TagTransform_Simple('h2');
	//$def->info_tag_transform['i'] = new HTMLPurifier_TagTransform_Simple('em');
	
	if ($get_images) {
		// Here we tell HTML Purifier to filter out image elements with
		// small image dimensions (width or height smaller than 5 pixels).
		// By removing them at this stage we avoid an extra HTTP call during
		// PDF creation to pull the image in. 
		// But if no size is specified in HTML, then the same rule is
		// applied after we've fetched the image via HTTP and determined its size.
		$_img = $def->addBlankElement('img');
		$_img->attr_transform_pre[] = new HTMLPurifier_AttrTransform_FilterImageElements();
		// make src a required attribute (ensures img elements are removed completely
		// if no src attribute is present).
		$def->addAttribute('img', 'src*', new HTMLPurifier_AttrDef_URI());
	}
	
	$story = '';
	//$story .= '<h1><a href="'.$item->get_permalink().'">'.$item->get_title().'</a></h1>';
	//$story .= '<p>'.$item->get_date('j M Y').'</p>';
	$content = $item->get_content();
	// run content through Tidy (if available)
	if (function_exists('tidy_parse_string')) {
		$tidy = tidy_parse_string($content, $tidy_config, 'UTF8');
		$tidy->cleanRepair();
		$content = $tidy->value;
	}
	// replace double <br>s to linebreaks
	$content = preg_replace('!<br[^>]+>\s*<br[^>]+>!m', "\n\n", $content);
	// end here if character count is about to exceed our maximum
	$strlen += strlen($content);
	if ($strlen > $max_strlen) {
		break;
	}
	// run content through HTML Purifier
	$content = $purifier->purify($content, $config);
	// run through Tidy one last time (TODO: check if this step can be avoided)
	if (function_exists('tidy_parse_string')) {
		$tidy = tidy_parse_string($content, $tidy_config, 'UTF8');
		$tidy->cleanRepair();
		$content = $tidy->value;
	}
	// a little additional cleanup...
	$content = str_replace('<p><br /></p>', '<br />', $content);
	$content = preg_replace('!<br />\s*<(/?(h2|p|li|ul|ol))>!', '<$1>', $content);
	//$content = preg_replace('!<br />\s*</p>!', '</p>', $content);
	$content = preg_replace('!\s*<br />\s*!', '<br />', $content);
	$content = preg_replace('!</(p|blockquote)>\s*<br />\s*!', '</$1>', $content);
	$content = str_replace('<p><br />', '<p>', $content);
	$content = str_replace('<p>&nbsp;</p>', '', $content);
	// move full stops inside <a> element (prevents TCPDF from moving the full stop to a new line on its own)
	//$content = str_replace('</a>.<', '.</a><', $content);
	// move punctuation inside <a> elements to prevent TCPDF from moving punctuation to 
	// separate line - todo: fix in TCPDF.
	$content = preg_replace('!</a>([:.,])<!', '$1</a><', $content);
	$content = preg_replace('!<a[^>]*>(<br />)+</a>!', '', $content);
	$content = preg_replace('!<(strong|a[^>]*)><br />!', '<$1>', $content);
	$content = preg_replace('!<p>\s*</p>!', '', $content);
	//$content = preg_replace('!</p>\s+<p>!', '</p><p>', $content);
	if ($get_images) {
		$content = preg_replace('!<strong>(<img[^>]+>)</strong>!', '$1', $content);
		//$content = preg_replace('!<p>((<a[^>]*>)?<img[^>]+>(</a>)?)</p>!', '<br />$1', $content);
		//$content = preg_replace('!((<a[^>]*>)?<img[^>]+>(</a>)?)!', '<br />$1', $content);
		$content = preg_replace('!(<img[^>]+>)<br />!', '$1', $content);
		//$content = preg_replace('!(<img[^>]+>)!', '<p>$1</p>', $content);
	}
	$content = preg_replace('!^(<br />)+!', '', $content);
	//$content = preg_replace('/\s+/', ' ', $content);
	//run content through PHP Typography to make things pretty
	$content = $typo->process($content);

	//$content = str_replace('</a>)<', ')</a><', $content);
	//$content = preg_replace('!\((<a[^>]+>)!', '$1(', $content);
	
	$title = trim($item->get_title());
	//$title = SmartyPants($title);
	$title = $typo->process($title);
	//echo $title." ... ";
	$story .= $content;
	// add enclosure link
	/*
	if ($enclosure = $item->get_enclosure()) {
		if ($enclosure->get_link()) {
			$story = '<p><a href="'.$enclosure->get_link().'">Click here to view or listen to the audio/video.</a></p>'.$story;
		}
	}
	*/
	//die($story);
	//die($purifier->purify($item->get_content()));
	$date = ($show_date) ? (int)$item->get_date('U') : 0;
	$pdf->addItem('<a href="'.$item->get_permalink().'">'.$title.'</a>', $story, $date, $author); 
} 

// // make PDF
$pdf->makePdf();
// // output PDF
$pdf->Output($cache_file, 'F');
$pdf->Output('news.pdf', 'I'); 

?>