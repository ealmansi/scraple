<?php 

ini_set('include_path', dirname(__FILE__));
require_once 'config.php';
require_once 'src/user_agents.php';

function set_filedir_directory_curl_opts(&$ch)
{
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	curl_setopt($ch, CURLOPT_PROXY, get_proxy_mesh_ip());
	curl_setopt($ch, CURLOPT_USERPWD, get_proxy_mesh_auth());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Host: filedir.com',
		'Connection: keep-alive',
		'Cache-Control: max-age=0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'User-Agent: ' . get_random_user_agent(),
		'Referer: http://filedir.com/',
		'Accept-Language: en-US,en;q=0.8',
		"Content-Type: text/html; charset=utf-8"
	));
}

function set_filedir_page_curl_opts(&$ch)
{
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	curl_setopt($ch, CURLOPT_PROXY, get_proxy_mesh_ip());
	curl_setopt($ch, CURLOPT_USERPWD, get_proxy_mesh_auth());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Host: filedir.com',
		'Connection: keep-alive',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'User-Agent: ' . get_random_user_agent(),
		'Referer: http://filedir.com/android/',
		'Accept-Language: en-US,en;q=0.8',
		"Content-Type: text/html; charset=utf-8"
	));
}

function set_filedir_app_curl_opts(&$ch)
{
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	curl_setopt($ch, CURLOPT_PROXY, get_proxy_mesh_ip());
	curl_setopt($ch, CURLOPT_USERPWD, get_proxy_mesh_auth());
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Host: filedir.com',
		'Connection: keep-alive',
		'Cache-Control: max-age=0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'User-Agent: ' . get_random_user_agent(),
		'Accept-Language: en-US,en;q=0.8',
		"Content-Type: text/html; charset=utf-8"
	));
}

function set_googleplay_app_curl_opts(&$ch)
{
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	// curl_setopt($ch, CURLOPT_PROXY, 'proxy_ip');
	// curl_setopt($ch, CURLOPT_USERPWD, 'user:password');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'accept-language: en-US,en;q=0.8',
		'user-agent: ' . get_random_user_agent(),
		'x-chrome-uma-enabled: 1',
		'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'cache-control: max-age=0',
		"content-type: text/html; charset=utf-8"
	));
}

/* auxilliaries */

function get_proxy_mesh_ip()
{
	$proxies = array(
					'us.proxymesh.com:31280',
					'uk.proxymesh.com:31280',
					// 'us-il.proxymesh.com:31280',
					// 'open.proxymesh.com:31280',
				);

	return $proxies[rand(0, count($proxies) - 1)];
}

function get_proxy_mesh_auth()
{
	return 'test1x:trial123';
}