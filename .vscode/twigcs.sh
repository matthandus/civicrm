#!/bin/bash
export PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin:/app/vendor/bin
input=$1
#echo -e "Input $input" >> phpcs-log.txt
dir=`pwd`
#echo -e "Directory $dir" >> phpcs-log.txt
app="/app"
path=${input//$dir/$app}
#echo -e "Replaced $path" >> phpcs-log.txt
output="$(lando twigcs $path)"
#echo "Output $output" >> phpcs-log.txt
echo "/$output"
