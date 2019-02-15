#!/bin/sh

destinationfile=/tmp/destinations.txt
reportfile=reports/report-$(date --iso-8601=seconds).txt

while [ 1 ]; do

cd /tmp

/root/mxversions/destinations.sh > $destinationfile

cd /root/mxversions

php scanner.php $destinationfile

./report.sh > $reportfile

cp $reportfile /var/www/html/mxversions.txt

done
