#!/bin/sh -e

total="$( sqlite3 scanner.db 'select count() from versions where datetime( last_time ) > datetime( "now", "-1 day" )' )"

__report() {

date -R
echo "$total homeservers online"
echo

sqlite3 scanner.db \
'select
    distinct( software || "/" || version ) as v,
    count() as c
from
    versions
where
    datetime( last_time ) > datetime( "now", "-1 day" )
group by v
order by c desc, v asc' \
| awk -F'|' '{print $2"|"$1}' \
| column -t -s '|'

}

__report

