=== Rodolfo ===
Contributors: menj
Tags: custom-colors, custom-menu, featured-images, threaded-comments, translation-ready, dark-mode, tailwind
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 3.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modern, responsive WordPress theme featuring a subtle flowing background art, dark mode support, and a clean typography-focused design. Inspired by the design of Rodolfo Berrios' blog (https://rodolfoberrios.com/).

== Description ==

Rodolfo is a beautiful, modern WordPress theme designed specifically for writers, bloggers, and creators who want their content to shine. It offers a distraction-free reading experience while providing subtle, premium touches like background video loops and glass-like headers.

Key features:
* **Dark & Light Mode**: A simple switch lets your readers choose their preferred reading environment. The theme remembers their choice for their next visit!
* **Video Backgrounds**: Want to make your site more dynamic? You can easily paste a link to any video, and it will play silently in the background.
* **Reading Time**: Automatically shows your readers how long an article will take to read.
* **Category Emojis**: Personalize your categories by assigning fun emojis to them directly from your WordPress dashboard.
* **Dynamic Footer**: A polished footer with your logo and site name, an "About the Site" links section, your copyright line (the starting year is worked out automatically from your first post), handy links for Privacy, Terms, Accessibility, Sitemap, and RSS, plus your social media icons.
* **Share & Bookmark**: Every article has a Share button (uses your device's share menu, or copies the link) and a Bookmark button that remembers your saved articles on your next visit.
* **Reading Progress Bar**: A slim bar shows readers how far through a post they are. Height and position (top or bottom) are adjustable.
* **Donate Button**: Accept donations through PayPal. Enter your PayPal email (or your own donation link) and the button can appear automatically at the end of every post, or wherever you place the [donate] shortcode.
* **Page-driven Navigation**: Decide where each page appears — header menu, footer row, or hidden — and in what order, without building a menu.

== Installation ==

1. In your WordPress dashboard, go to **Appearance > Themes**.
2. Click the **Add New** button at the top, then click **Upload Theme**.
3. Choose the `rodolfo.zip` file you downloaded and click **Install Now**.
4. Once installed, click **Activate**.

== Frequently Asked Questions ==

= How do I change the background video? =
Go to **Appearance > Customize** and look for the **Background Media** tab. Simply paste a link to any `.mp4` video file, and it will replace the static background image.

= How do I add emoji icons to my categories? =
Go to **Posts > Categories**. When adding a new category (or editing an existing one), you'll see a new field labeled "Category Icon (Emoji)". Paste any emoji there, and it will show up on that category's main page.

= How do I enable the Tags button in the navigation? =
Create a WordPress page with the slug `tags`. The Tags icon will appear automatically in the header once a page with that slug exists.

= I changed the Navigation settings but nothing changed on my site. Why? =
You most likely have a WordPress menu assigned under **Appearance > Menus**. An assigned menu always takes priority over the per-page Navigation settings in Theme Options. To use the Theme Options settings instead, go to **Appearance > Menus > Manage Locations** and set the Primary (and Secondary) location to "— Select —". The Navigation tab will warn you when this is happening.

= How do I show the Donate button? =
Go to **Appearance > Theme Options > Content** and enter your PayPal email, or a custom donation link. Then either set "Automatic placement" to add the button to the end of every post, or place it manually by adding the [donate] shortcode to any post or page. Note that if you fill in a custom donate URL, it is used instead of the PayPal email.

= My FAQ, Terms, Privacy or Accessibility pages are empty. How do I fill them? =
Go to Appearance > Theme Options > General > Site setup and press "Create missing pages & fill empty ones". Missing pages are created and any that exist but are still blank get starter content to edit. Pages that already have content are never changed.

= Why can't I see the reading progress bar? =
It only appears on individual posts and pages (not the homepage or category listings), and only when the page is long enough to scroll. Check that it is enabled under **Appearance > Theme Options > Content** and that the bar height is at least 2 pixels.

== Credits ==

Parsedown — Markdown parser used to render the theme's documentation inside the dashboard.
Copyright (c) 2013-2018 Emanuil Rusev, erusev.com
Licensed under the MIT License.
Source: https://github.com/erusev/parsedown
Bundled at vendor/parsedown/ with its licence file. The theme uses an existing
Parsedown class if another plugin or theme has already loaded one, and falls
back to a small built-in renderer if the bundled copy is unavailable.

== Changelog ==

= 3.10.0 =
* New: optional consent-gated embeds. YouTube, Vimeo, SoundCloud and Spotify load only when the reader presses play — no third-party connection, no tracking cookies before that.
* New: optional comment-form bot guard (hidden honeypot + timing check, invisible to your readers), with Cloudflare Turnstile as an optional extra layer.
* New: Click to Tweet is now a proper editor block — write the quote visually instead of typing a shortcode.
* New: an Unlisted column, filter and quick-edit toggle on the Posts screen.
* New: "trending" post rankings — views over the last day or week, alongside the all-time counter. No scheduled tasks involved.
* New: System Info now offers recommendations (permalinks, debug output, comment pagination and more), not just readings.
* Fixed: a batch of ten fixes restored after regressing in the 3.4.0 restructure, including llms.txt on plain-permalink sites and unlisted posts staying out of llms.txt; all now pinned by tests.
* Fixed: the test suite runs fully green for the first time (427 checks).

= 3.9.0 =
* New: optional post view counts, stored on your own site (no external service). Bots and your own admin browsing do not count.
* New: optional "Resume where you left off" pill for returning readers on long posts. Stored only in the reader's browser.
* New: authors can add their own social links on their profile — shown as icons on the author card at the end of their posts.
* Changed: search result excerpts are now a bit longer than card excerpts, so matching keywords tend to appear in the snippet. Both are configurable.

= 3.8.2 =
* Fixed: a critical error on the System Info tab (and a blank Documentation tab) on sites running an SEO plugin such as All in One SEO, Yoast, or Rank Math.

= 3.8.1 =
* Fixed: the Theme Options tabs did not respond on sites with strict security (CSP) settings. Tabs now work with or without JavaScript.

= 3.8.0 =
* Changed: internal code reorganisation for maintainability. No visible or functional difference, and child themes are unaffected.

= 3.7.1 =
* Fixed: pages marked "coming soon" could produce a conflicting robots tag alongside an SEO plugin. The theme now works through WordPress's shared robots setting instead.
* Changed: System Info now names which SEO plugin has taken over the theme's meta tags and structured data.

= 3.7.0 =
* New: structured data now covers pages, category and tag archives, author pages, search results, and post series — not just posts and the homepage. Search engines and AI tools can read what every part of your site is about.

= 3.6.2 =
* New: optional footer date stamp — choose "Last post" (when you last published) or "Updated" (when any post was last edited).
* New: your homepage now includes published and modified dates in its structured data, so search engines can read how fresh the site is.

= 3.6.1 =
* Changed: the footer date stamp now uses your newest post's publication date by default.

= 3.6.0 =
* New: optional numbered header menu — press 1-9 to jump to a section.
* New: mark any page as "coming soon" so it stays in your menu and says what is planned, instead of being hidden.
* New: optional footer date stamp showing when you last published.

= 3.5.3 =
* Changed: internal documentation only. No functional change.

= 3.5.2 =
* Changed: internal documentation housekeeping. No functional change.

= 3.5.1 =
* New: the System Info tab now reports on the theme itself — whether each feature can run, which settings are affecting your site, and whether the utility pages exist — with a one-click JSON download for support requests. General environment checks stay where they belong, in Tools > Site Health.

= 3.4.0 =
* Changed: internal code reorganisation for maintainability. No visible or functional difference, and child themes are unaffected.

= 3.3.2 =
* Fixed: posts could show the reading-time estimate twice with slightly different numbers. There is now a single estimate, and the setting to hide it works everywhere.

= 3.3.1 =
* Fixed: "Create anything missing" now adds starter content to utility pages that already exist but are empty. Previously it only filled pages it created itself, so blank pages from an older version stayed blank. Pages that already have content are never changed.

= 3.3.0 =
* Changed: the site now always starts in light mode. Dark mode is opt-in via the toggle, and your choice is remembered. Previously the site followed your device's dark mode setting on a first visit.
* Changed: social share images and the maintenance page now use the light style by default to match.

= 3.2.0 =
* New: the block editor now matches the published design — same fonts, spacing, headings and content width, so what you write is what you get.
* New: the editor colour picker and font sizes now use the theme's own palette instead of WordPress defaults.
* New: optional estimated reading time on posts.
* New: post series support for multi-part guides, with "Part 2 of 5" navigation.
* New: optional full-content RSS, a light social share card style, and an optional widget sidebar (all off by default).
* Fixed: threaded comment replies did not nest properly.

= 3.1.1 =
* Changed: internal rework of how the maintenance page is displayed. No visible difference.

= 3.1.0 =
* Improved: the maintenance / coming soon page now matches the theme — your logo, brand colour, dark mode support, and a friendlier default message.
* New: you can write your own maintenance headline and message under Appearance > Theme Options > General.

= 3.0.1 =
* Removed: the GitLab option from the Social Links tab. GitHub and the other platforms are unaffected.

= 3.0.0 =
* New: you can now add an email address to the footer social links, under Appearance > Theme Options > Social Links. Enter just the address — the mailto link is created for you.
* Fixed: corrected a broken link to the blog that inspired the theme's design.

= 2.13.2 =
* Improved: documentation pages in the dashboard now render with the Parsedown library, so more complex Markdown displays correctly.
* Fixed: checklists in the Upgrading document showed raw "[x]" markers instead of tick boxes.
* Fixed: tables in the Reference document lost their borders and heading styles.

= 2.13.1 =
* Improved: documentation rendering now uses the bundled Parsedown library (MIT). If another plugin has already loaded Parsedown, that copy is used instead, so there is no conflict.

= 2.13.0 =
* New: the theme's documentation is now readable inside your dashboard, under Appearance > Theme Options > Documentation — no need to open the theme folder.

= 2.12.0 =
* Fixed: pages at /privacy and /privacy-policy are now recognised as the same page, so the Privacy link no longer disappears from the footer or show up twice in the menu.
* Fixed: "Create anything missing" no longer creates a duplicate page when one already exists under a different address (e.g. /terms-of-use instead of /terms).
* New: newly created FAQ, Terms, Privacy and Accessibility pages now come with starter content instead of being blank. Privacy and Terms are clearly marked as drafts to replace — they are a starting outline, not legal advice.
* New: Site setup now tells you when a page exists but is still empty, with a link to edit it.
* New: the Navigation tab now warns you when a WordPress menu is assigned, because an assigned menu overrides the per-page settings there.

= 2.11.1 =
* Fixed: the reading progress bar could be saved at 1 pixel tall, making it effectively invisible and appear broken. Height is now kept between 2 and 20 pixels.

= 2.11.0 =
* New: the Donate button can now appear automatically at the end of your posts or pages — no shortcode or block needed. Set it under Theme Options → Content.
* New: Theme Options is now easier to navigate. The crowded General tab has been split into General, Navigation and Content. No settings were lost or renamed.
* New: the GitHub social link is available again in the Social Links tab.

= 2.10.0 =
* New: breadcrumbs on posts (Home › Category › Title) — helps readers, search engines and AI assistants understand where a page sits. Uses your SEO plugin's breadcrumbs automatically if one is active.
* Improved: the big featured images now load first instead of lazily, making pages feel visibly faster (better Core Web Vitals).
* New: the site's robots.txt now explicitly welcomes AI crawlers and points them to your llms.txt summary. Can be turned off by developers if you prefer not to appear in AI answers.

= 2.9.0 =
* New: posts now show an "Updated" date when meaningfully revised — a freshness signal readers and AI assistants both look for.
* New: the site automatically publishes an llms.txt summary (your pages, recent posts and topics) that AI tools read to understand and cite your site correctly.
* Improved accessibility: text colours now meet WCAG AA contrast, all animation respects your device's "reduce motion" setting, and closing the search panel returns you to where you were.

= 2.8.0 =
* New: a "proof" callout for showing a real, specific result near the top of a post — a big exact number (e.g. "20 min"), what it measures, and an optional link to the case study backing it. Use [proof metric="20 min" label="setup time, down from 3 hours" cite="https://…"].

= 2.7.0 =
* New: AI-search (AEO) content blocks. Open a post with a highlighted [answer]…[/answer] "Quick answer" box, add an FAQ section with [faqs][faq q="…"]…[/faq][/faqs] (search engines and AI assistants receive the matching FAQ structured data automatically), and add an honest side-by-side [compare]…[/compare] table.

= 2.6.0 =
* New: 14 social platforms in Theme Options → Social Links — Twitter/X, Facebook, Instagram, YouTube, TikTok, Threads, LinkedIn, Pinterest, Reddit, WhatsApp, Telegram, GitLab, Medium and Substack. Fill in a URL and its icon appears in the footer; leave blank to hide it.
* Changed: refreshed the Theme Options page design — calmer colours, clearer sections, better spacing and focus states.
* Removed: the GitHub social field (set it as a custom link if you still need it — or ask and we'll re-add it).

= 2.5.0 =
* New: the built-in footer links now include FAQ, Terms of Use, Privacy Policy, Sitemap and RSS by default. Each can be hidden, renamed, or reordered under Appearance → Theme Options → Navigation — none are forced on, so you keep only the ones your site needs.
* New: Site setup now also creates FAQ and Terms of Use pages (only if missing; never overwrites).


= 2.4.0 =
* New: full control over navigation. Under Appearance → Theme Options → Navigation, choose for each page whether it appears in the header menu, the footer, or nowhere — and set the order. You can also show/hide and reorder the built-in Privacy Policy, Sitemap and RSS footer links.
* Fixed: the Privacy Policy page no longer shows up twice or under two different names.


= 2.3.1 =
* New: enter just your PayPal email to accept donations — the theme builds the hosted PayPal donate link for you. Optional currency setting.

= 2.3.0 =
* New: reading progress bar on posts and pages, with height and position settings.
* New: [donate] button shortcode linking to your payment page.

= 2.2.1 =
* Changed: internal build improvement to how layout styles are compiled. No visible change.

= 2.2.0 =
* New: Click-to-Tweet blocks — add a shareable, highlighted quote with the [bctt] shortcode (e.g. [bctt tweet="Your quote"]). Set your X/Twitter handle under Appearance → Theme Options → Social.
* New: Unlist a post — tick "Unlist this post" in the Publish box to hide it from the home page, archives, search and feeds while its direct link keeps working.
* New: a branded login screen — your site logo (or site icon) now appears on the WordPress login page, tinted with your theme colour.
* New: search results and thin archive pages are now kept out of search engines automatically (skipped if you run an SEO plugin).

= 2.1.1 =
* Changed: the footer's "Explore Topics" column now always shows your top categories instead of page links — pages belong in the menus, topics belong here.

= 2.1.0 =
* New: the main menu now builds itself from your Pages. In Appearance → Theme Options → Navigation you can tick any page to move it out of the main menu and into the footer instead.
* Changed: the footer's secondary links now show any pages you moved there, followed by Privacy, Sitemap and RSS.
* Changed: removed the duplicate "Tags" link from the menu — the tag list is already available from the tag icon next to the search box.

= 2.0.4 =
* Changed: the search box is now larger — wider panel, taller input and bigger text — for easier reading and typing.

= 2.0.3 =
* Fixed: the search overlay now dims the page behind it more strongly, so the search box is easy to read and focus on.

= 2.0.2 =
* Fixed: post cards no longer overlap their own text, and the article grid spacing/columns render correctly again.

= 2.0.1 =
* Fixed: the built-in UI toolkit can no longer interfere with the theme's own styling — the theme's design always takes priority. No action needed.

= 2.0.0 =
* New: a full UI toolkit is now built into the theme — styles plus interactive components like modals, dropdowns, carousels and tooltips — all loaded locally with nothing fetched from the internet.
* Note: this is a major update. There are no changes you need to make; existing sites keep working as-is.

= 1.11.1 =
* Fixed: an uploaded logo is no longer squeezed into a bordered circle — it now displays at its natural shape and proportions in both the header and footer.
* Fixed: utility pages (Terms, Accessibility) no longer appear in the main navigation. The header now shows clean, curated links (Tags and your pages), and existing sites are tidied up automatically.
* Changed: article cards now show a comma-separated topic line (like the original Rodolfo design) in place of the coloured category tag.

= 1.11.0 =
* New: a built-in Tags page at /tags/ that lists every tag as a clickable cloud — works automatically, with no page to create or set up.
* New: clearer footer navigation — a header (primary) menu, a secondary row below the copyright, and a tertiary "topics" column that fills itself from your most-used categories when you haven't set a menu.
* Changed: refreshed the article cards — each is now a bordered card that lifts on hover, with the author's photo, name and date along the bottom.

= 1.10.5 =
* Changed: the footer logo is now larger than the header logo for a stronger sign-off.

= 1.10.4 =
* Fixed: the article grid on the homepage had no vertical spacing between rows of stories — the rows now breathe evenly.
* Fixed: the footer logo now matches the header logo exactly (same size and round shape).

= 1.10.3 =
* Fixed: an invisible search layer was sitting on top of every page and blocking all clicks, so links and buttons appeared dead. The search panel now stays fully out of the way until you open it.
* Improved: tightened the spacing around the homepage featured story and article grid to reduce excess empty space.

= 1.10.2 =
* Changed: the homepage now leads with a single, balanced featured story (image beside its summary) instead of the lop-sided magazine layout — removes the large empty space on the right, and the extra stories now fill a denser article grid below.

= 1.10.1 =
* Fixed: on the homepage, the two smaller stories beside the lead article showed tiny slivers of text with oversized images. Their thumbnails and titles now display correctly.

= 1.10.0 =
* New: posts without a featured image now show a tasteful placeholder tinted in the category's colour with its icon — no more empty grey boxes.
* New: single posts end with an “About the author” card (photo, bio, and a link to their other posts) when the author has filled in their bio.
* Improved: the homepage's lead story now shows the author and reading time.

= 1.9.0 =
* New: instant search — press ⌘K (or Ctrl+K), or click the search box, to open a fast search-as-you-type panel that jumps straight to any article.
* New: the homepage now leads with a magazine-style layout — one large featured story alongside a column of the next two.
* New: share previews (the image shown when your posts are shared on social media) now include the category colour and the author.
* Improved: refreshed the comments area with a clearer heading.

= 1.8.0 =
* New: long articles now show a “Table of contents” so readers can jump to any section.
* New: each article now ends with a “Related articles” row suggesting more to read.
* New: article and headline titles now use an elegant serif typeface for a more editorial, magazine-like feel.
* Improved: refreshed the theme's accent colour to a distinctive cobalt blue that's all its own.
* Improved: modernised the behind-the-scenes styling tools so future design tweaks are quicker and safer (no visible change).

= 1.7.1 =
* New: the Inter font now comes built into the theme, so your site looks the same everywhere without loading anything from Google (better privacy). No setup needed.

= 1.7.0 =
* New: your posts now include rich search-engine data (SEO description, Facebook/Twitter share tags, and Google structured data) — automatically switched off if you run an SEO plugin, so nothing is duplicated.
* New: posts without a featured image get a good-looking share picture generated from the post's title.
* New: each category can have its own accent colour — pick one on the category screen, or let the theme choose a tasteful one for you. It appears on category badges and a thin line on each card.
* New: one-click "Create anything missing" button (Appearance → Theme Options → General) sets up your Privacy, Terms, Accessibility, and Tags pages and a footer menu. It never touches anything you've already made.
* New: a built-in site icon (favicon) shows until you set your own under Site Identity.
* Improved: option to use the Inter font again, self-hosted (privacy-friendly) — just drop the font file in place; the site stays on your system font until you do.

= 1.6.1 =
* Fixed: page numbers now display as proper round buttons (the styling wasn't being applied before).
* Fixed: your logo no longer appears oversized in the header — it now displays neatly at the same size as in the footer.
* Fixed: the menu no longer disappears from the header when your menu exists but has no links in it — your categories are shown instead.

= 1.6.0 =
* Improved: the Theme Options page has a fresh, modern look — and now shows which version of the theme you're running.
* Improved: clearer setting names, arranged in the order you'd naturally set up a site.

= 1.5.1 =
* Fixed: page numbers at the bottom of article lists now look like proper buttons instead of plain text.
* Fixed: the footer's copyright and links now sit side by side on desktop instead of stacking.
* Fixed: the footer no longer shows an empty "About the Site" heading when your footer menu has no links.

= 1.5.0 =
* Fixed: an article no longer appears twice when browsing from page 1 to page 2 of your homepage.
* Fixed: the Reset Settings button in Theme Options now works reliably.
* Fixed: reading time now calculates correctly for all languages, including Chinese, Japanese, and Korean.
* Security: all theme settings are now checked and cleaned before saving; your admin email address can no longer be discovered from the site's code; maintenance mode now properly hides the site from everyone except site managers.
* Improved: icons now appear instantly and work even with JavaScript turned off.
* Improved: fonts no longer load from Google's servers — better privacy for your visitors (GDPR).
* New: mobile menu — visitors on phones now get a menu button with navigation and search.
* New: "Skip to content" shortcut for keyboard and screen-reader users.
* New: redesigned footer with your logo and site name, an "About the Site" links section, and a row of links for Privacy, Terms, Accessibility, Sitemap, and RSS.
* New: set your Terms and Accessibility page links under Appearance > Theme Options > General. The Sitemap link works automatically.
* Added: mobile navigation (hamburger menu + search), skip-to-content link, align-wide and responsive-embeds support, expanded i18n coverage.

= 1.4.0 =
* Rebuilt: Theme Options page now has a tabbed interface (General, Social, Performance, Scripts, System Info).
* Added: Export Settings / Import Settings — backup and restore all options as JSON.
* Added: Reset Settings — wipes all options to defaults with a confirmation prompt.
* Added: Performance tab — disable emojis, disable embeds, disable jQuery Migrate.
* Added: Scripts tab — inject custom HTML into head, body open, and body close on every page.
* Added: Maintenance mode — Disabled / Maintenance (503) / Coming Soon (200).
* Added: System Info tab — WordPress environment, server, PHP, and theme details.

= 1.3.0 =
* Added: template-parts/content/content-card.php — single source of truth for the post card markup.
* Added: template-parts/content/content-single.php — single post article header and content area.
* Added: Custom logo support via add_theme_support('custom-logo') — set a logo in Appearance → Customize.
* Changed: All loop templates (index, archive, category, tag, search) now use get_template_part() for the card.
* Changed: single.php delegates to content-single.php — reduced from 117 to 24 lines.
* Changed: Header and footer logo now uses the custom logo when set, with Gravatar as a fallback.
* Security: Admin email MD5 hash is no longer unconditionally embedded in public HTML.

= 1.2.0 =
* Added: archive.php — dedicated template for date, author, and custom post type archives.
* Added: category.php — dedicated category archive template with emoji icon header, extracted from index.php.
* Added: tag.php — dedicated tag archive template with article count.
* Added: assets/css/ directory with compiled Tailwind CSS (theme.min.css) and source file (input.css).
* Added: tailwind.config.js for build-time Tailwind compilation.
* Added: load_theme_textdomain() — translations now work correctly.
* Changed: Tailwind CSS now served as a compiled static stylesheet. CDN script removed.
* Changed: Lucide Icons pinned to v0.383.0 — no longer loaded from @latest.
* Changed: lucide.createIcons() moved into WordPress enqueue system via wp_add_inline_script().
* Changed: Pagination CSS extracted from inline style block in index.php into compiled stylesheet.

= 1.1.0 =
* Fixed: Pagination was silently skipping posts on pages 2 and beyond due to incorrect WP_Query offset usage.
* Fixed: Fatal error on posts with comments enabled — comments.php was missing.
* Fixed: 404 pages were rendering the homepage article grid instead of a not-found page.
* Fixed: Search results were rendering through the homepage template.
* Fixed: CSRF vulnerability in category icon save handler — nonce verification added.
* Fixed: XSS vulnerability — search query output is now properly escaped.
* Fixed: Reverse tabnapping vulnerability — added rel="noopener noreferrer" to all external links.
* Fixed: Tags navigation link generated a hardcoded URL that 404'd on standard installs.
* Fixed: Share and Bookmark buttons were non-functional — both are now fully implemented.
* Fixed: Multi-page posts had no page navigation — wp_link_pages() added to single and page templates.
* Fixed: Multiple output escaping standards violations across templates.
* Added: comments.php — threaded comment list and styled form.
* Added: 404.php — clean not-found page with inline search.
* Added: search.php — dedicated search results template with result count and no-results state.

= 1.0.2 =
* Added: Dedicated "Theme Options" page in the WordPress admin under Appearance > Theme Options.
* Added: Support for adding GitHub, Twitter, and LinkedIn links to the footer.
* Added: Ability to override the dynamic footer text.
* Changed: Re-designed footer to better match the high-end mockup styling, split into two distinct tiers.

= 1.0.1 =
* Changed: Made the subtle background art even softer for better readability.
* Changed: The footer now automatically updates your site name and starting year.
* Added: You can now add emojis to your categories!
* Fixed: Ensured the background looks consistent across all pages.

= 1.0.0 =
* Initial release.

