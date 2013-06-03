#!/bin/bash

# Converts MPEG-2 files to MPEG-4 (H.264) using HandBradeCLI.

# To use this script to transcode your recordings you will need to add the
# following command to a user job (or transcode command):
#     <path_to_script>/rokuencode.sh "%DIR%" "%FILE%"
# For example:
#     /home/user/MythRokuPlayer/tools/rokuencode.sh "%DIR%" "%FILE%"

# See note below for any custom settings you may want to set.

MYTHDIR="$1"
OLDFILE="$2"

NEWFILE=`echo $OLDFILE | sed 's/\(.*\)\..*/\1/'`
NEWFILE="$NEWFILE.m4v"

OLDPATH="$MYTHDIR/$OLDFILE"
NEWPATH="$MYTHDIR/$NEWFILE"

LOG="/tmp/rokuencode.sh.log"

echo "=================================================================" >> $LOG
date >> $LOG

echo "SOURCE:      $OLDPATH" >> $LOG
echo "DESTINATION: $NEWPATH" >> $LOG
echo "" >> $LOG

# NOTE: Modify this to specify any HandBrakeCLI parameters you would like to
#       use to transcode your recordings (see 'HandBrakeCLI --help' for more
#       options):

nice -n 10 /usr/bin/HandBrakeCLI -v --preset="Normal" -i "$OLDPATH" -o "$NEWPATH" 2>> $LOG

echo "=================================================================" >> $LOG

