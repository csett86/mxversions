# mxversions

gather all known homeservers from a matrix server and collect version numbers.

Graphing can be done via graphite.sh

For an example, see reportloop.sh or graphiteloop.sh which both can be used in a simple systemd unit like mxversions.service.

All the actual version collection is from https://github.com/4nd3r/matrix-tools, only the graphite stuff is new.
