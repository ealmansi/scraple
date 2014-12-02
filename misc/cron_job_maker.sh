JOB_USER=$1
PROJECT_BASE_DIR=$2
FREQ_DESCRIPTOR=$3

echo "# /etc/cron.d/scraple: crontab fragment for scraple" > scraple
echo "#" >> scraple
echo "#" >> scraple
echo "#" >> scraple
echo "" >> scraple
echo "SHELL=/bin/bash" >> scraple
echo "" >> scraple
echo "$FREQ_DESCRIPTOR $JOB_USER $PROJECT_BASE_DIR/run_scraple.sh $PROJECT_BASE_DIR" >> scraple