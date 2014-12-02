#!/bin/bash

if [[ $UID != 0 ]]; then
    echo ">> Please, run this script with sudo:"
    echo "	sudo $0 $*"
    exit 1
fi
echo ""
echo ""

echo ">> Checking for missing dependencies."
apt-get install mysql-client mysql-server php5 php5-mysql php5-curl
if [ "$?" -ne "0" ]; then
  echo "*** Dependecy check failed. Finishing..."
  exit 1
fi
initctl reload-configuration
echo ""
echo ""

echo ">> Starting mysql service."
service mysql restart
if [ "$?" -ne "0" ]; then
  echo "*** MySQL server start up failed. Finishing..."
  exit 1
fi
echo ""
echo ""

echo ">> Setting up database."
printf "Please, enter your mysql administrator username (usually \"root\"): "
read usermysql
mysql -u $usermysql -p < ./data/db_scraple_empty.sql
if [ "$?" -ne "0" ]; then
  echo "*** Database setup failed. Finishing..."
  exit 1
fi
echo ">> Database setup properly; the database name is db_scraple and it can be accessed by user 'scraplemysql', password 'scraple123456'"
echo ""
echo ""

echo ">> Setting up cron job. You will need to enter the job's frequency description; examples:"
echo "	*/1 * * * *			; run every one minute"
echo "	*/10 * * * *			; run every ten minutes"
echo "	*/30 * * * *			; run every thirty minutes"
echo "	0 * * * *			; run every one hour"
echo "	0 0 * * * 			; run every one day"
echo "	@reboot 			; run at start up"
echo "(more details: http://www.thegeekstuff.com/2009/06/15-practical-crontab-examples/)"
echo ""
printf "Please enter frequency decription: "
read FREQUENCY_DESCRIPTOR
misc/cron_job_maker.sh $SUDO_USER `pwd` "$FREQUENCY_DESCRIPTOR"
mv scraple /etc/cron.d/
if [ "$?" -ne "0" ]; then
  echo "*** Moving cron job to /etc/cron.d failed. Finishing..."
  exit 1
fi
service cron restart
echo ">> Cron job created in /etc/cron.d/scraple."
echo ""
echo ""

echo ">> Setting up directory structure."
echo ""
echo ""
mkdir -p log
mkdir -p log/dumps
touch log/log.txt
chown $SUDO_USER:$SUDO_USER log
chown $SUDO_USER:$SUDO_USER log/dumps
chown $SUDO_USER:$SUDO_USER log/log.txt

echo ">> Scraple is ready to run; you may modify the configuration from config.php, and curl options from config_curl_opts.php ."
echo ""
echo ""