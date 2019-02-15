#!/bin/sh

sudo -u matrix-synapse psql synapse -c 'select destination from destinations;' | tail -n +3 | head -n -2 | sed "s/^[ \t]*//"
