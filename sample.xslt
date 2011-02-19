<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

    <xsl:output method='html'/>

<!-- param values may be changed during the XSL Transformation -->
    <xsl:param name="title">Sample</xsl:param>
    <xsl:param name="script">index.php/test_xml</xsl:param>
    <xsl:param name="numrows">0</xsl:param>
    <xsl:param name="curpage">1</xsl:param>
    <xsl:param name="lastpage">1</xsl:param>
    <xsl:param name="script_time">0.2744</xsl:param>

    <xsl:template match="/">

        <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title>
                    <xsl:value-of select="$title"/>
                </title>
                <style type="text/css">
                  <![CDATA[
                  <!--
                    caption { font-weight: bold; }
                    th { background: #cceeff; }
                    tr.odd { background: #eeeeee; }
                    tr.even { background: #dddddd; }
                    .center { text-align: center; }
                  -->
                  ]]>
                </style>
            </head>
            <body>
                <div class="center">

                    <table border="1">
                        <caption>Title</caption>
                        <thead>
                            <tr>
                                <th>Input</th>
                            </tr>
                        </thead>

                        <tbody>
                            <xsl:apply-templates select="//input" />
                        </tbody>

                    </table>


                </div>
            </body>
        </html>

    </xsl:template>

    <xsl:template match="input">
        <tr>
            <xsl:attribute name="class">
                <xsl:choose>
                    <xsl:when test="position()mod 2">odd</xsl:when>
                    <xsl:otherwise>even</xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>

            <td>
                <xsl:value-of select="."/>
            </td>
        </tr>

    </xsl:template>

</xsl:stylesheet>