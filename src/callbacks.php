<?php 

ini_set('include_path', dirname(dirname(__FILE__)));
require_once 'config.php';
require_once 'libs/meekrodb/meekrodb.2.2.class.php';

DB::$user = Config('DB_USER');
DB::$password = Config('DB_PASSWORD');
DB::$dbName = Config('DB_NAME');
DB::$encoding = Config('DB_ENCODING');


define('FILEDIR_APPS_PER_PAGE', 15);

/* scrape_filedir_directory */

function scrape_filedir_directory($content, $url, &$tasks)
{
	$categories = parse_filedir_directory_categories($content);
	
	if($categories == null) return array(
								'parse_error' => true,
								'error_data' => array(
									'callback' => 'scrape_filedir_directory',
									'content' => $content,
								),
							);

	$new_tasks = array();	
	foreach ($categories as $category)
	{
		$page_count = ceil($category['app_count'] / FILEDIR_APPS_PER_PAGE);
		for ($i = 1; $i <= $page_count; $i++)
		{
			if(!isset($new_tasks[$i - 1])) $new_tasks[$i - 1] = array();

			if($i == 1) $page_url = $category['url'];
			else $page_url = $category['url']."page-$i.html";

			$new_tasks[$i - 1][] = array(
									'url' => $page_url,
									'curl_opts' => 'set_filedir_page_curl_opts',
									'callback' => 'scrape_filedir_page',
								);
		}
	}

	for ($i = 0; $i < sizeof($new_tasks); $i++)
		foreach ($new_tasks[$i] as $task)
			add_task($task, $tasks);

	return array('parse_error' => false);
}

function parse_filedir_directory_categories($content)
{
	$pattern_category = '/(?<=\<\/i\>\<a href=\")http:\/\/filedir.com\/(.(?!span class=\"sup_infm\")){0,1000}/';
	
	$matches = null;
	preg_match_all($pattern_category, $content, $matches);
	$matches = $matches[0];

	if(empty($matches)) return null;
	
	$categories = array();
	foreach ($matches as $match)
	{
		$url_end = strpos($match, '>') - 1;
		$url = substr($match, 0, $url_end);
		
		$app_count_begin = strpos($match, '(') + 1;
		$app_count_end = strpos($match, ')');
		$app_count = substr($match, $app_count_begin, $app_count_end - $app_count_begin);
		$app_count = str_replace(',', '', $app_count);
		
		$categories[] = array(
							'url' => $url,
							'app_count' => $app_count
						);
	}

	return $categories;
}

/* scrape_filedir_page */

function scrape_filedir_page($content, $url, &$tasks)
{
	$apps_data = parse_filedir_page_apps_data($content);
	
	if($apps_data == null)
		return array(
				'parse_error' => true,
				'error_data' => array(
					'callback' => 'scrape_filedir_page',
					'content' => $content,
				),
			);

	foreach ($apps_data as $app_data)
	{
		add_task_at_front(array(
					'url' => $app_data['filedir_app_url'],
					'curl_opts' => 'set_filedir_app_curl_opts',
					'callback' => 'scrape_filedir_app',
				), $tasks);

		DB::insertUpdate('t_filedir_apps', $app_data);
	}

	return array('parse_error' => false);
}

function parse_filedir_page_apps_data($content)
{
	$pattern_app_name = "/(?<=\<div class=\"ptitle_in\"\>)(.(?!\<div class=\"sbutt\"\>)){0,1000}/";
	$pattern_developer = "/(?<=\<a class=\"cname notranslate\" href=\")(.(?!\<\/a\> \<br \/\>\<\/li\>)){0,1000}./";
	$pattern_filedir_app_url = "/(?<=Now\<\/a\> \<a class=\"uibutton large)(.(?!\<i class=\"spr_os)){0,1000}/";

	$matches_app_name = null;
	preg_match_all($pattern_app_name, $content, $matches_app_name);
	$matches_app_name = $matches_app_name[0];

	$matches_developer = null;
	preg_match_all($pattern_developer, $content, $matches_developer);
	$matches_developer = $matches_developer[0];

	$matches_filedir_app_url = null;
	preg_match_all($pattern_filedir_app_url, $content, $matches_filedir_app_url);
	$matches_filedir_app_url = $matches_filedir_app_url[0];

	$app_names = array();
	foreach ($matches_app_name as $match)
	{
		$app_name_begin = strrpos($match, '.html">') + strlen('.html">');
		$app_name_end = strrpos($match, '</a>');
		$app_name = substr($match, $app_name_begin, $app_name_end - $app_name_begin);
		if(empty($app_name)) return null;
		$app_names[] = $app_name;
	}

	$developers = array();
	foreach ($matches_developer as $match)
	{
		$developer_begin = strrpos($match, '>') + strlen('>');
		$developer = substr($match, $developer_begin);
		if(empty($developer)) return null;
		$developers[] = $developer;
	}

	$filedir_app_urls = array();
	foreach ($matches_filedir_app_url as $match)
	{
		$filedir_app_url_begin = strrpos($match, 'http://');
		$filedir_app_url_end = strrpos($match, '.html') + strlen('.html');
		$filedir_app_url = substr($match, $filedir_app_url_begin, $filedir_app_url_end - $filedir_app_url_begin);
		if(empty($filedir_app_url)) return null;
		$filedir_app_urls[] = $filedir_app_url;
	}

	if(sizeof($app_names) != FILEDIR_APPS_PER_PAGE ||
		sizeof($developers) != FILEDIR_APPS_PER_PAGE ||
		sizeof($filedir_app_urls) != FILEDIR_APPS_PER_PAGE)
		return null;

	$apps_data = array();
	for ($i = 0; $i < FILEDIR_APPS_PER_PAGE; $i++)
	{ 
		$apps_data[] = array(
			'app_name' => $app_names[$i],
			'developer' => $developers[$i],
			'filedir_app_url' => $filedir_app_urls[$i],
		);
	}

	return $apps_data;
}

