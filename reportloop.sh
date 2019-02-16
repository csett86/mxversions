#!/bin/sh

destinationfile=/tmp/destinations.txt

while [ 1 ]; do

cd /tmp

/root/mxversions/destinations.sh > $destinationfile

cd /root/mxversions

php scanner.php $destinationfile

reportfile=reports/report-$(date --iso-8601=seconds).txt

./report.sh > $reportfile

cp $reportfile /var/www/html/mxversions.txt

done
