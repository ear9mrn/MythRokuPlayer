#!/bin/bash

renice +15 --pid $$

#convert mpeg file to mp4 using handbrakecli
MYTHDIR=$1
MPGFILE=$2

DATABASEUSER=mythtv
DATABASEPASSWORD=mythtv

# extract subtitles
srtbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
srtname="$MYTHDIR/$srtbname.srt"

# extract subtitles
/usr/bin/ccextractor -90090 --fixpadding --nofontcolor -out=srt --sentencecap "$MYTHDIR/$MPGFILE" -o "$srtname"

# create mp4
newbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
newname="$MYTHDIR/$newbname.mp4"

/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname 

# update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE' WHERE basename='$2';" > /tmp/update-database.sql
mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg < /tmp/update-database.sql

# update the seek table
mythcommflag --file $newname --rebuild

# create bif trick files
bifbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
sdbifname="/tmp/${bifbname}_sd"
hdbifname="/tmp/${bifbname}_hd"

mkdir $sdbifname
mkdir $hdbifname

/usr/bin/ffmpeg -i "$MYTHDIR/$MPGFILE" -r .1 -s 240x180 "$sdbifname/%08d.jpg"
/usr/bin/ffmpeg -i "$MYTHDIR/$MPGFILE" -r .1 -s 320x240 "$hdbifname/%08d.jpg"

cd /tmp
/usr/bin/biftool -t 10000 "$sdbifname"
/usr/bin/biftool -t 10000 "$hdbifname"

rm -rf "$sdbifname"
rm -rf "$hdbifname"

mv "$sdbifname.bif" $MYTHDIR
mv "$hdbifname.bif" $MYTHDIR

# remove the orignal mpg
rm $MYTHDIR/$MPGFILE
