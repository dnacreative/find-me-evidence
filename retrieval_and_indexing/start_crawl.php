<?php
error_reporting ( E_ERROR );

require_once("lib/PHPCrawl/PHPCrawler.class.php");
require_once("lib/html2text/html2text.php"); // TODO: perhaps solr.HTMLStripCharFilterFactory in Solr would also work?
require_once('lib/http_post/http_post.php');

include('./config.php');

set_time_limit ( 60 * 60 * 24 * 3); // Execution times out only after 3 days, crawling might take a while!

/*
 * Note: The following settings for $link_follow_mode are available:
*
* 0 - The crawler will follow EVERY link, even if the link leads to a different host or domain.
* If you choose this mode, you really should set a limit to the crawling-process (see limit-options),
* otherwise the crawler maybe will crawl the whole WWW!
*
* 1 - The crawler only follow links that lead to the same domain like the one in the root-url.
* E.g. if the root-url (setURL()) is "http://www.foo.com", the crawler will follow links to "http://www.foo.com/..."
* and "http://bar.foo.com/...", but not to "http://www.another-domain.com/...".
*
* 2 - The crawler will only follow links that lead to the same host like the one in the root-url.
* E.g. if the root-url (setURL()) is "http://www.foo.com", the crawler will ONLY follow links to "http://www.foo.com/...", but not
* to "http://bar.foo.com/..." and "http://www.another-domain.com/...". This is the default mode.
*
* 3 - The crawler only follows links to pages or files located in or under the same path like the one of the root-url.
* E.g. if the root-url is "http://www.foo.com/bar/index.html", the crawler will follow links to "http://www.foo.com/bar/page.html" and
* "http://www.foo.com/bar/path/index.html", but not links to "http://www.foo.com/page.html".
*/

// Extend the PHPCrawler class and override the handleDocumentInfo()-method
class MyCrawler extends PHPCrawler {
	
	var $site_name;
	var $category;
	var $dataset_priority;
	
	function set_site_name ($site_name) {
		$this->site_name = $site_name;
	}
	
	function set_category ($category) {
		$this->category = $category;
	}
	
	function set_dataset_priority ($dataset_priority) {
		$this->dataset_priority = $dataset_priority;
	}
	
	function handleDocumentInfo($DocInfo) {
		// Just detect linebreak for output ("\n" in CLI-mode, otherwise "<br>").
		if (PHP_SAPI == "cli")
			$lb = "\n";
		else
			$lb = "<br />";
			
		// Print the URL and the HTTP-status-Code
		echo "Page requested: " . $DocInfo->url . " (" . $DocInfo->http_status_code . ")" . $lb;

		// Print the refering URL
		echo "Referer-page: " . $DocInfo->referer_url . $lb;

		// Print if the content of the document was recieved or not
		if ($DocInfo->received == true) {
			echo "Content received: " . $DocInfo->bytes_received . " bytes" . $lb;
			echo "Content type: " . $DocInfo->content_type . $lb;
				
			// Get title of HTML page
			if (preg_match ( '/<title>([^<]+?)<\/title>/', $DocInfo->content, $matches ) && isset ( $matches [1] )) {
				$title = trim($matches [1]);
			} else {
				$title = "Untitled document";
			}
				
			$output =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?><update><add><doc>\n";
										
			$output .= "<field name='title'>" . htmlspecialchars ( $title ) . "</field>\n";
			$output .= "<field name='body'>" . htmlspecialchars ( html2text(extract_useful_page_content($DocInfo->content, $DocInfo->url))) . "</field>\n";
			$output .= "<field name='data_source_name'>" . $this->site_name . "</field>\n";
			$output .= "<field name='dateCreated'>" . date("Y-m-d\TG:i:s\Z") . "</field>\n";
			$output .= "<field name='id'>" . htmlspecialchars($DocInfo->url) . "</field>\n";
			$output .= "<field name='mimeType'>text/plain</field>\n";
			$output .= "<field name='category'>" . $this->category . "</field>\n";
			$output .= "<field name='dataset_priority'>" . $this->dataset_priority . "</field>\n";
			$output .= "</doc></add></update>";
			
			do_post_request(SOLR_URL . '/update', $output);			
			
		} else
			echo "Content not received" . $lb;
			
		echo $lb;
		
		
		print "\n\n\n---------- Full version ----------\n\n\n";
		print $DocInfo->content;
		print "\n\n\n---------- Extracted version ----------\n\n\n";
		print extract_useful_page_content($DocInfo->content, $DocInfo->url);
		print "\n\n\n--------------------\n\n\n";
		
		
		flush ();

		// Wait between requests to decrease load on the crawled server
		sleep(1);
	}
}

