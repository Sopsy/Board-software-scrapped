#!/bin/sh

CWD=$(cd $(dirname $0) ; pwd -P);
PROCESSES=4;

while true;
do
    I=0
    while [ "${I}" -lt "${PROCESSES}" ];
    do
        PROCESSCOUNT=`pgrep -f RunCommand.php | wc -l`
        if [ "${PROCESSCOUNT}" -ge "${PROCESSES}" ]; then
            exit 0
        fi
        I=`expr ${I} + 1`
        php -f "${CWD}/RunCommand.php" MessageListenerDaemon index true &
    done
done
