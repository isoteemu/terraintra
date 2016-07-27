<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
          <!ENTITY lt "&#60;" >
          <!ENTITY gt "&#62;" >
          <!ENTITY nbsp "&#160;" >
]>
<!-- kate: space-indent on; indent-width 2; mixedindent on; indent-mode xml; encoding utf-8; -->
<xsl:stylesheet version="1.0"
      xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
      xmlns:fo="http://www.w3.org/1999/XSL/Format">
  <xsl:output method="xml" indent="yes"/>

  <xsl:include href="./common-fop.xsl" />

  <xsl:template match="html">
    <fo:root font-family="Liberation Sans, Tahoma, Arial, Helvectica, sans-serif" font-size="10pt">
      <fo:layout-master-set>
        <fo:simple-page-master master-name="A4-portrait"
                               page-height="29.7cm"
                               page-width="21.0cm"
                               margin="1.5cm">
          <fo:region-body margin-top="2.1cm" margin-bottom="2.1cm"/>
          <fo:region-before extent="1.6cm"/>
          <fo:region-after extent="1cm"/>
        </fo:simple-page-master>
      </fo:layout-master-set>
      <fo:page-sequence master-reference="A4-portrait">

        <!-- HEADER -->
        <fo:static-content flow-name="xsl-region-before" font-family="Liberation Sans, Tahoma, Arial, Helvectica, sans-serif" font-size="10pt">
          <xsl:call-template name="header">
            <xsl:with-param name="date" select="head/meta[@name='revised']/@content" />
            <xsl:with-param name="type" select="head/title" />
            <xsl:with-param name="number" select="head/meta[@name='number']/@content" />
            <xsl:with-param name="href" select="head/link[@rel='top']/@href" />
          </xsl:call-template>
        </fo:static-content>

        <!-- FOOTER -->
        <fo:static-content flow-name="xsl-region-after" font-family="Liberation Sans, Tahoma, Arial, Helvectica, sans-serif" font-size="10pt">
          <xsl:call-template name="footer" />
        </fo:static-content>
        <!-- BODY-->
        <fo:flow flow-name="xsl-region-body">
          <fo:block margin="0.5cm">
            <xsl:apply-templates select="body" />
          </fo:block>
        </fo:flow>
      </fo:page-sequence>
    </fo:root>
  </xsl:template>
</xsl:stylesheet>
