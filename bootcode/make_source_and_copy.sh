#!/bin/bash

CURRENTDIR=$(dirname $0)
COMPILER="./compiler/mtl_linux/mtl_comp"
PREPROC="../preproc.pl"
PREPROC2="../preproc_remove_extra_protos.pl"

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_classic.mtl | "$PREPROC2" > "../bootcode.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode.mtl" ; exit; }

"$COMPILER" -s "bootcode.mtl" "bootcode.bin"
cp bootcode.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.ojn.noreboot

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_norfid.mtl | "$PREPROC2" > "../bootcode_norfid.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_norfid.mtl" ; exit; }

"$COMPILER" -s "bootcode_norfid.mtl" "bootcode_norfid.bin"
cp bootcode_norfid.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.norfid

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_debug.mtl | "$PREPROC2" > "../bootcode_debug.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_debug.mtl" ; exit; }

"$COMPILER" -s "bootcode_debug.mtl" "bootcode_debug.bin"
cp bootcode_debug.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.ojn.debug

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_reconf.mtl | "$PREPROC2" > "../bootcode_reconf.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_reconf.mtl" ; exit; }

"$COMPILER" -s "bootcode_reconf.mtl" "bootcode_reconf.bin"
cp bootcode_reconf.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.ojn.reconf

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_silent.mtl | "$PREPROC2" > "../bootcode_silent.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_silent.mtl" ; exit; }

"$COMPILER" -s "bootcode_silent.mtl" "bootcode_silent.bin"
cp bootcode_silent.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.silent

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_pixel.mtl | "$PREPROC2" > "../bootcode_pixel.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_pixel.mtl" ; exit; }

"$COMPILER" -s "bootcode_pixel.mtl" "bootcode_pixel.bin"
cp bootcode_pixel.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.pixel

(
	cd $CURRENTDIR/sources
	"$PREPROC" < main_beta.mtl | "$PREPROC2" > "../bootcode_beta.mtl"
)

[ $? -eq 0 ] || { echo "Could not make bootcode_beta.mtl" ; exit; }

"$COMPILER" -s "bootcode_beta.mtl" "bootcode_beta.bin"
cp bootcode_beta.bin /home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.beta

