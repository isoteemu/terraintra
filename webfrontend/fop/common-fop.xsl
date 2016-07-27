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

  <!-- Default language -->
  <xsl:param name="Lang">en-GB</xsl:param>
  <xsl:param name="strings">strings.xml</xsl:param>

  <xsl:variable name="translations" select="document($strings)"/>

  <xsl:template name="header">
    <xsl:param name="type" />
    <xsl:param name="number" />
    <xsl:param name="date" />
    <xsl:param name="href" />
    <fo:block border-bottom-style="solid">
      <fo:external-graphic src="/srv/www/drupal/modules/teemu/intra/fop/terralogo.svg"
                          text-align="left" />
      <fo:block font-weight="bold"
                font-size="16"
                float="right"
                margin-top="-10mm"
                text-align="center">
        <xsl:value-of select="$type" />
      </fo:block>
      <fo:block text-align="right"
                float="right"
                margin-top="-3mm">
        <xsl:if test="$number">
          <fo:inline>
          <xsl:call-template name="getText">
            <xsl:with-param name="string" select="'Number'"/>
          </xsl:call-template>:
          </fo:inline>
          <fo:inline font-weight="bold">
            <xsl:value-of select="$number" />
          </fo:inline>
           <!-- nbsp -->
        </xsl:if>
        <fo:inline>
          <xsl:call-template name="getText">
            <xsl:with-param name="string" select="'Date'"/>
          </xsl:call-template>:
          <fo:inline font-weight="bold">
            <xsl:call-template name="dateFormat">
              <xsl:with-param name="isoDate" select="$date"/>
            </xsl:call-template>
          </fo:inline>
        </fo:inline>
      </fo:block>
    </fo:block>
  </xsl:template>

  <xsl:template name="footer">
    <xsl:choose>
      <xsl:when test="owner">
        <xsl:call-template name="footer-body">
          <xsl:with-param name="owner" select="owner"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="footer-body">
          <xsl:with-param name="owner" select="document('./owner.xml')//owner"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="footer-body">
    <xsl:param name="owner" />
    <fo:block border-top-style="solid">
      <fo:block>
        <fo:block text-align="left"><xsl:apply-templates select="$owner/company/cname" /></fo:block>
        <xsl:if test="$owner/company/vat">
          <fo:block text-align="right" margin-top="-12pt">Vat ID: <xsl:apply-templates select="$owner/company/vat" /></fo:block>
        </xsl:if>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../street">
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../street" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="$owner/company/street" />
          </xsl:otherwise>
        </xsl:choose>, 
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../zip">
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../zip" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="$owner/company/zip" />
          </xsl:otherwise>
        </xsl:choose>&#160;
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../city">
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../city" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="$owner/company/city" />
          </xsl:otherwise>
        </xsl:choose>,
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../country">
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../country" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="$owner/company/country" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../phone">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Phone'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../phone" />,
          </xsl:when>
          <xsl:when test="$owner/company/phone">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Phone'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/company/phone" />,
          </xsl:when>
        </xsl:choose>
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../telefax">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Fax'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../telefax" />,
          </xsl:when>
          <xsl:when test="$owner/company/telefax">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Fax'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/company/telefax" />,
          </xsl:when>
        </xsl:choose>
        <xsl:choose>
          <xsl:when test="$owner/person/class[text()='Account']/../email">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Email'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/person/class[text()='Account']/../email" />
          </xsl:when>
          <xsl:when test="$owner/company/email">
            <xsl:call-template name="getText">
              <xsl:with-param name="string" select="'Email'"/>
            </xsl:call-template>:
            <xsl:apply-templates select="$owner/company/email" />
          </xsl:when>
        </xsl:choose>
      </fo:block>
    </fo:block>
  </xsl:template>

  <!-- Date formating template -->
  <xsl:template name="dateFormat">
    <xsl:param name="isoDate" />
    <xsl:variable name="year">
      <xsl:value-of select="substring($isoDate,1,4)" />
    </xsl:variable>

    <xsl:variable name="month-temp">
      <xsl:value-of select="substring-after($isoDate,'-')" />
    </xsl:variable>
    <xsl:variable name="month">
      <xsl:value-of select="substring-before($month-temp,'-')" />
    </xsl:variable>

    <xsl:variable name="day-temp">
      <xsl:value-of select="substring-after($month-temp,'-')" />
    </xsl:variable>
    <xsl:variable name="day" select="substring($day-temp,1,2)" />

    <xsl:value-of select="$day"/>.<xsl:value-of select="$month"/>.<xsl:value-of select="$year"/>
  </xsl:template>

  <!--
    Translation
  -->
  <xsl:template name="getText">
    <xsl:param name="string" />

    <xsl:variable name="PrimaryLang" select="substring-before($Lang,'-')"/>

    <xsl:variable name="str" select="$translations/strings/str[@name=$string]"/>
    <xsl:choose>
      <xsl:when test="$str[lang($Lang)]">
        <xsl:value-of select="$str[lang($Lang)][1]"/>
      </xsl:when>
      <xsl:when test="$str[lang($PrimaryLang)]">
        <xsl:value-of select="$str[lang($PrimaryLang)][1]"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:message terminate="no">
          <xsl:text>Warning: no string named '</xsl:text>
          <xsl:value-of select="$string"/>
          <xsl:text>' found.</xsl:text>
        </xsl:message>

        <xsl:value-of select="$string"/>
      </xsl:otherwise>
    </xsl:choose>

  </xsl:template>

  <!--
      HTML Conversion to FO
      http://www.ibm.com/developerworks/library/x-xslfo2app/
      -->
  
  <xsl:template match="html/body//a|rem//a">
    <xsl:choose>
      <xsl:when test="@name">
        <xsl:if test="not(name(following-sibling::*[1]) = 'h1')">
          <fo:block line-height="0" space-after="0pt" 
                    font-size="0pt" id="{@name}"/>
        </xsl:if>
      </xsl:when>
      <xsl:when test="@href">
        <fo:basic-link color="blue">
          <xsl:choose>
            <xsl:when test="starts-with(@href, '#')">
              <xsl:attribute name="internal-destination">
                <xsl:value-of select="substring(@href, 2)"/>
              </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
              <xsl:attribute name="external-destination">
                <xsl:value-of select="@href"/>
              </xsl:attribute>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:apply-templates select="*|text()"/>
        </fo:basic-link>
        <xsl:if test="starts-with(@href, '#')">
          <xsl:text> on page </xsl:text>
          <fo:page-number-citation ref-id="{substring(@href, 2)}"/>
        </xsl:if>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="html/body//abbr[@class='money']">
    <fo:inline font-family="Liberation Mono, Courier new, monospace">
      <xsl:apply-templates select="*|text()"/>
    </fo:inline>
  </xsl:template>
  
  <xsl:template match="html/body//br|rem//br">
    <fo:block> </fo:block>
  </xsl:template>
  
  <xsl:template match="html/body//b|html/body//strong|rem//b|rem//strong">
    <fo:inline font-weight="bold">
      <xsl:apply-templates select="*|text()"/>
    </fo:inline>
  </xsl:template>
  
  <xsl:template match="html/body//address|rem//address">
    <fo:block>
      <xsl:apply-templates select="*|text()"/>
    </fo:block>
  </xsl:template>
  
  <xsl:template match="html/body//em|rem//em">
    <fo:inline font-style="italic">
      <xsl:apply-templates select="*|text()"/>
    </fo:inline>
  </xsl:template>
  
  <xsl:template match="html/body//h1|rem//h1">
    <fo:block font-size="14pt"
              font-weight="bold"
              margin-left="-1em">
      <xsl:apply-templates select="*|text()"/>
    </fo:block>
  </xsl:template>
  
  <xsl:template match="html/body//h2|rem//h2">
    <fo:block font-size="12pt"
              font-weight="bold">
      <xsl:apply-templates select="*|text()"/>
    </fo:block>
  </xsl:template>

  <xsl:template match="html/body//h3|rem//h3">
    <fo:block font-weight="bold">
      <xsl:apply-templates select="*|text()"/>
    </fo:block>
  </xsl:template>

  <xsl:template match="html/body//hr|rem//hr">
    <fo:block>
      <fo:leader leader-pattern="rule"/>
    </fo:block>
  </xsl:template>
  
  <xsl:template match="html/body//i|rem//i">
    <fo:inline font-style="italic">
      <xsl:apply-templates select="*|text()"/>
    </fo:inline>
  </xsl:template>
  
  <xsl:template match="html/body//nobr|rem//nobr">
    <fo:inline wrap-option="no-wrap">
      <xsl:apply-templates select="*|text()"/>
    </fo:inline>
  </xsl:template>
  
  <xsl:template match="p">
    <fo:block line-height="12pt"
              space-before="5pt"
              space-after="5pt">
      <xsl:apply-templates select="*|text()"/>
    </fo:block>
  </xsl:template>
  
  <xsl:template match="html/body//table|rem//table">
    <!-- Webpage is 798px width -->
    <xsl:variable name="width">
      <xsl:choose>
        <xsl:when test="substring(@width,string-length(@width))='%'">
          <xsl:value-of select="@width" />
        </xsl:when>
        <xsl:when test="@width">
          <xsl:value-of select="@width div 798 * 100" /><xsl:text>%</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>100%</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <fo:table border-style="solid"
              width="$width"
              background-color="rgb(95%, 95%, 100%)"
              border-color="lightgrey"
              margin-left="3em" table-layout="fixed">
      <xsl:attribute name="width">
        <xsl:value-of select="$width"/>
      </xsl:attribute>

      <xsl:for-each select="./tbody/tr[1]/td">
        <fo:table-column>
          <xsl:attribute name="column-width">proportional-column-width(<xsl:value-of select="@width"/>)</xsl:attribute>
        </fo:table-column>
      </xsl:for-each>
      <fo:table-body>
        <xsl:apply-templates select="*"/>
      </fo:table-body>
    </fo:table>
  </xsl:template>
  
  <xsl:template match="tfoot">
    <xsl:apply-templates select="tr"/>
  </xsl:template>
  
  <xsl:template match="html/body//tr|rem//tr">
    <fo:table-row>
      <xsl:choose>
        <xsl:when test="@class='odd'">
          <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
        </xsl:when>
        <xsl:when test="@class='even'">
          <xsl:attribute name="background-color"><xsl:text>rgb(100%, 100%, 100%)</xsl:text></xsl:attribute>
        </xsl:when>
      </xsl:choose>
      <xsl:apply-templates select="*|text()"/>
    </fo:table-row>
  </xsl:template>
  
  <xsl:template match="html/body//td|rem//td|html/body//th|rem//th">
    <fo:table-cell margin="3pt">
      <xsl:if test="@colspan">
        <xsl:attribute name="number-columns-spanned">
          <xsl:value-of select="@colspan"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:if test="@rowspan">
        <xsl:attribute name="number-rows-spanned">
          <xsl:value-of select="@rowspan"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:if test="@border='1' or 
              ancestor::tr[@border='1'] or
              ancestor::thead[@border='1'] or
              ancestor::table[@border='1']">
        <xsl:attribute name="border-style">
          <xsl:text>solid</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="border-color">
          <xsl:text>rgb(102, 184, 217)</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="border-width">
          <xsl:text>1pt</xsl:text>
        </xsl:attribute>
      </xsl:if>
      <xsl:variable name="align">
        <xsl:choose>
          <xsl:when test="@align">
            <xsl:choose>
              <xsl:when test="@align='center'">
                <xsl:text>center</xsl:text>
              </xsl:when>
              <xsl:when test="@align='right'">
                <xsl:text>end</xsl:text>
              </xsl:when>
              <xsl:when test="@align='justify'">
                <xsl:text>justify</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>start</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:when test="ancestor::tr[@align]">
            <xsl:choose>
              <xsl:when test="ancestor::tr/@align='center'">
                <xsl:text>center</xsl:text>
              </xsl:when>
              <xsl:when test="ancestor::tr/@align='right'">
                <xsl:text>end</xsl:text>
              </xsl:when>
              <xsl:when test="ancestor::tr/@align='justify'">
                <xsl:text>justify</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>start</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:when test="ancestor::thead">
            <xsl:text>center</xsl:text>
          </xsl:when>
          <xsl:when test="ancestor::table[@align]">
            <xsl:choose>
              <xsl:when test="ancestor::table/@align='center'">
                <xsl:text>center</xsl:text>
              </xsl:when>
              <xsl:when test="ancestor::table/@align='right'">
                <xsl:text>end</xsl:text>
              </xsl:when>
              <xsl:when test="ancestor::table/@align='justify'">
                <xsl:text>justify</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>start</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>start</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      
      <xsl:choose>
        <xsl:when test="name()='th'">
          <fo:block text-align="{$align}" font-weight="bold">
            <xsl:apply-templates select="*|text()"/>
          </fo:block>
        </xsl:when>
        <xsl:otherwise>
          <fo:block text-align="{$align}">
            <xsl:apply-templates select="*|text()"/>
          </fo:block>
        </xsl:otherwise>
      </xsl:choose>

    </fo:table-cell>
  </xsl:template>
</xsl:stylesheet>
