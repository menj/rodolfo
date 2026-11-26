=== Rodolfo ===
Contributors: menj
Tags: custom-colors, custom-menu, featured-images, threaded-comments, translation-ready, dark-mode, blog
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 4.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A fast, text-first blog theme: system fonts, no UI library, full Schema.org structured data, and built-in SEO and GEO tooling.

== Description ==

* Page weight: about 6-9 KB of gzipped HTML plus ~6 KB of gzipped CSS.
* System font stack, no web fonts, no background images.
* One unified Schema.org JSON-LD graph per page (WebSite, Organization or Person, WebPage subtypes, header/footer/navigation, breadcrumbs, articles, FAQ, HowTo, tables, quotations, lists, definitions, images, video, comments).
* Unique titles and descriptions, canonical URLs, noindex rules for search/404, crawlable navigation, HTML sitemap page template (assign "Sitemap" to a page).
* Virtual /llms.txt in four layouts, robots.txt directives for search traps and AI crawlers.
* Optional "Preferred source" button for Google Top Stories (Theme Options).
* Guest authors: per-post custom bylines (one or several names), optional site-wide default, guest Person nodes in the schema graph.
* End mark: a symbol or image after the last paragraph of each article, with per-post opt-out and an [end_mark] shortcode.
* Footnotes: write ((a note)) in a post; numbered, linked notes with back-links appear under the article (per-post list style, merged identical notes, ((ref:N))).
* Unlist: hide any post or page from listings, search, feeds, sitemaps, navigation and prev/next links while its direct URL keeps working (noindex). Publish-box checkbox, Quick Edit, Bulk Edit, list filter.
* Layout switches (home layout, header layout, footer columns) for per-site variation.

== Installation ==

1. Upload the theme folder to wp-content/themes/ and activate it.
2. Open Appearance > Theme Options and review the Content, SEO and Performance tabs.
3. Optional: run rebrand-theme.sh from the theme folder to give each install its own prefix and identity.

== Changelog ==

= 4.0.0 =
* Rebuilt for performance, structured data and GEO. Login, donate and click-to-tweet modules are off by default.
