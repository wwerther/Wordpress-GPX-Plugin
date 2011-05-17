#!/bin/bash
# vim: set ts=4 et nu ai ff=unix backupcopy=yes syntax=sh indentexpr= :vim

TAG=$1

if [ -z "$TAG" ]; then
	echo "Kein Tag mit angegeben"
	exit
fi

echo "Copy PHP-Source"
cp -v *php wpsvn/trunk
echo "Copy Javascript"
cp -v -r js wpsvn/trunk
echo "Copy Test-Files"
cp -v -r test wpsvn/trunk
echo "Copy Screenshots"
cp -v screenshots/* wpsvn/trunk
echo "Updating Readme-File"
./readme2wordpress.sh > wpsvn/trunk/Readme.txt
cd wpsvn
echo "Creating Tag $TAG"
svn cp trunk tags/$TAG
echo "Commiting and uploading SVN"
svn ci -m "tagging with version $TAG"
