#!/bin/bash

#convert mpeg file to mp4 using handbrakecli
 
newname=`echo $2 | sed 's/\(.*\)\..*/\1/'`
newname="$1/$newname.mp4"

/usr/bin/HandBrakeCLI -i $1/$2 -o $newname -e x264 -b 1500 -E faac -B 256 -R 48 -w 720