/* scrape_filedir_app */

function scrape_filedir_app($content, $url, &$tasks)
{
	$googleplay_app_url = parse_filedir_app_data($content);
	
	if($googleplay_app_url == null)
		return array(
				'parse_error' => true,
				'error_data' => array(
					'callback' => 'scrape_filedir_app',
					'content' => $content,
				),
			);

	add_task_at_front(array(
				'url' => $googleplay_app_url . '&hl=en',
				'curl_opts' => 'set_googleplay_app_curl_opts',
				'callback' => 'scrape_googleplay_app',
			), $tasks);

	DB::update('t_filedir_apps', array(
		'googleplay_app_url' => $googleplay_app_url,
	), 'filedir_app_url=%s', $url);

	return array('parse_error' => false);
}

function parse_filedir_app_data($content)
{
	$googleplay_app_url_begin = strpos($content, 'https://play.google.com/store/apps/details?id=');
	$googleplay_app_url = substr($content, $googleplay_app_url_begin, 400);
	$googleplay_app_url_end = strpos($googleplay_app_url, '"');
	$googleplay_app_url = substr($googleplay_app_url, 0, $googleplay_app_url_end);

	if(empty($googleplay_app_url)) return null;

	return $googleplay_app_url;
}

/* scrape_googleplay_app */

function scrape_googleplay_app($content, $url, &$tasks)
{
	$app_data = parse_googleplay_app_data($content);
	
	if($app_data == null) return array(
								'parse_error' => true,
								'error_data' => array(
									'callback' => 'scrape_googleplay_app',
									'content' => $content,
								),
							);

	DB::insertUpdate('t_googleplay_apps', $app_data);
	
	return array('parse_error' => false);
}

define('DB_DATA_MISSING_KEYWORD', '*** missing value ****');

