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

  <xsl:template match="invoice">
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
            <xsl:with-param name="date" select="invoice_date"/>
            <xsl:with-param name="type">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Invoice'"/>
              </xsl:call-template>
            </xsl:with-param>
            <xsl:with-param name="number" select="nr" />
          </xsl:call-template>
        </fo:static-content>

        <!-- FOOTER -->
        <fo:static-content flow-name="xsl-region-after" font-family="Liberation Sans, Tahoma, Arial, Helvectica, sans-serif" font-size="10pt">
          <xsl:call-template name="footer" />
        </fo:static-content>
        <!-- BODY-->
        <fo:flow flow-name="xsl-region-body">
          
          <!-- Fucking fuck fuck fuck. Reminds me of HTML4 times. -->
          <fo:table table-layout="fixed" width="100%" border-collapse="separate">
            <fo:table-column />
            <fo:table-column column-width="(from-parent('width') div 2) - (from-parent('width') div 2 - 78mm)" /> <!-- Xiit to get child aligned to right border-->
            <fo:table-body>
              <fo:table-row>
                <fo:table-cell>
                  <!-- BILLING -->
                  <xsl:apply-templates select="billing" />
                </fo:table-cell>
                <fo:table-cell>
                  <xsl:call-template name="invoice-info" />
                </fo:table-cell>
              </fo:table-row>
            </fo:table-body>
          </fo:table>

          <xsl:if test="rem[@type='rem']">
            <fo:block margin-top="3em">
              <xsl:apply-templates select="rem[@type='rem']" />
            </fo:block>
          </xsl:if>

          <xsl:apply-templates select="items" />
          
          <!-- Payment details -->
          <xsl:if test="rem[@type='payment']">
            <fo:block margin-top="3em"
                      margin-bottom="3em"
                      margin-left="3em"
                      margin-right="3em"
                      keep-together.within-page="always">
              <xsl:apply-templates select="rem[@type='payment']" />
            </fo:block>
          </xsl:if>

          <!-- Additional text -->
        </fo:flow>
      </fo:page-sequence>
    </fo:root>
  </xsl:template>

  <xsl:template match="billing">
    <!-- Container -->
    <fo:block margin-left="2mm">
      <fo:block font-variant="small-caps"
                font-weight="bold">
        <xsl:choose>
          <xsl:when test="person/class[text()='Faktura']/../street">
            <xsl:apply-templates select="person/class[text()='Faktura']/../dname" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/cname" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="person/class[text()='Account']/../street">
            <xsl:apply-templates select="person/class[text()='Account']/../street" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/street" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="person/class[text()='Account']/../box">
            <xsl:apply-templates select="person/class[text()='Account']/../box" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/box" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="person/class[text()='Account']/../zip">
            <xsl:apply-templates select="person/class[text()='Account']/../zip" />&#160;
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/zip" />&#160;
          </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
          <xsl:when test="person/class[text()='Account']/../city">
            <xsl:apply-templates select="person/class[text()='Account']/../city" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/city" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
      <fo:block>
        <xsl:choose>
          <xsl:when test="person/class[text()='Account']/../country">
            <xsl:apply-templates select="person/class[text()='Account']/../country" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="company/country" />
          </xsl:otherwise>
        </xsl:choose>
      </fo:block>
