#!/bin/bash
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sh indentexpr= :vim

TAG=$1
TAGFORCE=$2

if [ -z "$TAG" ]; then
	echo "Kein Tag mit angegeben"
	exit
fi

sed -i -e "s/^\\(Stable tag:\\).\+/\1 $TAG/" README.markdown
sed -i -e "s/^\\(Version:\\).\+/\1 $TAG/" gpx2chart.php
sed -i -e "s/^\\(GPX2CHART_PLUGIN_VER',)'.\+'/\1'$TAG'/" gpx2chart.php

git commit -a -m "Prepare to tag $TAG"
git push


git tag $TAGFORCE -a $TAG -m "Tag to $TAG"
git push --tags

#./updatewordpress.sh $TAG

