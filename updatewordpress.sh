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

echo "Copy PHP-Source"
cp -v *php wpsvn/trunk

echo "Compile JS-Libs"
make -C js/helper
make -C jsflot

echo "Copy Javascript"
cp -v -r js wpsvn/trunk
echo "Copy CSS"
cp -v -r css wpsvn/trunk
echo "Copy Render"
cp -v -r render wpsvn/trunk
echo "Copy Test-Files"
cp -v -r test wpsvn/trunk
echo "Copy Screenshots"
cp -v screenshots/* wpsvn/trunk
echo "Updating Readme-File"
./readme2wordpress.sh > wpsvn/trunk/readme.txt
echo "Cleaning env"
find wpsvn/ -name "*~" | xargs rm
find wpsvn/ -name ".git*" | xargs rm -r
find wpsvn/ -name "examples" | xargs rm -r
cd wpsvn
echo "Creating Tag $TAG"
svn cp trunk tags/$TAG
echo "Commiting and uploading SVN"
svn ci -m "tagging with version $TAG"