function parse_googleplay_app_data($content)
{
	$content = mb_convert_encoding($content, 'html-entities', 'utf-8');

	$dom = new DOMDocument;
	@$dom->loadHTML($content);
	$finder = new DomXPath($dom);

	$missing_values = 0;

	$id = _item(_query($finder, "//meta[@itemprop='url' and starts-with(@content, 'https://play.google.com/store/apps/details?id=')]"), 0);
	$id = _getAttribute($id, 'content');
	$id = substr($id, strlen('https://play.google.com/store/apps/details?id='));
	if(data_missing($id)) { return null; }

	$app_name = _item(_query($finder, "//div[@class='document-title']"), 0);
	$app_name = trim(_nodeValue($app_name));
	if(data_missing($app_name)) { $app_name = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$date_released = _item(_query($finder, "//div[@class='document-subtitle']"), 0);
	$date_released = _nodeValue($date_released);
	$date_released = substr($date_released, 2);
	if(data_missing($date_released)) { $date_released = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$developer = _item(_query($finder, "//a[@class='document-subtitle primary']"), 0);
	$developer = trim(_nodeValue($developer));
	if(data_missing($developer)) { $developer = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$total_score = _item(_query($finder, "//div[@class='score']"), 0);
	$total_score = trim(_nodeValue($total_score));
	if(data_missing($total_score)) { $total_score = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$total_num_ratings = _item(_query($finder, "//span[@class='reviews-num']"), 0);
	$total_num_ratings = trim(_nodeValue($total_num_ratings));
	if(data_missing($total_num_ratings)) { $total_num_ratings = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$partial_num_ratings_5 = _item(_query($finder, "//div[@class='rating-bar-container five'] /span[@class='bar-number']"), 0);
	$partial_num_ratings_5 = trim(_nodeValue($partial_num_ratings_5));
	if(data_missing($partial_num_ratings_5)) { $partial_num_ratings_5 = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$partial_num_ratings_4 = _item(_query($finder, "//div[@class='rating-bar-container four'] /span[@class='bar-number']"), 0);
	$partial_num_ratings_4 = trim(_nodeValue($partial_num_ratings_4));
	if(data_missing($partial_num_ratings_4)) { $partial_num_ratings_4 = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$partial_num_ratings_3 = _item(_query($finder, "//div[@class='rating-bar-container three'] /span[@class='bar-number']"), 0);
	$partial_num_ratings_3 = trim(_nodeValue($partial_num_ratings_3));
	if(data_missing($partial_num_ratings_3)) { $partial_num_ratings_3 = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$partial_num_ratings_2 = _item(_query($finder, "//div[@class='rating-bar-container two'] /span[@class='bar-number']"), 0);
	$partial_num_ratings_2 = trim(_nodeValue($partial_num_ratings_2));
	if(data_missing($partial_num_ratings_2)) { $partial_num_ratings_2 = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$partial_num_ratings_1 = _item(_query($finder, "//div[@class='rating-bar-container one'] /span[@class='bar-number']"), 0);
	$partial_num_ratings_1 = trim(_nodeValue($partial_num_ratings_1));
	if(data_missing($partial_num_ratings_1)) { $partial_num_ratings_1 = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$description = _item(_query($finder, "//div[@class='app-orig-desc']"), 0);
	$description = $dom->saveHtml($description);
	$description = substr($description, strlen('<div class="app-orig-desc">'));
	$description = substr($description, 0, strlen($description) - strlen("</div>\n"));
	if(data_missing($description)) { $description = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	// $reviews = array();
	// $featured_reviews_nodes = $finder->query("//div[contains(@class, 'featured-review') and @data-reviewid]");
	// foreach ($featured_reviews_nodes as $review)
	// 	$reviews[] = $dom->saveHtml($review);
	// $single_reviews_nodes = $finder->query("//div[contains(@class, 'single-review')]");
	// foreach ($single_reviews_nodes as $review)
	// 	$reviews[] = $dom->saveHtml($review);
	// $reviews = serialize($reviews);

	$details = _item(_query($finder, "//div[@class='details-section metadata']"), 0);

	$last_updated = _item(_query($finder, ".//div[@itemprop='datePublished']", $details), 0);
	$last_updated = trim(_nodeValue($last_updated));
	if(data_missing($last_updated)) { $last_updated = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$app_size = _item(_query($finder, ".//div[@itemprop='fileSize']", $details), 0);
	$app_size = trim(_nodeValue($app_size));
	if(data_missing($app_size)) { $app_size = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$installs = _item(_query($finder, ".//div[@itemprop='numDownloads']", $details), 0);
	$installs = trim(_nodeValue($installs));
	if(data_missing($installs)) { $installs = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$version = _item(_query($finder, ".//div[@itemprop='softwareVersion']", $details), 0);
	$version = trim(_nodeValue($version));
	if(data_missing($version)) { $version = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$requires_version = _item(_query($finder, ".//div[@itemprop='operatingSystems']", $details), 0);
	$requires_version = trim(_nodeValue($requires_version));
	if(data_missing($requires_version)) { $requires_version = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$content_rating = _item(_query($finder, ".//div[@itemprop='contentRating']", $details), 0);
	$content_rating = trim(_nodeValue($content_rating));
	if(data_missing($content_rating)) { $content_rating = DB_DATA_MISSING_KEYWORD; $missing_values++; }

	$developer_links_nodes = $finder->query(".//a[@class='dev-link']", $details);
	$developer_links = array();
	foreach ($developer_links_nodes as $developer_link)
		$developer_links[] = $dom->saveHtml($developer_link);
	$developer_links = serialize($developer_links);

	if($missing_values > 5) return null;

	$app_data = array(
		'id' => $id,
		'app_name' => $app_name,
		'date_released' => $date_released,
		'developer' => $developer,
		'total_score' => $total_score,
		'total_num_ratings' => $total_num_ratings,
		'partial_num_ratings_5' => $partial_num_ratings_5,
		'partial_num_ratings_4' => $partial_num_ratings_4,
		'partial_num_ratings_3' => $partial_num_ratings_3,
		'partial_num_ratings_2' => $partial_num_ratings_2,
		'partial_num_ratings_1' => $partial_num_ratings_1,
		'description' => $description,
		// 'reviews' => $reviews,
		'last_updated' => $last_updated,
		'app_size' => $app_size,
		'installs' => $installs,
		'version' => $version,
		'requires_version' => $requires_version,
		'content_rating' => $content_rating,
		'developer_links' => $developer_links,
	);

	return $app_data;
}

function _query(&$finder, $str, $context=null)
{
	if(is_null($finder)) return null;
	else if(is_null($context))
		return $finder->query($str);
	else return $finder->query($str, $context);
}

function _item(&$nodeList, $index)
{
	if(is_null($nodeList) || $nodeList->length == 0) return null;
	else return $nodeList->item($index);
}

function _getAttribute(&$node, $attr)
{
	if(is_null($node)) return null;
	else return $node->getAttribute($attr);
}

function _nodeValue(&$node)
{
	if(is_null($node)) return null;
	else return $node->nodeValue;
}

function data_missing($value)
{
	return (is_null($value) || $value === false || $value === '');
}