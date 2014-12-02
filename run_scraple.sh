#!/bin/bash

if [ $# -eq 0 ]
  then
    SCRAPLE_PATH="`pwd`/src/scraple.php"
  else
	SCRAPLE_PATH="$1/src/scraple.php"
fi

if [ `ps aux | grep $SCRAPLE_PATH | wc -l` == 1 ]; then
	php $SCRAPLE_PATH
else
	echo 'Scraple is already running! Running multiple instances is not supported.'
fi