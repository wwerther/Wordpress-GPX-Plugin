#!/bin/bash
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sh indentexpr= :vim

TAG=$1
TAGFORCE=$2

if [ -z "$TAG" ]; then
	echo "Kein Tag mit angegeben"
	exit
fi

sed -i -e "s/^\\(Stable tag:\\).\+/\1 $TAG/" README.markdown
sed -i -e "s/^\\(Version:\\).\+/\1 $TAG/" ww_gpx_infos.php

git commit -a -m "Prepare to tag $TAG"
git push


git tag $TAGFORCE -a $TAG -m "Tag to $TAG"
git push --tags

./updatewordpress.sh $TAG

