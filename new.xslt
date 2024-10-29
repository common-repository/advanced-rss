<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:php="http://php.net/xsl"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xsl:extension-element-prefixes="php"
    >

    <xsl:output
        method="html"
        indent="yes"
        encoding="iso-8859-1"
        />

    <xsl:template match="/rss/channel">

        <xsl:value-of select="$before_widget" disable-output-escaping="yes" />
        <xsl:value-of select="$before_title" disable-output-escaping="yes" />

        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="$url"/>
            </xsl:attribute>
            <xsl:attribute name="class">
                <xsl:text>rsswidget</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:text>Syndicate this content</xsl:text>
            </xsl:attribute>
            <xsl:element name="img">
                <xsl:attribute name="style">
                    <xsl:text>background:orange;color:white;border:none;</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="height">
                    <xsl:text>14</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="width">
                    <xsl:text>14</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="src">
                    <xsl:copy-of select="$rss_icon"/>
                </xsl:attribute>
            </xsl:element>
        </xsl:element>

        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:copy-of select="link"/>
            </xsl:attribute>
            <xsl:attribute name="class">
                <xsl:text>rsswidget</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:copy-of select="description" />
            </xsl:attribute>
            <xsl:value-of select="title" />
        </xsl:element>
        <xsl:value-of select="$after_title" disable-output-escaping="yes" />

        <ul>
            <xsl:for-each select="item">

                <xsl:if test="position() &lt;= $items">
                <li>
                    <xsl:element name="a">
                        <xsl:attribute name="href">
                            <xsl:value-of select="link" />
                        </xsl:attribute>
                        <xsl:attribute name="class">
                                <xsl:text>rsswidget</xsl:text>
                        </xsl:attribute>
                        <xsl:attribute name="title">
                                <xsl:value-of select="description" />
                        </xsl:attribute>
                        <xsl:value-of select="title" />
                    </xsl:element>
                </li>
                </xsl:if>

            </xsl:for-each>
        </ul>

        <xsl:value-of select="$after_widget" disable-output-escaping="yes" />

    </xsl:template>

</xsl:stylesheet>
