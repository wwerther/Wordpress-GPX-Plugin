#!/bin/bash
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sh indentexpr= :vim

TAG=$1

if [ -z "$TAG" ]; then
	echo "Kein Tag mit angegeben"
	exit
fi

if [ ! -e "wpsvn" ]; then
	echo "Creating SVN-Enviroment"
	mkdir wpsvn
	svn co http://wwerther@svn.wp-plugins.org/gpx2chart wpsvn
	mkdir wpsvn/trunk
	mkdir wpsvn/tags
fi

echo "Create new staging"
./stage.sh

echo "remove all from trunk"
find wpsvn/trunk/ | grep -v '/\.' | xargs rm

echo "Copy staging to trunk"
cp -v -r stage/* wpsvn/trunk

#cd wpsvn
#echo "Creating Tag $TAG"
#svn cp trunk tags/$TAG
#echo "Commiting and uploading SVN"
#svn ci -m "tagging with version $TAG"
