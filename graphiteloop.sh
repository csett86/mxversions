#!/bin/sh

destinationfile=/home/chris/mxversions/destinations.txt

while [ 1 ]; do

cd /home/chris/mxversions

# the actual destinations.sh is run remotely and set as command in the authorized_keys
ssh root@lin02.settgast.org > $destinationfile

php scanner.php $destinationfile

reportfile=reports/report-$(date --iso-8601=seconds).txt

./report.sh > $reportfile

cp $reportfile /var/www/html/mxversions.txt

./graphite.sh |nc -q0 localhost 3002

done
