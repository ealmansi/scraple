<?php 

ini_set('include_path', dirname(dirname(__FILE__)));
require_once 'config.php';

define('LOG_FILE_PATH', BASE_DIRECTORY.'log'.DIRECTORY_SEPARATOR.'log.txt');
define('LOG_DUMPS_DIR', BASE_DIRECTORY.'log'.DIRECTORY_SEPARATOR.'dumps'.DIRECTORY_SEPARATOR);

function log_script_beginning()
{
	$log_entry = "Scraple begins running. " . date('l jS \of F Y h:i:s A') . PHP_EOL;
	file_put_contents(LOG_FILE_PATH, $log_entry, FILE_APPEND);
}

function log_script_ending_abruptly()
{
	$log_entry = "Scraple needs to end abruptly. Cancelling and saving all pending tasks. " . date('l jS \of F Y h:i:s A') . PHP_EOL;
	file_put_contents(LOG_FILE_PATH, $log_entry, FILE_APPEND);
}

function log_multi_exec_error($multi_exec_ret)
{
	log_error(array(
		'msg' => 'Curl multi error (curl_multi_exec did not return CURLM_OK). Dumping curl_multi_exec return value.',
		'error_dump' => $multi_exec_ret,
	));
}

function log_worker_error($worker_info, $url, $failed_too_many_times)
{
	$msg = "Worker HTTP error (status code not 200 OK) [$url]. Dumping curl_getinfo return value.";
	if($failed_too_many_times) $msg .= " Notice: this url has failed too many times, it will be removed from queue.";

	log_error(array(
		'msg' => $msg,
		'error_dump' => $worker_info,
	));
}

function log_content_error($error_data, $url, $failed_too_many_times)
{
	$msg = "Parse error while executing '".$error_data['callback']."' [$url]. Dumping content received.";
	if($failed_too_many_times) $msg .= " Notice: this url has failed too many times, it will be removed from queue.";

	log_error(array(
		'msg' => $msg,
		'error_dump' => $error_data['content'],
	));
}

function log_error($error_data)
{
	if(get_dump_directory_size() < Config('MAX_ERROR_DUMP_DIR_SIZE_MB')*1024*1024)
	{
		$serialized_data = serialize($error_data['error_dump']);
		$error_dump_path = LOG_DUMPS_DIR.'error_'.md5($serialized_data).'_'.time().'.bin';
		file_put_contents($error_dump_path, $serialized_data);
	
		$log_entry = $error_data['msg'] . " | Dump file path: " . $error_dump_path . "\n";
		file_put_contents(LOG_FILE_PATH, $log_entry, FILE_APPEND);

	} else {
	
		$log_entry = $error_data['msg'] . " | Could not dump error data because the dump directory has exceeded Config('MAX_ERROR_DUMP_DIR_SIZE_MB').\n";
		file_put_contents(LOG_FILE_PATH, $log_entry, FILE_APPEND);
	}

	if(Config('VERBOSE')) echo($log_entry);
}

function get_dump_directory_size()
{
	$io = popen('/usr/bin/du -sb '. LOG_DUMPS_DIR, 'r');
	$dump_dir_size = intval(fgets($io,80));
	pclose($io);
	return $dump_dir_size;
}