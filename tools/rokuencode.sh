#!/bin/bash

# Redirect stdout ( > ) into a named pipe ( >() ) running "tee"
exec > >(tee /tmp/rokuencode.log)

# Without this, only stdout would be captured - i.e. your
# log file would not contain any error messages.
# SEE answer by Adam Spiers, which keeps STDERR a seperate stream -
# I did not want to steal from him by simply adding his answer to mine.
exec 2>&1

set -x

renice +15 --pid $$

#convert mpeg file to mp4 using handbrakecli
MYTHDIR=$1
MPGFILE=$2

TOOLS=$(dirname $0)

if [ ! -f $TOOLS/rokuencode-settings ]; then
  echo "No such file: $TOOLS/rokuencode-settings"
  exit 1
fi

source $TOOLS/rokuencode-settings

if [ -f /usr/bin/ccextractor ]; then
  # extract subtitles
  srtbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
  srtname="$MYTHDIR/$srtbname.srt"

  # extract subtitles
  /usr/bin/ccextractor -90090 --fixpadding --nofontcolor -out=srt --sentencecap "$MYTHDIR/$MPGFILE" -o "$srtname"
fi

# create mp4
newbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
newname="$MYTHDIR/$newbname.mp4"

/usr/bin/HandBrakeCLI --preset="$HANDBRAKE_PRESET" -i $MYTHDIR/$MPGFILE -o $newname 

# update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE' WHERE basename='$2';" | mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg

# update the seek table
mythcommflag --file $newname --rebuild

biftool=$(which biftool)
ffmpeg=$(which ffmpeg)
if [ -f $biftool -a -f $ffmpeg ]; then
  # create bif trick files
  bifbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
  sdbifname="/var/tmp/${bifbname}_sd"
  hdbifname="/var/tmp/${bifbname}_hd"

  mkdir $sdbifname
  mkdir $hdbifname

  $ffmpeg -i "$MYTHDIR/$MPGFILE" -r .1 -s 240x180 "$sdbifname/%08d.jpg"
  $ffmpeg -i "$MYTHDIR/$MPGFILE" -r .1 -s 320x240 "$hdbifname/%08d.jpg"

  cd /var/tmp
  $biftool -t 10000 "$sdbifname"
  $biftool -t 10000 "$hdbifname"

  rm -rf "$sdbifname"
  rm -rf "$hdbifname"

  mv "$sdbifname.bif" $MYTHDIR
  mv "$hdbifname.bif" $MYTHDIR
fi

# remove the orignal mpg
rm $MYTHDIR/$MPGFILE
