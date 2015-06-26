#! /bin/bash
echo "Hi"

mkdir /var/log/seedsync/

echo "Adding cron entries"

crontab -l > currentCron

echo "#_________SeedSync__Start________" >> currentCron
echo "0,15,30,45 * * * * /usr/bin/php /seedsync/seedsync.php action check >> /var/log/seedsync/check.log" >> currentCron
echo "* * * * * /usr/bin/php /seedsync/seedsync.php action calendar >> /var/log/seedsync/calendar.log" >> currentCron
echo "* * * * * /usr/bin/php /seedsync/seedsync.php action download >> /var/log/seedsync/download.log" >> currentCron
echo "* * * * * /usr/bin/php /seedsync/seedsync.php action resume >> /var/log/seedsync/resume.log" >> currentCron
echo "* * * * * /usr/bin/php /seedsync/seedsync.php action pause >> /var/log/seedsync/pause.log" >> currentCron
echo "#_________SeedSync__End_________" >> currentCron

#install new cron file
crontab currentCron
rm currentCron

echo "Cron entries added"

echo "Add logrotate rule"

ln -s /seedsync/setup/logrotate.d/seedsync /etc/logrotate.d/