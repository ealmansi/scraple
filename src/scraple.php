<?php 

ini_set('include_path', dirname(dirname(__FILE__)));
require_once 'config.php';
require_once 'config_curl_opts.php';
require_once 'src/tasks.php';
require_once 'src/callbacks.php';
require_once 'src/logging.php';

function main()
{
	if(scraple_is_running())
		exit;

	lock_scraple();

	log_script_beginning();

	$tasks = load_tasks();

	if(!has_next_task($tasks))
	{
		add_task(array(
					'url' => 'http://filedir.com/android/',
					'curl_opts' => 'set_filedir_directory_curl_opts',
					'callback' => 'scrape_filedir_directory',
				), $tasks);
		add_task(array(
					'url' => 'http://filedir.com/android-games/',
					'curl_opts' => 'set_filedir_directory_curl_opts',
					'callback' => 'scrape_filedir_directory',
				), $tasks);
	}	

	$manager = curl_multi_init();
	$active_workers = 0;

	while(has_next_task($tasks) && $active_workers < Config('NUM_WORKERS'))
		assign_next_task_to_worker($manager, $tasks, $active_workers, $still_running);

	$consecutive_request_errors = 0;
	$still_running = null;
	do
	{
		$multi_exec_ret = wait_for_network_activity($manager, $still_running);

		if($multi_exec_ret != CURLM_OK)
		{
			echo_verbose("Logging: fatal error (something wrong with curl_multi_exec). Script stopping.");

			log_multi_exec_error($multi_exec_ret);
			die_gracefully();
		}

		$worker = null;
		while(worker_finished_task($manager, $worker))
		{
		    $worker_info = curl_getinfo($worker);

		    $url = $worker_info['url'];
		    $http_code = $worker_info['http_code'];

			if($http_code == 200)
		    {
		        echo_verbose("Request completed successfully ($url).");

		        $content = curl_multi_getcontent($worker);
				$callback = get_task_callback_by_url($url, $tasks);

		        $callback_status = $callback($content, $url, $tasks);

		        if($callback_status['parse_error'])
		        {
		        	echo_verbose("Logging: parse error ($url).");

		        	$failed_too_many_times = increase_task_num_errors_by_url($url, $tasks);

		        	if($failed_too_many_times)
		        		delete_task_by_url($url, $tasks);
		        	else
		        		reinsert_task_by_url($url, $tasks);
		        	
		        	$consecutive_request_errors++;

		        	log_content_error($callback_status['error_data'], $url, $failed_too_many_times);

		        } else {

		        	echo_verbose("Content parsed successfully ($url).");

		        	delete_task_by_url($url, $tasks);

		        	$consecutive_request_errors = 0;
		        }

		    } else {

		    	echo_verbose("Logging: request error ($url).");

		        $failed_too_many_times = increase_task_num_errors_by_url($url, $tasks);

		        if($failed_too_many_times)
		        	delete_task_by_url($url, $tasks);
		        else
		        	reinsert_task_by_url($url, $tasks);
		        
		        $consecutive_request_errors++;
		        
		        log_worker_error($worker_info, $url, $failed_too_many_times);
		    }

		    save_tasks($tasks);

		    release_worker($manager, $worker, $active_workers);
		}

		if(30 < $consecutive_request_errors)
		{
			echo_verbose("More than 30 consecutive request errors. Script stopping.");

			die_gracefully();

		} else if(20 < $consecutive_request_errors) {

			echo_verbose("More than 20 consecutive request errors. Adding 5 minute delay between requests until one succeeds.");

			sleep(5*60);
			if(has_next_task($tasks))
				assign_next_task_to_worker($manager, $tasks, $active_workers, $still_running);

		} else if(10 < $consecutive_request_errors) {

			echo_verbose("More than 10 consecutive request errors. Adding 1 minute delay between requests until one succeeds.");

			sleep(1*60);
			if(has_next_task($tasks))
				assign_next_task_to_worker($manager, $tasks, $active_workers, $still_running);

		} else {

			while(has_next_task($tasks) && $active_workers <= Config('NUM_WORKERS'))
				assign_next_task_to_worker($manager, $tasks, $active_workers, $still_running);
		}

	} while ($still_running);
	    
	curl_multi_close($manager);
	unlock_scraple();
}

/* main auxilliaries */

define('SCRAPLE_LOCK_FILE_PATH', BASE_DIRECTORY.'data'.DIRECTORY_SEPARATOR.'.lock');

function scraple_is_running()
{
	return file_exists(SCRAPLE_LOCK_FILE_PATH);
}

function lock_scraple()
{
	file_put_contents(SCRAPLE_LOCK_FILE_PATH, date('l jS \of F Y h:i:s A') . PHP_EOL);
}

function unlock_scraple()
{
	if(file_exists(SCRAPLE_LOCK_FILE_PATH))
		unlink(SCRAPLE_LOCK_FILE_PATH);
}

function create_worker($url, $set_curl_opts)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	$set_curl_opts($ch);

	return $ch;
}

function assign_next_task_to_worker(&$manager, &$tasks, &$active_workers, &$still_running)
{
	$task = get_next_task($tasks);
	$worker = create_worker($task['url'], $task['curl_opts']);
	curl_multi_add_handle($manager, $worker);

	$active_workers++;
	$still_running = true;

	usleep(rand(0, Config('AFTER_REQUEST_DELAY_MS') * 1000));
}

function release_worker(&$manager, &$worker, &$active_workers)
{
	curl_multi_remove_handle($manager, $worker);

	$active_workers--;
}

function wait_for_network_activity(&$manager, &$still_running)
{
	do
	{
		$multi_exec_ret = curl_multi_exec($manager, $still_running);

		usleep(20000);

	} while($multi_exec_ret == CURLM_CALL_MULTI_PERFORM);

	return $multi_exec_ret;
}

function worker_finished_task(&$manager, &$worker)
{
	$info = curl_multi_info_read($manager);

	if(isset($info['handle']))
	{
		$worker = $info['handle'];
		return true;
	}

	return false;
}

function die_gracefully()
{
	log_script_ending_abruptly();

	cancel_and_save_all_tasks();
	unlock_scraple();
	exit;
}

function echo_verbose($msg)
{
	if(Config('VERBOSE')) echo $msg."\n";
}

declare(ticks = 1);

pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");

function signal_handler($signal) {
    switch($signal) {
        case SIGTERM:
        case SIGINT:
        	echo("All tasks cancelled and saved before ending script...\n");
            die_gracefully();
    }
}

main();