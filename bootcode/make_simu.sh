#!/bin/bash

CURRENTDIR=$(dirname $0)
COMPILER="./compiler/mtl_linux/mtl_comp"
PREPROC="../preproc.pl"
PREPROC2="../preproc_remove_extra_protos.pl"

(
	cd $CURRENTDIR/sources_simu
	"$PREPROC" < main.mtl | "$PREPROC2" > "../bootcode_simu.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode.mtl" ; exit; }

"$COMPILER" -s "bootcode_simu.mtl" "bootcode_simu.bin"
