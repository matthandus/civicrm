#!/bin/bash
export PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin:/app/vendor/bin
dir=`pwd`
arg=""
for i in "$@"; do
  if [[ $i == --stdin-path* ]]; then
    find="--stdin-path=${dir}"
    replace="--extensions=php,module,inc,install,test,profile,theme,css,info,txt /app"
    result=${i//$find/$replace}
    i=$result
  fi
  if [[ $i != "-" ]]; then
    arg+=" "$i
  fi
done
#echo -e $arg >> phpcs-arg.txt
#echo -e $dir >> phpcs-arg.txt
stdout="$(lando phpcs $arg)"
if [[ $stdout == PHP_CodeSniffer* ]]; then
  #echo "${stdout}" >> phpcs-stdout.txt
  echo "${stdout}"
else
  #echo "${stdout}" >> phpcs-stdout.txt
  str="\/app"
  appdir=${dir//"/"/"\/"}
  swap=${appdir:1}
  json=${stdout//$str/$swap}
  #echo $json >> phpcs-stdout.txt
  echo $json
fi
