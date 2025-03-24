<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
                xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
                xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <xsl:template match="/">
    <html>
      <head>
        <title>
          <xsl:choose>
            <xsl:when test="sitemap:sitemapindex">FridayAI XML Sitemap Index</xsl:when>
            <xsl:otherwise>FridayAI XML Sitemap</xsl:otherwise>
          </xsl:choose>
        </title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style>
          body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
            padding: 20px;
            margin: 0;
          }
          h1 {
            color: #0c4da2;
            font-size: 24px;
            font-weight: normal;
            margin: 0 0 20px;
          }
          table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
          }
          th {
            background-color: #f7f7f7;
            text-align: left;
            padding: 12px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
          }
          td {
            padding: 12px;
            border-bottom: 1px solid #eee;
          }
          .url {
            word-break: break-all;
          }
          a {
            color: #0c4da2;
            text-decoration: none;
          }
          a:hover {
            text-decoration: underline;
          }
          .description {
            margin-bottom: 20px;
          }
          .count {
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
          }
          .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
          }
          @media screen and (max-width: 768px) {
            body {
              padding: 10px;
            }
            th, td {
              padding: 8px;
            }
          }
        </style>
      </head>
      <body>
        <h1>
          <xsl:choose>
            <xsl:when test="sitemap:sitemapindex">FridayAI XML Sitemap Index</xsl:when>
            <xsl:otherwise>FridayAI XML Sitemap</xsl:otherwise>
          </xsl:choose>
        </h1>

        <div class="description">
          <xsl:choose>
            <xsl:when test="sitemap:sitemapindex">
              This sitemap index contains links to sub-sitemaps for different sections of the FridayAI website.
            </xsl:when>
            <xsl:otherwise>
              This XML Sitemap lists all pages on the FridayAI website to help search engine crawlers find content.
            </xsl:otherwise>
          </xsl:choose>
        </div>

        <xsl:choose>
          <xsl:when test="sitemap:sitemapindex">
            <div class="count">Number of sitemaps: <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/></div>
            <table>
              <tr>
                <th>URL</th>
                <th>Last Modified</th>
              </tr>
              <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                <tr>
                  <td class="url">
                    <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                  </td>
                  <td>
                    <xsl:if test="sitemap:lastmod">
                      <xsl:value-of select="sitemap:lastmod"/>
                    </xsl:if>
                  </td>
                </tr>
              </xsl:for-each>
            </table>
          </xsl:when>
          <xsl:otherwise>
            <div class="count">Number of URLs: <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></div>
            <table>
              <tr>
                <th>URL</th>
                <th>Priority</th>
                <th>Change Frequency</th>
                <th>Last Modified</th>
              </tr>
              <xsl:for-each select="sitemap:urlset/sitemap:url">
                <tr>
                  <td class="url">
                    <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                  </td>
                  <td>
                    <xsl:if test="sitemap:priority">
                      <xsl:value-of select="sitemap:priority"/>
                    </xsl:if>
                  </td>
                  <td>
                    <xsl:if test="sitemap:changefreq">
                      <xsl:value-of select="sitemap:changefreq"/>
                    </xsl:if>
                  </td>
                  <td>
                    <xsl:if test="sitemap:lastmod">
                      <xsl:value-of select="sitemap:lastmod"/>
                    </xsl:if>
                  </td>
                </tr>
              </xsl:for-each>
            </table>
          </xsl:otherwise>
        </xsl:choose>

        <div class="footer">
          This sitemap was generated by FridayAI on <xsl:value-of select="format-date(current-date(), '[D01] [MNn,*-3] [Y0001]')"/>.
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
