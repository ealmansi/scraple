<?php 

define('BASE_DIRECTORY', dirname(__FILE__) . DIRECTORY_SEPARATOR);

function Config($key)
{
	$config = array(
		
		/* database configuration */
		'DB_USER' 		=> 'scraplemysql',
		'DB_PASSWORD' 	=> 'scraple123456',
		'DB_NAME' 		=> 'db_scraple',
		'DB_ENCODING' 	=> 'utf8',

		/* number of threads and delay between requests */
		'NUM_WORKERS' 				=> 10,
		'AFTER_REQUEST_DELAY_MS' 	=> 300,

		/* system related settings */
		'VERBOSE'						=> true,
		'MAX_ERROR_DUMP_DIR_SIZE_MB' 	=> 300,
	);

	return $config[$key];
}
