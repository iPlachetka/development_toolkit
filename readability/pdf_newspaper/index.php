<?php
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+(60*60*12)) . ' GMT');
header('Content-Type: text/html; charset=utf-8'); 
if (file_exists(dirname(__FILE__).'/config.php')) {
	require_once(dirname(__FILE__).'/config.php');
}
echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>PDF Newspaper | fivefilters.org</title>
	<link rel="stylesheet" href="css/default.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="js/validationEngine.jquery.css" type="text/css" media="screen" />
	<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="js/jquery.validationEngine-en.js"></script>
	<script type="text/javascript" src="js/jquery.validationEngine.js"></script>
	<script type="text/javascript">
	// bookmarklet
	var title = "Your+Personal+Newspaper";
	var mode = "multi-story";
	var order = "desc";
	var images = "false";
	var date = "true";
	var fulltext = "true";
	var apikey = "";
	var subheading = "";

	var baseHref = window.location.toString().match(/.*\//);
	var linkStringStart = "javascript:location.href='" + baseHref + "makepdf.php?v=2&";
	var linkStringEnd   = "&feed='+escape(document.location.href);";	
	
	$(document).ready(function() {
		$('#advanced').hide();
		
		///////////////
		// bookmarklet
		///////////////
		$("#bookmarklet").attr("href", linkStringStart + "title=" + title + "&mode=" + mode + "&order=" + order + "&images=" + images + "&date=" + date + "&fulltext=" + fulltext + "&api_key=" + apikey + "&sub=" + subheading + linkStringEnd);
		function changeBookmarklet(name,val) {
			switch(name){
				case "title":
					title = escape(val);
					break;
				case "mode":
					mode = escape(val);
					break;
				case "order":
					order = escape(val);
					break;
				case "images":
					if ($("#images").is(":checked")) {
						images = "true";
					} else {
						images = "false";
					}
					break;
				case "date":
					if ($("#date").is(":checked")) {
						date = "true";
					} else {
						date = "false";
					}
					break;					
				case "fulltext":
					if ($("#fulltext").is(":checked")) {
						fulltext = "true";
					} else {
						fulltext = "false";
					}
					break;
				case "api_key":
					apikey = escape(val);
					break;
				case "sub":
					subheading = escape(val);
					break;					
			}
			$("#bookmarklet").attr("href", linkStringStart + "title=" + title + "&mode=" + mode + "&order=" + order + "&images=" + images + "&date=" + date +"&fulltext=" + fulltext + "&api_key=" + apikey + "&sub=" + subheading + linkStringEnd);
		}

		$("#images, #fulltext, #order, #mode, #date").bind("change", function(){
				changeBookmarklet(this.name, this.value);
		});
		$("#title, #api_key, #sub").bind("keyup", function(){
				changeBookmarklet(this.name, this.value);
		});		
		
		$("#bookmarklet").bind("click", function(){
			if ($.browser.msie) {
				alert("Right-click and select 'Add To Favorites...'");
			} else {
				alert("Drag this link to your browser's bookmarks toolbar.");
			}
			return false;
		});
		
		
		$('#toggle-advanced').click(function() {
			if (!$('#advanced').is(':visible')) {
				$('#toggle-advanced label').text('hide options');
				$('#advanced').slideToggle('fast');
			} else {
				$('#toggle-advanced label').text('show options...');
				$('#advanced').slideToggle('fast', function() {
				});
			}
		});
		
		$('#mode').bind("change", function() {
			if (this.value == 'single-story') {
				$('#title-label').hide();
				$('#order-label').hide();
			} else {
				$('#title-label').show();
				$('#order-label').show();
			}
		});		
		
		// label + input field 
		$('input[title]').each(function() {
			if($(this).val() === '') {
				$(this).val($(this).attr('title'));
			}

			$(this).focus(function() {
				if($(this).val() === $(this).attr('title')) {
					$(this).val('').addClass('focused');
				}
			});

			$(this).blur(function() {
				if($(this).val() === '') {
					$(this).val($(this).attr('title')).removeClass('focused');
				}
			});
		});
		// validate form
		$("#pdf_form").validationEngine({scroll:false, inlineValidation:false});		
	});
	</script>
  </head>
  <body>
	<div id="header">
		<h1 id="title"><a href="http://fivefilters.org">fivefilters.org</a></h1>
		<div id="menu">
		<a href="../explore_independent_media.php">explore independent media</a><a href="../pdf-newspaper">pdf newspaper</a><a href="../content-only">full-text rss</a><a href="../term-extraction">term extraction</a><a href="#donate" style="background-color: red;">donate</a>
		</div>
	</div>
	<div id="content">
	<h1>PDF Newspaper</h1>
    <form method="get" action="makepdf.php" id="pdf_form">
	<input type="hidden" name="v" value="2" />
	<!-- <label id="feed_label" for="feed">Enter feed or OPML URL</label> -->
	<input type="text" id="feed" title="Enter URL here (web page or feed or OPML)" name="feed" class="validate[required,exemptString[Enter URL here (web page or feed or OPML)]]" />
	<div id="toggle-advanced" class="advanced-bottom"><label>show options...</label></div>
	<div id="advanced">
		<fieldset>
			<legend>General options</legend>
			<label class="inline">Mode: <select id="mode" name="mode">
				<option value="multi-story">Multiple stories</option>
				<option value="single-story">Single story</option>
			</select></label>
			<label class="inline">Display: images <input type="checkbox" id="images" name="images" value="true" /></label> <label class="inline">date and time <input type="checkbox" id="date" name="date" value="true" checked="checked" /></label>
			<label id="title-label">Title: <input id="title" type="text" name="title" value="Your Personal Newspaper" style="width: 250px;" /></label>
			<label id="order-label">Show stories in <select id="order" name="order"><option value="desc">descending</option><option value="asc">ascending</option></select> date order</label>

			<?php if (@$options->allow_full_text_option) { ?>
			<label>Fetch full text: <input type="checkbox" id="fulltext" name="fulltext" value="true" /> (use for partial feeds)</label>		
			<?php } ?>
		</fieldset>
	
		<fieldset>
			<legend>Premium options ($10/month, <a href="#limits">more information</a>)</legend>
			<label>API key: <input type="text" id="api_key" name="api_key" /></label>
			<label>Subheading: <input type="text" id="sub" name="sub" value="" style="width: 180px" /></label>
			<label>Custom header image, custom fonts: <em>please send via email</em></label>
		</fieldset>
		
		<fieldset>
			<legend>Bookmarklet (<a href="#bookmarklet-info">more information</a>)</legend>
			<label><a style="cursor: move;" href="javascript:location.href='http://<?php echo $_SERVER['HTTP_HOST'].rtrim($_SERVER['REQUEST_URI'], '/'); ?>/makepdf.php?title=Your+Personal+Newspaper&feed='+escape(document.location.href);" id="bookmarklet">Create PDF</a></label>
		</fieldset>
	</div>
	<input type="submit" id="submit" name="submit" value="Create PDF" /> 
	<h4>Or select from one of the feeds below...</h4>
	<div class="alt-feeds">
		<?php if (@$options->allow_full_text_option) { ?>
		<a href="makepdf.php?v=2&title=Medialens&date=false&fulltext=true&feed=<?php echo urlencode('http://www.myantiwar.org/feeds/rss/channel_8.xml'); ?>">Medialens</a>
		<a href="makepdf.php?v=2&title=SchNEWS&date=true&fulltext=true&feed=<?php echo urlencode('http://www.schnews.org.uk/feed.xml'); ?>">SchNEWS</a>
		<?php } ?>
		<a href="makepdf.php?v=2&title=New+Left+Project&date=true&images=true&feed=<?php echo urlencode('www.newleftproject.org/index.php/site/site_feed'); ?>">New Left Project</a>
		<a href="makepdf.php?v=2&title=UK+Indymedia&date=true&feed=<?php echo urlencode('http://indymedia.org.uk/en/features.rss'); ?>">UK Indymedia</a>
		<a href="makepdf.php?v=2&title=Mark+Curtis&date=true&feed=<?php echo urlencode('http://markcurtis.wordpress.com/'); ?>">Mark Curtis</a>
	</div>
	<p>Read <a href="http://www.keyvan.net/2009/07/select-stories-for-your-newspaper/">selecting stories for your PDF newspaper</a> to see how to combine content.</p>
	<p><strong>Update:</strong> Version 2.0 has been released.</p>
	</form>
	
	<object width="350" height="250"><param name="movie" value="http://widget.chipin.com/widget/id/80d664a4f9d25fc7"></param><param name="allowScriptAccess" value="always"></param><param name="wmode" value="transparent"></param><param name="event_title" value="Donate%20to%20PDF%20Newspaper"></param><param name="event_desc" value="The%20next%20version%20of%20the%20PDF%20Newspaper%20app%20is%20in%20the%20works.%20It%20will%20feature%20a%20new%20template%20and%20a%20WordPress%20plugin.%20If%20you%20can%2C%20please%20donate%20to%20support%20development."></param><embed src="http://widget.chipin.com/widget/id/80d664a4f9d25fc7" flashVars="event_title=Donate%20to%20PDF%20Newspaper&event_desc=The%20next%20version%20of%20the%20PDF%20Newspaper%20app%20is%20in%20the%20works.%20It%20will%20feature%20a%20new%20template%20and%20an%20upgrade%20to%20the%20latest%20TCPDF.%20If%20you%20can%2C%20please%20donate%20to%20support%20development." type="application/x-shockwave-flash" allowScriptAccess="always" wmode="transparent" width="350" height="250"></embed></object>
	
	<h2>About</h2>
	<p>This is a free software project to help people create printable PDFs from content found on the web. It is a <a href="http://www.gnu.org/philosophy/free-sw.html" title="free as in freedom">free</a> alternative to HP's Tabbloid service. It is being developed as part of the <a href="http://fivefilters.org">Five Filters</a> project to promote alternative, non-corporate media.</p>
	
<!-- AddThis Button BEGIN -->
<script type="text/javascript">var addthis_pub="k1mk1m";</script>
<a style="border-bottom: none;" href="http://www.addthis.com/bookmark.php?v=20" onmouseover="return addthis_open(this, '', '[URL]', '[TITLE]')" onmouseout="addthis_close()" onclick="return addthis_sendto()"><img src="http://s7.addthis.com/static/btn/lg-share-en.gif" width="125" height="16" alt="Bookmark and Share" style="border:0"/></a><script type="text/javascript" src="http://s7.addthis.com/js/200/addthis_widget.js"></script>
<!-- AddThis Button END -->

	<h2 id="bookmarklet-info">Bookmarklet</h2>
	<p>To easily convert web pages and feeds into PDF, we provide a bookmarklet which can be added to your browser's bookmarks toolbar.
	Once added, you can get a PDF version of any web page or feed you're viewing by clicking the bookmarklet - no need to visit this website.</p>
	<p>The bookmarklet is available in the options panel (click 'show options...' on the form above). Before dragging it up to your
	bookmarks toolbar, configure it by setting the title, story order, image, full-text and other options. Then drag up. If you've
	never used a bookmarklet before, <a href="http://en.wikipedia.org/wiki/Bookmarklet">read more</a>.</p>
	
	<h2 id="wordpress-plugin">WordPress Plugin</h2>
	<a style="float: right; margin-left:20px; border: none;" href="http://www.rsc-ne-scotland.org.uk/mashe/wordpress-plugins/make-pdf-newspaper-2/" title="Make PDF Newspaper"><img src="http://s.wordpress.org/about/images/buttons/buttonw-grey.png" alt="WordPress logo" /></a>
	<p><a href="http://www.rsc-ne-scotland.org.uk/mashe/wordpress-plugins/make-pdf-newspaper-2/">Make PDF Newspaper</a> is a new WordPress plugin which integrates the FiveFilters.org code and allows users to offer their content in PDF form. Many thanks to <a href="http://www.rsc-ne-scotland.org.uk/mashe/">Martin Hawksey</a> for developing the plugin.</p>
	
	<h2>Compare</h2>
	<table id="compare">
		<thead>
			<tr><th></th><th>FiveFilters.org</th><th>Tabbloid.com</th><th>FeedJournal.com</th><th>Feedbooks.com</th><th>Zinepal.com</th></tr>
		</thead>
		<tbody>
			<tr>
				<th>PDF library</th>
				<td style="background-color: #C8E6A6"><a href="http://www.tcpdf.org">TCPDF</a> (<a href="http://www.gnu.org/philosophy/free-sw.html">free</a>)</td>
				<td style="background-color: #f1a1a1"><a href="http://www.pdflib.com/" rel="nofollow">PDFLib</a> (not <a href="http://www.gnu.org/philosophy/free-sw.html">free</a>)</td>
				<td style="background-color: #C8E6A6"><a href="http://sourceforge.net/projects/itextsharp/">iTextSharp</a> (<a href="http://www.gnu.org/philosophy/free-sw.html">free</a>)</td>
				<td style="background-color: #f1a1a1"><a href="http://princexml.com" rel="nofollow">PrinceXML</a> (not <a href="http://www.gnu.org/philosophy/free-sw.html">free</a>)</td>
				<td style="background-color: #C8E6A6"><a href="http://xmlgraphics.apache.org/fop/">Apache FOP</a> (<a href="http://www.gnu.org/philosophy/free-sw.html">free</a>)</td>
			</tr>
			<tr>
				<th><a href="http://www.gnu.org/philosophy/free-sw.html" title="free as in freedom">Free software</a> (<acronym title="free/libre/open source software">FLOSS</acronym>)</th>
				<td style="background-color: #C8E6A6">Yes (see below)</td>
				<td style="background-color: #f1a1a1">No</td>
				<td style="background-color: #f1a1a1">No</td>
				<td style="background-color: #f1a1a1">No</td>
				<td style="background-color: #f1a1a1">No</td>
			</tr>
			<!--
			<tr>
				<th>Output #1: <a href="samples/lasthours/feed.xml" style="font-weight: normal;">Last Hours</th>
				<td><a href="samples/lasthours/fivefilters.pdf">PDF</a> (<a href="samples/lasthours/fivefilters_textonly.pdf">text only</a>)</td>
				<td><a href="samples/lasthours/tabbloid.pdf">PDF</a></td>
				<td><a href="samples/lasthours/feedjournal.pdf">PDF</a> (<a href="samples/lasthours/feedjournal_textonly.pdf">text only</a>)</td>
				<td><a href="samples/lasthours/feedbooks.pdf">PDF</a></td>
				<td><a href="samples/lasthours/zinepal.pdf">PDF</a></td>
			</tr>
			<tr>
				<th>Output #2: <a href="samples/crimethink/feed.xml" style="font-weight: normal;">CrimethInc.</th>
				<td><a href="samples/crimethink/fivefilters.pdf">PDF</a> (<a href="samples/crimethink/fivefilters_textonly.pdf">text only</a>)</td>
				<td><a href="samples/crimethink/tabbloid.pdf">PDF</a></td>
				<td><a href="samples/crimethink/feedjournal.pdf">PDF</a> (<a href="samples/crimethink/feedjournal_textonly.pdf">text only</a>)</td>
				<td><a href="samples/crimethink/feedbooks.pdf">PDF</a></td>
				<td><a href="samples/crimethink/zinepal.pdf">PDF</a></td>
			</tr>
			<tr>
				<th>Output #3: <a href="samples/ceasefire/feed.xml" style="font-weight: normal;">Ceasefire</th>
				<td><a href="samples/ceasefire/fivefilters.pdf">PDF</a> (<a href="samples/ceasefire/fivefilters_textonly.pdf">text only</a>)</td>
				<td><a href="samples/ceasefire/tabbloid.pdf">PDF</a></td>
				<td><a href="samples/ceasefire/feedjournal.pdf">PDF</a> (<a href="samples/ceasefire/feedjournal_textonly.pdf">text only</a>)</td>
				<td><a href="samples/ceasefire/feedbooks.pdf">PDF</a></td>
				<td><a href="samples/ceasefire/zinepal.pdf">PDF</a></td>
			</tr>
			-->
		</tbody>
	</table>
	<!--<p>Note: Many of these services allow you to customize the output. The PDFs above were generated with the default settings in each app.</p>-->
	
	<h2 id="api">API</h2>
	<p>To create a PDF from a web page or a feed, pass the URL (<a href="http://meyerweb.com/eric/tools/dencoder/">encoded</a>) in the querystring to the following URL:</p>
	<ul>
		<li style="font-family: monospace;">http://fivefilters.org/pdf-newspaper/makepdf.php?feed=<strong>[url]</strong></li>
	</ul>
	<p>To customise the output, the following options can be appended to the querystring above (again, make sure to URL encode the values):</p>
	<ul>
		<li style="font-family: monospace;">&title=<strong>[PDF title]</strong></li>
		<li style="font-family: monospace;">&order=<strong>[Date order]</strong> ('desc' or 'asc')</li>
		<li style="font-family: monospace;">&images=<strong>[Include images?]</strong> ('true' or 'false')</li>
		<li style="font-family: monospace;">&fulltext=<strong>[Fetch full text?]</strong> ('true' or 'false')</li>
	</ul>	
	<p>If you have an API key, add that to the querystring:</p>
	<ul>
		<li style="font-family: monospace; white-space:nowrap;">http://fivefilters.org/pdf-newspaper/makepdf.php?feed=<strong>[url]</strong>&amp;api_key=<strong>[key]</strong></li>
	</ul>
	<p><strong>Note:</strong> When a valid API key is supplied, the service redirects to another URL
	to hide the API key - a key ID and unique hash replace the API key in the querystring. The API key should not be shared, 
	so if you'd like to link to a PDF publically while protecting your API key, make sure you copy and paste the URL that results
	after the redirect.</p>
	
	<h2 id="limits">Web Service Limits: Free and Paid Access</h2>
	<p>We have imposed limits to keep the service running smoothly and to avoid abuse. 
	Certain features have also been reserved for paying users:</p>
	<table id="compare-limits">
	<thead>
		<tr><th></th><th>Free</th><th><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RWFWACL287H3S">$10/month</a></th></tr>
	</thead>
	<tr>
		<th>PDF pages produced per feed</th>
		<td>~10</td>
		<td>~20</td>
	</tr>
	<tr>
		<th>Full-text fetches per feed</th>
		<td>4</td>
		<td>10</td>
	</tr>
	<tr>
		<th>Customisable subheading</th>
		<td>No</td>
		<td>Yes</td>
	</tr>
	<tr>
		<th>Custom title image</th>
		<td>No</td>
		<td>Yes (should be emailed to us)</td>
	</tr>
	<tr>
		<th>Custom fonts</th>
		<td>No</td>
		<td>Yes (should be emailed to us)</td>
	</tr>	
	</table>
	
	<p>If you don't like the limits associated with the free service, you can either download the source code and host it 
	yourself (see below) or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RWFWACL287H3S">buy an API key</a> 
	for $10 a month and continue to use the hosted solution here on fivefilters.org. (The monthly fee allows us to cover costs and continue
	developing the project.)</p>	
	
	<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RWFWACL287H3S" class="download"><strong>FiveFilters.org API Key</strong><br /><span class="small">hosted service &mdash; $10/month<span><br /><img src="https://www.paypal.com/en_GB/i/btn/btn_buynow_LG.gif" style="vertical-align: middle;"></a>	
	
	<h2 id="pros-cons">Hosted or Self-hosted: What's Right for Me?</h2>
	
	<p>Unlike most web services you'll encounter online, we want our users to be free to examine and run the code behind FiveFilters.org
	however they like. As such, in addition to our hosted option, we also give you the option of self-hosting.</p>
	<p>Using the hosted service is the easiest option as we manage everything&mdash;you don't have to set anything up or worry about code. 
	It also means you do not have to worry about staying up to date&mdash;we maintain the code and any changes we make 
	will automatically be made available to you.</p>
	<p>If, however, you have your own hosting account or manage your own server, the self-hosted option gives you the freedom to run the code and 
	manage everything yourself without relying on us. It also gives developers the opportunity to examine the code and make changes.</p>	
	
	<h2>Source Code and Technologies</h2>
	<p><a href="https://code.launchpad.net/~keyvan/fivefilters/pdf-newspaper">Source code available</a>.<br />The application uses PHP, <a href="http://www.tcpdf.org">TCPDF</a>, <a href="http://tidy.sourceforge.net/">HTML Tidy</a>, <a href="http://htmlpurifier.org">HTML Purifier</a>, <a href="http://simplepie.org/">SimplePie</a>, <a href="http://kingdesk.com/projects/php-typography/">PHP Typography</a>, <a href="http://freshmeat.net/projects/opml-parser-class/">OPML Parser</a> and <a href="http://code.google.com/p/phphooks/">PHP Hooks</a>.</p>

	<h2>Installation and System Requirements</h2>
	<p>This code should run on most hosts running PHP5 with Tidy enabled. I have it running on <a href="https://www.nearlyfreespeech.net/">NearlyFreeSpeech.NET</a> (a great host with a smart pricing model) but I've also tested it on Windows using <a href="http://www.wampserver.com/en/index.php">WampServer</a>. The instructions below will install the code in a folder called 'pdf-newspaper'. The instructions have been tested on a NearlyFreeSpeech account but should work on other hosts which offer shell access and have the <a href="http://bazaar-vcs.org/">Bazaar</a> client installed. (Note: If you can't connect directly to the server to carry out these steps, you can install the Bazaar client on your own computer, carry out steps 2 and 3 to retrieve the files, then connect to your server via FTP to upload the files.)</p>
	
	<ol style="width: 800px">
		<li>Log in to your host using SSH</li>
		<li>Change to the directory where you want RSS to PDF Newspaper installed</li>
		<li>Enter <kbd>bzr export pdf-newspaper http://bazaar.launchpad.net/~keyvan/fivefilters/pdf-newspaper/</kbd></li>
		<li>Now enter <kbd>chmod -R 0777 pdf-newspaper/cache/</kbd></li>
		<li>That's it! Try accessing the pdf-newspaper folder through your web browser, you should see the form asking for a feed URL.</li>
	</ol>
	
	<p>If you'd like to generate PDFs without going through the form first, you can simply pass the feed URL in the query string to makepdf.php. For example:<br /><tt>http://example.org/rss-to-pdf/makepdf.php?feed=http://schnews.org.uk/feed.xml</tt></p>
	
	<p>If you'd like to change the title image, replace images/five_filters.jpg with an image of your own (keeping the same filename).</p>
	
	<h2>Todo</h2>
	<ul>
	<li><del>Image support</del> (partial)</li>
	<li><del>Prevent headlines wrapping to next column/page</del></li>
	<li><del>Display date</del></li>
	<li><del>Custom title</del></li>
	<li><del>Compact, ink/toner saving template</del></li>
	<li><del>WordPress plugin</del> (thanks Martin!)</li>
	<li>Display source</li>
	<li>Test support for other languages</li>
	<li>More testing</li>
	<li>Develop web app
		<ul>
			<li>multiple feeds (see tabbloid/feedbooks' newspaper feature)</li>
			<li>multiple output formats (for ebook use)</li>
		</ul></li>
	</ul>
	
	<h2>Support</h2>
	<p>I'm happy to help activists/anarchists/progressives set this up for their own content. I can either help you set it up on your own server, or create a customised look (e.g. different title image) and host it here. If you fall in this category, get in touch.</p>
	<p>If you don't fall in this category, I offer paid support.</p>
	<p><a href="https://bugs.launchpad.net/fivefilters">Bug reports</a> and <a href="https://answers.launchpad.net/fivefilters">questions</a> welcome.</p>
	
	<h2>License</h2>
	<p><a href="http://en.wikipedia.org/wiki/Affero_General_Public_License" style="border-bottom: none;"><img src="images/agplv3.png" /></a><br />This web application is licensed under the <a href="http://en.wikipedia.org/wiki/Affero_General_Public_License">AGPL version 3</a> &mdash; which basically means if you use the code to offer PDF creation for your users, you are also required to share the code with your users so they can do the same themselves. (<a href="http://www.clipperz.com/users/marco/blog/2008/05/30/freedom_and_privacy_cloud_call_action">More on why this is important.</a>)</p> 
	<p>The libraries used by the application are licensed as follows...</p>
	<ul>
		<li>TCPDF: <a href="http://www.fsf.org/licensing/licenses/lgpl.html">LGPL</a></li>
		<li>HTML Tidy: <a href="http://tidy.sourceforge.net/#license">MIT-like</a></li>
		<li>HTML Purifier: <a href="http://www.fsf.org/licensing/licenses/lgpl.html">LGPL</a></li>
		<li>SimplePie: <a href="http://en.wikipedia.org/wiki/BSD_license">BSD</a></li>
		<li>PHP Typography: <a href="http://www.gnu.org/licenses/gpl-2.0.html">GPL v2</a></li>
		<li>OPML Parser: Freeware</li>
		<li>PHP Hooks: <a href="http://www.fsf.org/licensing/licenses/lgpl.html">LGPL</a></li>		
	</ul>
	
	<h2 id="donate">Donate</h2>
	<p>If you find the service here or the code useful, please consider donating. I'm a student working on this project in my spare time. At the moment it takes up a lot of my time and as much as I like working on it, I need to think about living and hosting costs too. The site carries no advertising and I've released the code under a <a href="http://www.gnu.org/philosophy/open-source-misses-the-point.html">free software</a> license so anyone can benefit from it. Donations are the only way I can continue developing the project and continue avoiding <a href="http://www.youtube.com/watch?v=oztdRo9GLLk">wage slavery</a>. If you're able to donate, your contribution (whatever the amount) would be greatly appreciated.</p>
	
	<object width="350" height="250"><param name="movie" value="http://widget.chipin.com/widget/id/80d664a4f9d25fc7"></param><param name="allowScriptAccess" value="always"></param><param name="wmode" value="transparent"></param><param name="event_title" value="Donate%20to%20PDF%20Newspaper"></param><param name="event_desc" value="The%20next%20version%20of%20the%20PDF%20Newspaper%20app%20is%20in%20the%20works.%20It%20will%20feature%20a%20new%20template%20and%20a%20WordPress%20plugin.%20If%20you%20can%2C%20please%20donate%20to%20support%20development."></param><embed src="http://widget.chipin.com/widget/id/80d664a4f9d25fc7" flashVars="event_title=Donate%20to%20PDF%20Newspaper&event_desc=The%20next%20version%20of%20the%20PDF%20Newspaper%20app%20is%20in%20the%20works.%20It%20will%20feature%20a%20new%20template%20and%20an%20upgrade%20to%20the%20latest%20TCPDF.%20If%20you%20can%2C%20please%20donate%20to%20support%20development." type="application/x-shockwave-flash" allowScriptAccess="always" wmode="transparent" width="350" height="250"></embed></object>	
	
	<h2>Author</h2>
	<p>Created by <a href="http://www.keyvan.net">Keyvan Minoukadeh</a> for the <a href="http://fivefilters.org">Five Filters</a> project.<br />
	Email: keyvan (at) keyvan.net</p>
	
	</div>
  </body>
</html>
