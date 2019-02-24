# mxversions

Gather all known home servers with their versions numbers from one home server, plotting them over time with Graphite.

Live Demo: https://graph.settgast.org/d/z1nplqXik/matrix?orgId=1

Plotting is done with Grafana.

For an example, see reportloop.sh or graphiteloop.sh which both can be used in a simple systemd unit like mxversions.service.

All the actual version collection is from https://github.com/4nd3r/matrix-tools, only the graphite stuff is new.
