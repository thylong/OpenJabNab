#!/bin/bash

CURRENTDIR=$(realpath $(dirname $0))
COMPILER="$CURRENTDIR/../../utils/mtl_compiler"
PREPROC="$CURRENTDIR/preproc.pl"
PREPROC2="$CURRENTDIR/preproc_remove_extra_protos.pl"

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_classic.mtl | "$PREPROC2" > "$CURRENTDIR/bootcode.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode.mtl" ; exit; }

"$COMPILER" -s "bootcode.mtl" "bootcode.bin"
#cp bootcode.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.ojn.noreboot

