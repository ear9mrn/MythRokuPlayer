<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/> 

    <xsl:template match="/">
        <html>
            <head>
                <title>MythRoku Test Page</title>
            </head>
            <body bgcolor="white" text="blue">
		<br />
		<h1>MythRoku Test Page: Recordings</h1>
		<br />
		Use this page to help diagnose any problems there may be with settings and configuration. If all data displays ok here then it should work with Roku!
		<br /><br />
		<xsl:apply-templates select="feed" />
            </body>
        </html>
    </xsl:template>

    <xsl:template match="feed">
	<xsl:for-each select="item">
	    <b><xsl:value-of select="title" /> (#<xsl:value-of select="contentId" />)</b><br />
	    <b><xsl:value-of select="subtitle" /></b><br />

	    <xsl:variable name="url"    select="media/streamUrl" />
	    <xsl:variable name="delcmd" select="delcommand" />
	    
	    <table border="0">
		<tr>
		    <td rowspan="9">
			<xsl:variable name="sdimg" select="@sdImg" />
			<a href="{$url}">
			    <img src="{$sdimg}" width="300" height="168" />
			</a>
		    </td>
		    <td>
			<tr>
			    <td><b>Type:</b></td> <td><xsl:value-of select="contentType" /></td>
			    <td><b>Quality:</b></td> <td><xsl:value-of select="contentQuality" /></td>
			    <td><b>Format:</b></td> <td><xsl:value-of select="media/streamFormat" /></td>
			    <td><b>Bitrate:</b></td> <td><xsl:value-of select="media/streamBitrate" /></td>
			</tr>
			<tr>
			    <td><b>Genre:</b></td> <td> <xsl:value-of select="genres" /></td>
			    <td><b>Runtime:</b></td> <td> <xsl:value-of select="runtime" /> min</td>
			    <td><b>Recorded:</b></td> <td> <xsl:value-of select="date" /></td>
			    <td> </td> <td> </td>
			</tr>
			<tr><td colspan="8"><b>Delete Command:</b></td></tr>
			<tr><td colspan="8"><a href="{$delcmd}"><xsl:copy-of select="$delcmd" /></a></td></tr>
			<tr><td colspan="8"><b>Stream Url:</b></td></tr>
			<tr><td colspan="8"><a href="{$url}"><xsl:copy-of select="$url" /></a></td></tr>
			<tr><td colspan="8"><b>Synopsis:</b></td></tr>
			<tr><td colspan="8"><xsl:value-of select="synopsis" /></td></tr>
		    </td>
		</tr>
	    </table>

	    <br /> 

	</xsl:for-each>
    </xsl:template>

</xsl:stylesheet>

