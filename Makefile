################################################################################
# Simple makefile for packaging and deploying the MythRokuPlayer
#
# Makefile Usage:
#	$ make
#	$ make install
#	$ make remove
#
# To exclude certain files from being added to the zipfile during packaging
# include a line like this:
#
#	ZIP_EXCLUDE= -x test\* -x .\*
#
# This will exclude any file who's name begins with 'keys' and all hidden files.
#
# Important Notes:
# To use the "install" and "remove" targets to install your application directly
# from the shell, you must do the following:
#
# 1) Make sure that you have the curl command line executable in your path
# 2) Set the environment variable ROKU_DEV_TARGET to the IP address of your Roku
#    box. For example:
#
#		$ export ROKU_DEV_TARGET=192.168.1.1
#		$ make install
#
#	 Alternatively, you can add the following to your ~/.bashrc file:
#
#		export ROKU_DEV_TARGET=192.168.1.1
#
#	 Or, you can edit the mythrokumake tool (<pathtomythrokuplayer>/tools/) with
#	 your Roku's IP address. This tool is especially useful if you have more
#	 than one Roku because you can push to more than one Roku with one command.
#
################################################################################

APPNAME = MythRokuPlayer
VERSION = 1.1

ZIP_EXCLUDE= -x .\* -x \*/.\* -x mythroku\* -x tools\* 

include app.mk

