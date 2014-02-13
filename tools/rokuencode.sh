#!/bin/bash

renice +15 --pid $$

#convert mpeg file to mp4 using handbrakecli
MYTHDIR=$1
MPGFILE=$2

DATABASEUSER=mythtv
DATABASEPASSWORD=mythtv

newbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
newname="$MYTHDIR/$newbname.mp4"

/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname 

# update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE' WHERE basename='$2';" > /tmp/update-database.sql
mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg < /tmp/update-database.sql

# update the seek table
mythcommflag --file $newname --rebuild

# remove the orignal mpg
rm $MYTHDIR/$MPGFILE
