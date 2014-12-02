# Scraple: PHP + MySQL multithreaded scraper

## Installation

Open terminal: Ctrl + Shift + T

Navigate to the parent directory where you would like to store the project directory. For example:
 
	$ cd /home/emilio/Projects/

(Recommended for getting source code)
Install git:

	$ sudo apt-get install git

From Bitbucket website, within the repository's Overview section, get the repository's url for cloning (something like https://<username>@bitbucket.org/ealmansi/scraple.git). Clone repository:

	$ git clone https://<username>@bitbucket.org/ealmansi/scraple.git scraple

(Alternative for getting source code)
From Bitbucket website, within the repository's Downloads section, in subsection Branches (third tab), you may download a compressed file containing the project.

Once the source has been obtained, navigate to the project's root directory (for example, /home/emilio/Projects/scraple) and execute the following command:

	$ sudo ./setup_scraple.php

The installation script will install dependencies (if missing), create the database and set up the cron job. The same script may be ran later to modify the cron job configuration.

## Configuration

The script is configured by editing config.php and config_curl_opts.php files.

The file config.php contains parameters about the scraping speed (namely, how many threads will run simultaneously and the amount of milliseconds to wait between sending requests).

The file config_curl_opts.php defines the cURL options used for making requests; most significantly, the request headers, timeouts and proxy settings (if any). These options are defined independently for every type of webpage to be scraped, as different cURL options are needed for best results when scraping different sites. To identify what options are used for what kind of webpage, they are divided into functions with names following this format:

	set_<site>_<type-of-webpage>_curl_opts


For example:

* _set_filedir_directory_curl_opts_ defines options for scraping this kind of webpage: http://filedir.com/android/books-reference/

## Usage

The script will be called periodically by a cron job, as often as specified by the job's frequency descriptor which can be configured during setup. The script may also be executed by running:

	$ ./run_scraple.sh

When the option VERBOSE is set to 'false' in config.php, or when the script is ran automatically by the system, information on any network or parsing errors that may happen will be logged in log/log.txt, dumping any relevant data for diagnosis in a separate file whose path will be specified in the error's log entry (all dump files are located in log/dumps).

If the value of VERBOSE is set to 'true' in config.php, running the script manually will provide additional information about the program's execution.

## Ending execution

Scraple is prepared to handle SIGINT and SIGTEM signals; no data will be lost if execution is ended by these means. A script is provided to kill scaple gracefully:

	$ ./run_scraple.sh

---

Setup tested on a fresh install Ubuntu 12.04.