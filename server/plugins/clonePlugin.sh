#!/bin/bash

if [ $# -ne 2 ]; then
  echo "Usage: ./`basename $0` oldPluginName newPluginName"
  exit 65
fi

templateLower=`echo $1 | tr [:upper:] [:lower:]`
templateClassName="Plugin"`echo ${1:0:1} | tr [:lower:] [:upper:]`${1:1:${#1}};
templateClassNameUpper=`echo $templateClassName | tr [:lower:] [:upper:]`;

pluginLower=`echo $2 | tr [:upper:] [:lower:]`
pluginClassName="Plugin"`echo ${2:0:1} | tr [:lower:] [:upper:]`${2:1:${#1}};
pluginClassNameUpper=`echo $pluginClassName | tr [:lower:] [:upper:]`;

echo "Creating '$pluginClassName' from '$templateLower' in '$pluginLower' folder ..."

if [ -d $pluginLower ]; then
  echo "Folder already exists"
  exit -1
fi

mkdir $pluginLower

cp $templateLower/plugin_$templateLower.cpp $pluginLower/plugin_$pluginLower.cpp
cp $templateLower/plugin_$templateLower.h $pluginLower/plugin_$pluginLower.h
cp $templateLower/$templateLower.pro $pluginLower/$pluginLower.pro

sed -i -s "s/$templateClassNameUpper/$pluginClassNameUpper/g" $pluginLower/*
sed -i -s "s/$templateClassName/$pluginClassName/g" $pluginLower/*
sed -i -s "s/$templateLower/$pluginLower/g" $pluginLower/*

rm -rf $pluginLower/tmp
