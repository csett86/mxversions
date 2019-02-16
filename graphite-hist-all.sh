#!/bin/bash

files=$@

for file in $files; do
	./graphite-hist.sh $file
done
