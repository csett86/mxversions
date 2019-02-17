#!/bin/bash

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
| sed -e 's/\./-/g' -e 's,/,.,' -e 's/$/ -1/' -e 's/|/ /' -e 's/S/s/'
