#!/bin/sh

sqlite3 scanner.db \
'select
    distinct( software || "/" || version ) as v,
    count() as c
from
    versions
where
    datetime( last_time ) > datetime( "now", "-1 day" )
group by v
order by c desc, v asc 
limit 5' \
| sed 's/\./-/g' | sed 's/|/:/' | sed 's,/,.,' \
| xargs -n 1 -J % echo % "`date +%s`" | sed 's/:/ /' | sed 's/S/s/'