<!--
      <xsl:choose>
        <xsl:when test="person/class[text()='Account']/../country">
          <xsl:variable name="billing_country" select="person/class[text()='Account']/../country" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="billing_country" select="company/country" />
        </xsl:otherwise>
      </xsl:choose>
      <xsl:choose>
        <xsl:when test="../owner/person/class[text()='Account']/../country">
          <xsl:variable name="owner_country" select="../owner/person/class[text()='Account']/../country" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="owner_country" select="../owner/company/country" />
        </xsl:otherwise>
      </xsl:choose>
      <xsl:if test="$billing_country">
        <fo:block>
          <xsl:value-of select="$billing_country" />
        </fo:block>
      </xsl:if>
      -->
    </fo:block>
  </xsl:template>
  
  <xsl:template name="invoice-info">
    <!-- INVOICE -->
    <fo:table border-style="solid"
              padding="2pt"
              margin="0mm"
              border-collapse="separate"
              width="76mm"
              border-color="rgb(102, 184, 217)">
      <fo:table-column column-width="48%" />
      <fo:table-column />
      <fo:table-header>
        <fo:table-row>
          <fo:table-cell margin-bottom="4mm"
                         number-columns-spanned="2">
            <fo:block font-weight="bold">
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Order details'"/>
                </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body margin="1pt">
        <fo:table-row>
          <fo:table-cell>
            <fo:block number-columns-spanned="2">&#160;</fo:block>
          </fo:table-cell>
          </fo:table-row>
        <fo:table-row>
          <fo:table-cell number-columns-spanned="2">
            <fo:block>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <xsl:if test="ag_nr">
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Maintenance Agreement'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block>
                <xsl:apply-templates select="ag_nr" />
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        <xsl:if test="cust_reference">
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Purchase Order'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block>
                <xsl:apply-templates select="cust_reference" />
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        <xsl:if test="order_date">
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Order Date'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block>
                <xsl:call-template name="dateFormat">
                  <xsl:with-param name="isoDate" select="order_date"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        <xsl:if test="p_id">
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Contact'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block>
                <xsl:variable name="p_id" select="p_id" />
                <xsl:value-of select="billing/person/id[text()=$p_id]/../fname" />&#160;<xsl:value-of select="billing/person/id[text()=$p_id]/../lname" />
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        <xsl:if test="billing/company/vat">
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Vat ID'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block>
                <xsl:value-of select="billing/company/vat" />
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        <xsl:if test="delivery/company/@id!=billing/company/@id">
          <fo:table-row>
            <fo:table-cell>
              <fo:block number-columns-spanned="2">&#160;</fo:block>
            </fo:table-cell>
          </fo:table-row>
          <fo:table-row>
            <fo:table-cell text-align="right">
              <fo:block font-weight="bold">
                <xsl:call-template name="getText">
                  <xsl:with-param name="string" select="'Customer'"/>
                </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block font-weight="bold">
                <xsl:apply-templates select="delivery/company/cname" />
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
      </fo:table-body>
    </fo:table>
  </xsl:template>

  <xsl:template name="sum">
    <fo:block margin-left="3em"
              margin-top="8mm"
              margin-bottom="8mm">
      <fo:block font-size="24pt"
                font-weight="bold"
                color="rgb(102, 184, 217)">
        <xsl:call-template name="money">
          <xsl:with-param name="money" select="fee"/>
        </xsl:call-template>
      </fo:block>
      <xsl:if test="due_date">
      <fo:block margin-top="-0.4em">
        <xsl:call-template name="getText">
          <xsl:with-param name="string" select="'Due'"/>
        </xsl:call-template>
 
        <xsl:call-template name="dateFormat">
          <xsl:with-param name="isoDate" select="due_date"/>
        </xsl:call-template>
      </fo:block>
      </xsl:if>
    </fo:block>
  </xsl:template>

  <!-- This is bullshit -->
  <xsl:template match="items[ancestor::invoice/type='3']">
    <fo:table margin="0mm" margin-top="1.2cm">
      <fo:table-column column-width="1cm" />
      <fo:table-column  />
      <fo:table-column  />
      <fo:table-column column-width="9em" />
      <fo:table-column  />
      <fo:table-column column-width="5em" />
      <fo:table-column column-width="9em" />
      <fo:table-header border-bottom-style="solid"
                       border-color="rgb(102, 184, 217)"
                       border-width="2pt">
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="center"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Nr'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Item'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Serials'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Rate'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold"
                      text-align="center">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'%'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold"
                      text-align="center">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Monthly'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold"
                      text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Subtotal'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:for-each select="item">
          <fo:table-row>
            <xsl:if test="(position() mod 2) != 1">
              <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
            </xsl:if>
            <fo:table-cell text-align="center">
              <fo:block>
                <xsl:value-of select='position()' />
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block><xsl:apply-templates select="rem" /></fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block><xsl:apply-templates select="serial" /></fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="right">
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="rate"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="center"
                        font-family="Liberation Mono, Courier new, monospace">
                <xsl:choose>
                  <xsl:when test="lease_rate &gt; 0">
                    <xsl:value-of select='format-number(lease_rate, "###,###.##")' />
                  </xsl:when>
                  <xsl:otherwise>
                    0
                  </xsl:otherwise>
                </xsl:choose>
                %
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="right">
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="lease_monthly"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="fee"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:for-each>

        <fo:table-row border-top-style="solid"
                      border-color="rgb(102, 184, 217)">
          <fo:table-cell number-columns-spanned="6">
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Subtotal'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="sum(item[*]/fee)"/>
                </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

        <xsl:variable name="vat">
          <xsl:choose>
            <xsl:when test="../vat_pros">
              <xsl:value-of select="number(../vat_pros)" />
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="number('0')" />
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <!-- Spacer row -->
        <fo:table-row>
          <fo:table-cell number-columns-spanned="7">
            <fo:block>&#160;</fo:block>
          </fo:table-cell>
        </fo:table-row>

        <xsl:if test="count(additional) > 0">
          <!-- Additional rows -->
          <xsl:for-each select="additional">
            <fo:table-row>
              <xsl:if test="(position() mod 2) != 1">
                <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
              </xsl:if>
              <fo:table-cell number-columns-spanned="6">
                <fo:block text-align="right">
                  <xsl:apply-templates select="rem" />:
                </fo:block>
              </fo:table-cell>
              <fo:table-cell>
                <fo:block text-align="right">
                  <xsl:call-template name="money">
                    <xsl:with-param name="money" select="fee"/>
                  </xsl:call-template>
                </fo:block>
              </fo:table-cell>
            </fo:table-row>
          </xsl:for-each>
          <!-- Spacer row -->
          <fo:table-row> 
            <fo:table-cell number-columns-spanned="7">
              <fo:block>&#160;</fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>

        <xsl:if test="$vat &gt; 0">
          <fo:table-row>
            <xsl:if test="(count(additional) mod 2) != 1">
              <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
            </xsl:if>
            <fo:table-cell number-columns-spanned="6">
              <fo:block text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Total (net)'"/>
              </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="right">
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="../fee"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        
        <fo:table-row>
          <xsl:if test="(count(additional) mod 2) = 1">
            <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
          </xsl:if>
          <fo:table-cell number-columns-spanned="6">
            <fo:block text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'VAT'"/>
              </xsl:call-template>
              <xsl:value-of select="format-number($vat, '###,###.##')" /> %:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="number(../fee) * ( $vat div 100 )"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

        <!-- Spacer row -->
        <fo:table-row> 
          <fo:table-cell number-columns-spanned="7">
            <fo:block>&#160;</fo:block>
          </fo:table-cell>
        </fo:table-row>

        <!-- Grant total -->
        <fo:table-row border-top-style="solid"
                      border-bottom-style="solid"
                      border-bottom-width="2"
                      border-color="rgb(102, 184, 217)">
          <fo:table-cell number-columns-spanned="6">
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Total'"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="../fee * ( $vat div 100 + 1 )"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

      </fo:table-body>
    </fo:table>
  </xsl:template>

  <xsl:template match="items">
    <fo:table margin="0mm" margin-top="1.2cm">
      <fo:table-column column-width="1cm" />
      <fo:table-column  />
      <fo:table-column  />
      <fo:table-column column-width="9em" />
      <fo:table-column column-width="5em" />
      <fo:table-column column-width="9em" />
      <fo:table-header border-bottom-style="solid"
                       border-color="rgb(102, 184, 217)"
                       border-width="2pt">
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="center"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Nr'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Item'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Serials'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Rate'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold"
                      text-align="center">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Disc. %'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block font-weight="bold"
                      text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Subtotal'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:for-each select="item">
          <fo:table-row>
            <xsl:if test="(position() mod 2) != 1">
              <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
            </xsl:if>
            <fo:table-cell text-align="center">
              <fo:block>
                <xsl:value-of select='position()' />
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block><xsl:apply-templates select="rem" /></fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block><xsl:apply-templates select="serial" /></fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="right">
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="rate"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="center"
                        font-family="Liberation Mono, Courier new, monospace">
                <xsl:choose>
                  <xsl:when test="discount &gt; 0">
                    <xsl:value-of select='format-number(discount, "###,###.##")' />
                  </xsl:when>
                  <xsl:otherwise>
                    0
                  </xsl:otherwise>
                </xsl:choose>
                %
              </fo:block>
            </fo:table-cell>
            <fo:table-cell text-align="right">
              <fo:block>
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="fee"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:for-each>

        <fo:table-row border-top-style="solid"
                      border-color="rgb(102, 184, 217)">
          <fo:table-cell number-columns-spanned="5">
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Subtotal'"/>
              </xsl:call-template>:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="sum(item[*]/fee)"/>
                </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

        <xsl:variable name="vat">
          <xsl:choose>
            <xsl:when test="../vat_pros">
              <xsl:value-of select="number(../vat_pros)" />
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="number('0')" />
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <!-- Spacer row -->
        <fo:table-row>
          <fo:table-cell number-columns-spanned="6">
            <fo:block>&#160;</fo:block>
          </fo:table-cell>
        </fo:table-row>

        <xsl:if test="count(additional) > 0">
          <!-- Additional rows -->
          <xsl:for-each select="additional">
            <fo:table-row>
              <xsl:if test="(position() mod 2) != 1">
                <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
              </xsl:if>
              <fo:table-cell number-columns-spanned="5">
                <fo:block text-align="right">
                  <xsl:apply-templates select="rem" />:
                </fo:block>
              </fo:table-cell>
              <fo:table-cell>
                <fo:block text-align="right">
                  <xsl:call-template name="money">
                    <xsl:with-param name="money" select="fee"/>
                  </xsl:call-template>
                </fo:block>
              </fo:table-cell>
            </fo:table-row>
          </xsl:for-each>
          <!-- Spacer row -->
          <fo:table-row> 
            <fo:table-cell number-columns-spanned="6">
              <fo:block>&#160;</fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>

        <xsl:if test="$vat &gt; 0">
          <fo:table-row>
            <xsl:if test="(count(additional) mod 2) != 1">
              <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
            </xsl:if>
            <fo:table-cell number-columns-spanned="5">
              <fo:block text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Total (net)'"/>
              </xsl:call-template>:
              </fo:block>
            </fo:table-cell>
            <fo:table-cell>
              <fo:block text-align="right">
                <xsl:call-template name="money">
                  <xsl:with-param name="money" select="../fee"/>
                </xsl:call-template>
              </fo:block>
            </fo:table-cell>
          </fo:table-row>
        </xsl:if>
        
        <fo:table-row>
          <xsl:if test="(count(additional) mod 2) = 1">
            <xsl:attribute name="background-color"><xsl:text>rgb(97%, 97%, 100%)</xsl:text></xsl:attribute>
          </xsl:if>
          <fo:table-cell number-columns-spanned="5">
            <fo:block text-align="right">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'VAT'"/>
              </xsl:call-template>
              <xsl:value-of select="format-number($vat, '###,###.##')" /> %:
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="number(../fee) * ( $vat div 100 )"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

        <!-- Spacer row -->
        <fo:table-row> 
          <fo:table-cell number-columns-spanned="6">
            <fo:block>&#160;</fo:block>
          </fo:table-cell>
        </fo:table-row>

        <!-- Grant total -->
        <fo:table-row border-top-style="solid"
                      border-bottom-style="solid"
                      border-bottom-width="2"
                      border-color="rgb(102, 184, 217)">
          <fo:table-cell number-columns-spanned="5">
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="getText">
                <xsl:with-param name="string" select="'Total'"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="right"
                      font-weight="bold">
              <xsl:call-template name="money">
                <xsl:with-param name="money" select="../fee * ( $vat div 100 + 1 )"/>
              </xsl:call-template>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>

      </fo:table-body>
    </fo:table>
  </xsl:template>

  <xsl:template name="money">
    <xsl:param name="money" />
    <xsl:variable name="currency" select="//invoice/currency" />
    <xsl:variable name="money" select="$money * //invoice/rate" />

    <fo:inline font-family="Liberation Mono, Courier new, monospace">
      <xsl:choose>
        <xsl:when test="$currency='USD' or $currency='CAD' or $currency='GEO'">
          <xsl:value-of select="document('currency_api.xml')/currency_api/symbol[@name=$currency]"/><xsl:value-of select="format-number($money , '###,##0.00')" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="format-number($money, '###,##0.00')" />&#160;<xsl:value-of select="document('currency_api.xml')/currency_api/symbol[@name=$currency]"/>
        </xsl:otherwise>
      </xsl:choose>
    </fo:inline>
  </xsl:template>

  <xsl:template match="rem/line">
    <fo:block white-space-collapse="false">
      <xsl:apply-templates select="*|text()" />
    </fo:block>
  </xsl:template>

  <xsl:template match="item/rem/line">

    <xsl:choose>
      <xsl:when test="position()=2">
        <fo:block white-space-collapse="false"
                  wrap-option="no-wrap"
                  text-decoration="underline">
          <xsl:apply-templates select="*|text()" />
        </fo:block>
      </xsl:when>
      <xsl:otherwise>
        <fo:block white-space-collapse="false"
                  wrap-option="no-wrap">
          <xsl:apply-templates select="*|text()" />
        </fo:block>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="items//serial">
    <xsl:if test="count(child::*) &gt; 1">
      <fo:block>&#160;</fo:block>
    </xsl:if>
    <fo:block
             font-family="Liberation Mono, Courier New, monospace">
      <xsl:apply-templates select="*|text()" />
    </fo:block>
  </xsl:template>

</xsl:stylesheet>