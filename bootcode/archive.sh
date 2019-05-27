#!/bin/sh
REV=`rgrep "Rev:" sources/main.mtl | cut -d" " -f5 | sort | head -n1`
tar -czf bootcode_$REV.tar.gz sources