/*
 * Do the crawling
 */

crawl("Medscape", "http://emedicine.medscape.com/home", "Evidence-based summary", 10);
crawl_via_links_on_page("Medscape monographs", "http://reference.medscape.com/sitemap_ref_monographs.xml", "/<loc>([^<]+)<\/loc>/", "Drug information", 10, 3);
crawl_via_links_on_page("Merck Manual", "http://www.merckmanuals.com/professional/sitemap.xml", "/<loc>([^<]+)<\/loc>/", "Evidence-based summary", 10, 3);
crawl("ATTRACT (Professional medical Q&A)", "http://www.attract.wales.nhs.uk/", "Evidence-based summary", 8);
crawl("BestBETs (Evidence-based summaries)", "http://bestbets.org/", "Evidence-based summary", 8);
crawl_via_links_on_page("Guideline.gov", "http://www.guideline.gov/browse/index.aspx?alpha=All", '/href="([^"]+)"/', "Evidence-based summary", 8, 16);

// Deprecated:
// crawl("Guideline.gov", "http://guideline.gov/", "Guideline", 7);    // possible alternative: all sites linked from http://www.guideline.gov/browse/index.aspx?alpha=All
// crawl("Merck Manual", "http://www.merckmanuals.com/professional/", "Evidence-based summary", 10); // Sitemap: http://www.merckmanuals.com/professional/sitemap.xml
// crawl("NICE Clinical Guidelines", "http://guidance.nice.org.uk/", "Evidence-based summary", 7, 1);
// crawl("doc2doc", "http://doc2doc.bmj.com/", "Professional discussions", 7);  
// crawl("Diagnosia English", "http://www.diagnosia.com/en/drugs", "Drug information", 8);
// crawl_via_links_on_page("NHS Clinical Knowledge Summaries (UK)", "http://cks.nice.org.uk/sitemap.xml", "/<loc>([^<]+)<\/loc>/",  "Evidence-based summary", 7, 3);


