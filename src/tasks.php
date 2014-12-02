<?php 

ini_set('include_path', dirname(dirname(__FILE__)));
require_once 'config.php';

define('TASKS_DATA_FILE_PATH', BASE_DIRECTORY.'data'.DIRECTORY_SEPARATOR.'tasks.bin');
define('MAX_ALLOWED_ERRORS_PER_TASK', 5);

function &load_tasks()
{
	static $tasks;

	if(file_exists(TASKS_DATA_FILE_PATH))
	{
		$tasks = unserialize(file_get_contents(TASKS_DATA_FILE_PATH));
		foreach($tasks['in_process'] as $url => $task)
			reinsert_task_by_url($url, $tasks);

	} else {
		
		$tasks = array(
			'pending' => array(),
			'in_process' => array(),
		);
	}

	/* needs to have global alias in case execution ends abruptly */
	$GLOBALS['tasks'] = &$tasks;
	$GLOBALS['tasks']['pending'] = &$tasks['pending'];
	$GLOBALS['tasks']['in_process'] = &$tasks['in_process'];

	return $tasks;
}

function save_tasks(&$tasks)
{
	file_put_contents(TASKS_DATA_FILE_PATH, serialize($tasks));
}

function has_next_task(&$tasks)
{
	return sizeof($tasks['pending']) > 0;
}

function add_task($task, &$tasks)
{
	$url = $task['url'];
	unset($task['url']);
	$task['num_errors'] = 0;
	$tasks['pending'][$url] = $task;
}

function add_task_at_front($task, &$tasks)
{
	$url = $task['url'];
	unset($task['url']);
	$task['num_errors'] = 0;
	$tasks['pending'] = array($url => $task) + $tasks['pending'];
}

function get_next_task(&$tasks)
{
	foreach ($tasks['pending'] as $url => $task) break;
																																	unset($tasks['pending'][$url]);
	$tasks['in_process'][$url] = $task;

	$task['url'] = $url;
	return $task;
}

function get_next_task_random(&$tasks)
{
	$url = array_rand($tasks['pending']);
	$task = $tasks['pending'][$url];

	unset($tasks['pending'][$url]);
	$tasks['in_process'][$url] = $task;

	$task['url'] = $url;
	return $task;
}

function get_task_callback_by_url($url, &$tasks)
{
	return $tasks['in_process'][$url]['callback'];
}

function delete_task_by_url($url, &$tasks)
{
	if(isset($tasks['pending'][$url]))
		unset($tasks['pending'][$url]);
	else if(isset($tasks['in_process'][$url]))
		unset($tasks['in_process'][$url]);
}

function reinsert_task_by_url($url, &$tasks)
{
	$task = $tasks['in_process'][$url];

	unset($tasks['in_process'][$url]);
	$tasks['pending'][$url] = $task;
}

function increase_task_num_errors_by_url($url, &$tasks)
{
	$num_errors = ++$tasks['in_process'][$url]['num_errors'];

	return $num_errors > MAX_ALLOWED_ERRORS_PER_TASK;
}

function cancel_and_save_all_tasks()
{
	foreach ($GLOBALS['tasks']['in_process'] as $url => $task)
		reinsert_task_by_url($url, $GLOBALS['tasks']);

	save_tasks($GLOBALS['tasks']);
}