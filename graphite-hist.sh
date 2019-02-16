#!/bin/bash

file=$1

tail -n +4 $file | head -5 | awk '{print $2" "$1}' | sed -e 's/S/s/' -e 's/\./-/g' -e 's,/,.,' -e "s/$/ $(date --date="$(head -n 1 $file)" +%s)/"