print do_post_request(SOLR_URL . '/update', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><update><commit/><optimize/></update>");



/*
 * FUNCTIONS
 */

/**
 * Extract the domain name (e.g., example.com) from a URL
 */
function get_domain( $url ) {
	$regex  = "/^((http|ftp|https):\/\/)?([\w-]+(\.[\w-]+)+)([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?$/i";
	if ( !preg_match( $regex, $url, $matches ) ) {
		return false;
	}
	$url    = $matches[3];
	$tlds   = array( 'ac', 'ad', 'ae', 'aero', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'arpa', 'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'biz', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cat', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'com', 'coop', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'info', 'int', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm', 'jo', 'jobs', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mil', 'mk', 'ml', 'mm', 'mn', 'mo', 'mobi', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'museum', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc', 'ne', 'net', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'pro', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz', 'tc', 'td', 'tel', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'travel', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'yu', 'za', 'zm', 'zw' );
	$parts  = array_reverse( explode( ".", $url ) );
	$domain = array();

	foreach( $parts as $part ) {
		$domain[] = $part;
		if ( !in_array( strtolower( $part ), $tlds ) ) {
			return implode( ".", array_reverse( $domain ) );
		}
	}
}

function extract_useful_page_content($content, $url) {
	$domain = get_domain($url);

	if ($domain == "medscape.com") {
		if (preg_match("/<!--Overview-->([\W\w]+)<!--\/Overview-->/", $content, $matches) == 1) 
			return $matches[1];
		else return $content;
	}
	elseif ($domain == "merckmanuals.com") {
		if (preg_match("/<!--startindex-->([\W\w]+)<!--stopindex-->/", $content, $matches) == 1) 
			return $matches[1];
		else return $content;
	}
	// TODO: guideline.gov page section selector does not seem to work properly
	elseif ($domain == "guideline.gov") {
		if (preg_match("/<a id='Section420'></a>([\W\w]+)<a id='Section434'>/", $content, $matches) == 1) 
			return $matches[1];
		else return $content;
	}
	else return $content;
}

function crawl($site_name, $seed_url, $category, $dataset_priority, $link_follow_mode = 2)  {

	$crawler = new MyCrawler ();
	
	$crawler->set_site_name($site_name);
        $crawler->set_category($category);
	$crawler->set_dataset_priority($dataset_priority);

	$crawler->setURL($seed_url);
	$crawler->setFollowMode($link_follow_mode);
	
	// Only receive content of files with specifc content-types
	$crawler->addContentTypeReceiveRule ( "#text/html#" );
	$crawler->addContentTypeReceiveRule ( "#application/xhtml+xml#" );
	
	// Ignore links to files with the following endings
	$crawler->addURLFilterRule ( "#\.(jpg|jpeg|gif|png|css|js|pdf|doc|exe)$# i" );
	
	// Store and send cookie-data like a browser does
	$crawler->enableCookieHandling ( true );
	
	// Set traffic limit in bytes // TODO: set much higher limit than 1MB (1000 * 1024) for production system!
	$crawler->setTrafficLimit ( 10000 * 1000 * 1024 ); 
	
	$crawler->setStreamTimeout(5);
	                                        
	// $crawler->setPageLimit(50);
	
	if (PHP_SAPI == "cli")
		$lb = "\n";
	else
		$lb = "<br />";
	
	
	echo "STARTING CRAWL OF " . $site_name . $lb;
	
	$crawler->go ();
	
	$report = $crawler->getProcessReport ();
	

	echo "Summary:" . $lb;
	echo "Links followed: " . $report->links_followed . $lb;
	echo "Documents received: " . $report->files_received . $lb;
	echo "Bytes received: " . $report->bytes_received . " bytes" . $lb;
	echo "Process runtime: " . $report->process_runtime . " sec" . $lb;
}

function crawl_via_links_on_page($site_name, $site_url, $regex_for_identifying_urls, $category, $dataset_priority, $interval_between_requests) {
	
	print "STARTING CRAWL OF " . $site_name . "\n";
	
	$sitemap = file_get_contents($site_url);
	preg_match_all ($regex_for_identifying_urls, $sitemap, $url_matches, PREG_PATTERN_ORDER);  // e.g., "/<loc>[^<]+<\/loc>/" for Sitemaps
	$urls = $url_matches[1];
	
        $ch = curl_init();    // initialize curl handle
        
	foreach ($urls as $url) {
		print "Downloading $url \n";
		$html = "";
		curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
                curl_setopt($ch, CURLOPT_TIMEOUT, 10); // times out after 4s
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:11.0) Gecko/20100101 Firefox/11.0");
                $cookie_file = "cookie1.txt";
                curl_setopt($ch, CURLOPT_COOKIESESSION, true);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
                $html = curl_exec($ch); // run the whole process
		if ($html == "") continue;
		
		// Get title of HTML page
		if (preg_match ( '/<title>([^<]+?)<\/title>/', $html, $matches ) && isset ( $matches [1] )) {
			$title = trim($matches [1]);
		} else {
			$title = "Untitled document";
		}
		
		$output =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?><update><add><doc>\n";
		
		$output .= "<field name='title'>" . htmlspecialchars ( $title ) . "</field>\n";
		$output .= "<field name='body'>" . htmlspecialchars ( html2text(extract_useful_page_content($html, $url))) . "</field>\n";
		$output .= "<field name='data_source_name'>" . $site_name . "</field>\n";
		$output .= "<field name='dateCreated'>" . date("Y-m-d\TG:i:s\Z") . "</field>\n";
		$output .= "<field name='id'>" . htmlspecialchars($url) . "</field>\n";
		$output .= "<field name='mimeType'>text/plain</field>\n";
		$output .= "<field name='category'>" . $category . "</field>\n";
		$output .= "<field name='dataset_priority'>" . $dataset_priority . "</field>\n";
		$output .= "</doc></add></update>";
		
		print $output . "\n";
			
		print do_post_request(SOLR_URL . '/update', $output);
		
		sleep($interval_between_requests);
	}
        
        curl_close($ch);
}
?>