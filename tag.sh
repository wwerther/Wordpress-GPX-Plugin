#!/bin/bash
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sh indentexpr= :vim

TAG=$1

if [ -z "$TAG" ]; then
	echo "Kein Tag mit angegeben"
	exit
fi

sed -i -e "s/^\\(Stable tag:\\).\+/\1 $TAG/" README.markdown

git commit -a -m "Prepare to tag $TAG"
git tag -a $TAG -m "Tag to $TAG"

./updatewordpress.sh $TAG
