<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/>

    <xsl:template match="/">
        <html>
            <head>
                <title>MythRoku Test Page</title>
            </head>
            <body bgcolor="white" text="blue">
                <h1>MythRoku Test Page:
                    <xsl:choose>
                        <xsl:when test="feed/@listType = 'vid'"> Videos</xsl:when>
                        <xsl:when test="feed/@listType = 'rec'"> Recordings</xsl:when>
                    </xsl:choose>
                </h1>
                Use this page to help diagnose any problems there may be with settings and configuration. If all data displays ok here then it should work with Roku!
                <br />
                <xsl:apply-templates select="feed" />
            </body>
        </html>
    </xsl:template>

    <xsl:template match="feed">
        <h2>Showing: <xsl:value-of select="@resultIndex"/> to <xsl:value-of select="@resultIndex + @resultLength - 1"/> of <xsl:value-of select="@resultTotal"/> files</h2>
        <xsl:for-each select="item">

            <xsl:variable name="img" select="hdImg" />

            <xsl:if test="itemType = 'dir'">
                <xsl:variable name="url" select="feed" />

                <b><xsl:value-of select="title" /></b><br/>

                <table border="1">
                    <tr>
                        <td>
                            <xsl:choose>
                                <xsl:when test="isRecording = 'true'"> <a href="{$url}"><img src="{$img}" width="300" height="168" /></a> </xsl:when>
                                <xsl:otherwise> <a href="{$url}"><img src="{$img}" width="200" height="250" /></a> </xsl:otherwise>
                            </xsl:choose>
                        </td>
                    </tr>
                </table><br />
            </xsl:if>

            <xsl:if test="itemType = 'file'">
                <b><xsl:text>[#</xsl:text><xsl:value-of select="index" /><xsl:text>] </xsl:text>
                <xsl:value-of select="title" /><xsl:text> </xsl:text></b>
                <i><xsl:value-of select="subtitle" /></i><br/>

                <table border="1">
                    <tr>
                        <td rowspan="5">
                            <xsl:choose>
                                <xsl:when test="isRecording = 'true'">
                                    <img src="{$img}" width="300" height="168" />
                                </xsl:when>
                                <xsl:otherwise>
                                    <img src="{$img}" width="200" height="250" />
                                </xsl:otherwise>
                            </xsl:choose>
                        </td>
                        <td><b>Rating:</b></td><td><xsl:value-of select="rating" /></td>
                        <td><b>Runtime:</b></td><td><xsl:value-of select="runtime div 60" /> min</td>
                        <td><b>Genre:</b></td><td><xsl:value-of select="genres" /></td>
                    </tr>
                    <tr>
                        <td><b>Date:</b></td><td><xsl:value-of select="date" /></td>
                        <td><b>Star Rating:</b></td><td><xsl:value-of select="starRating" /></td>
                        <xsl:if test="contentType = 'episode'">
                            <td><b>Episode:</b></td><td><xsl:value-of select="episode" /></td>
                        </xsl:if>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <b>Stream Url(s):</b><br/>
                            <xsl:apply-templates select="stream" />
                        </td>
                    </tr>
                    <tr><td colspan="6"><b>Synopsis:</b><br/><xsl:value-of select="synopsis" /></td></tr>
                    <xsl:if test="isRecording = 'true'">
                        <xsl:variable name="delcmd" select="delCmd" />
                        <tr>
                            <td colspan="7">
                                <b>Delete Command:</b><br/>
                                <a href="{$delcmd}"><xsl:copy-of select="$delcmd" /></a>
                            </td>
                        </tr>
                    </xsl:if>
                </table><br />
            </xsl:if>

        </xsl:for-each>
    </xsl:template>

    <xsl:template match="stream">
        <xsl:variable name="url" select="url" />
        <xsl:value-of select="quality" />: <a href="{$url}"><xsl:copy-of select="$url" /></a><br/>
    </xsl:template>

</xsl:stylesheet>

