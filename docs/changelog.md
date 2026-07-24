# Changelog

## [Unreleased]

### Added
- **Accent colour picker** (Customizer → Colors). One control recolours the entire theme — links, buttons, category pills, reading progress, the login screen, the maintenance page, even the options screen. Possible cleanly because the accent system was already variable-driven: the module just overrides `--rodolfo-accent` and three derived shades, and prints nothing at all when the default cobalt is untouched. Hover/focus shades are computed from the picked hue, so there is never a second stored colour to drift.
- **Feature toggles** (Theme Options → Features). Sixteen optional modules can be switched off individually — a disabled module is never even loaded, so unused features cost zero. Structural modules and modules that own content structures are deliberately not offered. Storage is inverted (`rodolfo_disable_<slug>`), so existing installs keep everything on.
- **Per-post display options.** A Display box on posts: hide the title (kept in the accessibility tree), hide the featured image (share cards still use it), or render full width.
- **Sticky title strip** (off by default). On long posts, a slim bar with the post title slides in under the site header once the headline scrolls away — driven entirely by an `IntersectionObserver`, adding no scroll handler, and appearing without animation for reduced-motion readers.

### Fixed
- **Footer bottom bar no longer collapses into a mess on sites with many social icons.** Four compounding wrap failures repaired: the ©-line / legal-links / social-circles row now stays stacked (and centred) until large screens instead of squeezing at tablet widths; each `·` separator travels with the link that follows it, so a line break can never strand a dot at the end of a line; the "Updated …" freshness stamp wraps as one unit instead of splitting mid-date; and the social circles wrap into a centred, width-capped grid rather than leaving an orphan icon hanging left. With ten-plus icons configured, the icons now form tidy rows.

### Changed
- **Table of contents grew up.** The current section is highlighted as you read (scroll-spy, observer-driven), and on phones the TOC collapses to its heading — tap to expand. Desktop behaviour is unchanged.
- **Related posts got smarter.** Posts sharing three tags now outrank posts sharing one (previously all tag matches sorted by date); editors can pin exact related posts per article via the `_rodolfo_related_ids` custom field, and the algorithm tops up around the pins.
- **Social cards carry the topic.** Each generated card's background now tints toward its category colour, and the site's custom logo appears bottom-right — every topic gets a family look.
- **⌘K search reaches further.** Results now include categories alongside posts and pages, and your last five searches appear when the palette opens — stored only in your own browser.
- **Comment form lightened.** Name / email / website sit side by side on wider screens, and the cookies-consent checkbox finally matches the theme.

## [3.10.0] - 2026-07-23

### Fixed
- **Re-landed the 3.3.x remediation batch** — ten fixes that regressed when 3.4.0 was built from a pre-batch working copy; all now pinned by permanent test assertions. Security: `esc_url_raw` sanitizer on the background-video Customizer setting; settings import verifies `is_uploaded_file()` and no longer text-sanitises the tmp path. llms.txt: served under plain permalinks via a direct request-path check (crawlers request the literal `/llms.txt`, which rewrite rules alone would 404); unlisted posts excluded from the body. i18n: the four `main.js` aria-labels (Share / Link copied! / Bookmark / Remove bookmark) localized via `rodolfoL10n`. Accessibility: autoplay background video hidden under `prefers-reduced-motion`; both decorative background wrappers `aria-hidden`. Hygiene: enqueues use `RODOLFO_VERSION` instead of re-reading `style.css` per request; the `/tags/` and `/llms.txt` rewrite-flush flags are keyed to the version so routing changes re-flush on every release; corrected the stale enqueue comment about `style.css`.
- **Test suite: first fully green run (393/393).** Repaired eight false-positive assertions that had failed since the 3.4–3.5 restructures — each was tripping on a comment, inline CSS, or renamed markup rather than behaviour, so the assertions were sharpened and no theme code changed: the light-default check now asserts the inline script never calls `matchMedia` (instead of failing on the comment that *explains* the design), the nav-table check follows the options page to `inc/options-page.php`, the docs-tables check asserts the kses allowlist rather than a retired class, the donate check runs against comment-stripped source, the tab-clamp needle no longer contains unmatchable literal backslashes, and the coming-soon/site-updated checks track the `wp_robots` array and the mode-scoped transient key. Also fixed a real test-file bug: `$sr` was read two lines before it was defined, producing a PHP warning on every run.
- **Admin CSS extracted to `assets/css/admin.css`** (+ `.min` twin), completing the asset separation the tab switcher started: the Theme Options styles were a 126-line `wp_add_inline_style()` string — uncacheable, unoverridable, and dead under a strict `style-src` CSP. Now enqueued by `RODOLFO_ASSET_SUFFIX` like every other asset.

### Added
- **Consent-gated embeds** (`inc/privacy-embeds.php`, off by default). YouTube, Vimeo, SoundCloud and Spotify iframes render as a click-to-load placeholder — a generic accent card, deliberately not the provider thumbnail, so the reader's browser contacts no third party until they press play. GDPR-friendly by default and a real performance win (a YouTube iframe costs hundreds of kilobytes before anyone plays it). Readers may opt to always load a provider; that choice lives in their own `localStorage`, nowhere else. Script-based embeds such as X pass through ungated by design.
- **Comment guard** (`inc/comment-guard.php`, off by default). A honeypot field plus a signed minimum-time trap on `preprocess_comment` — zero dependencies, invisible to readers, safe under page caches, catches drive-by comment bots. Never checks logged-in users or ping/trackbacks. Cloudflare Turnstile is available as an opt-in second layer: with both keys set, its script loads only when a reader focuses the comment field, so clean page views never contact Cloudflare.
- **Recommendations in System Info.** The report now gives advice, not just readings: ✓/⚠ rows for permalinks (plain permalinks degrade the virtual routes), debug output reaching visitors, unminified assets, unpaginated comments, autoloaded-option weight, and view-counting state. Filterable via `rodolfo_system_recommendations`.
- **Trending posts.** Alongside the all-time counter, views now keep a 7-day rolling daily tally in one pruned-on-write meta key — no cron, no midnight reset. `rodolfo_popular_posts()` gained a `$window` argument (1–7 days) ranking by views received in the window, distinct from `$scope` which restricts publish date; `rodolfo_trending_posts()` and `rodolfo_views_window()` are available to templates directly.
- **Click-to-Tweet as a native block** (`rodolfo/click-to-tweet`). Editors get a visual quote block with via/prompt controls instead of hand-typing the shortcode; it renders server-side through `[bctt]`, so block and shortcode output can never drift. Buildless vanilla `wp.blocks` — no JSX pipeline for a two-field block.
- **Unlist affordances on the Posts list.** An Unlisted column, an "Unlisted only" filter, and a quick-edit toggle — prefilled from the real state, because a blank quick-edit checkbox would silently re-list every post it saved.
- **Author archives complete the author card.** The social-links row from single posts now renders in the author-archive hero too.
- **Writing for answer engines.** The AEO section of the docs gained author guidance: honest 3–4-factor comparisons, objection-sourced FAQs, verdict-first answers, one verifiable number over three adjectives.

## [3.9.0] - 2026-07-22

### Added
- **Post view counts** (`inc/views.php`, off by default). One integer per post in post meta — no external service, no cookies, no fingerprinting. Bots, logged-in editors and repeat visits within an hour are excluded, so backfills, admin browsing and F5 do not skew the numbers. Available to templates via `rodolfo_views_get()`, `rodolfo_views_text()` and `rodolfo_popular_posts( $count, $scope, $exclude_id )`; the popularity helper is cached per scope for an hour and flushed on save/delete. The count optionally appears in the post meta line alongside the date and reading time.
- **Reading-position memory** (`assets/js/resume.js`, off by default, opt-in per site). A returning reader on a post they scrolled at least 15% through gets a small "Resume where you left off — 42%" pill in the corner. **Never auto-scrolls** — unsolicited page jumps are hostile. Stored entirely in `localStorage` (same posture as the dark-mode toggle); positions are pruned after 90 days.
- **Per-user social contact fields** (`inc/author-social.php`). Adds X/Twitter, GitHub, LinkedIn, Mastodon, Bluesky and a personal website field to the WordPress profile screen — via the native `user_contactmethods` filter, so `get_the_author_meta()` reads them anywhere. The author card on single posts now shows these as icons after the bio. Fills the gap the Social Links tab could not: that tab is for *site* accounts, not individual writers.
- **Per-context excerpt length**. Card excerpts on the index and archives now default to 24 words; search results to 40, so a matching keyword actually appears in the snippet. Configurable, and filterable via `rodolfo_excerpt_length`.
- Missing Lucide icons: `link`, `bluesky`, `mastodon`, `eye`.

## [3.8.2] - 2026-07-22

### Fixed
- **Fatal error on the System Info tab whenever an SEO plugin was active** (and, because the error landed inside a hidden panel, an apparently blank Documentation tab). Introduced in 3.7.1: the edit adding `rodolfo_detected_seo_plugin()` silently failed to apply — its insertion anchor had been removed by an earlier release — while the *call* to it landed, producing `Call to undefined function` the moment the report ran with an SEO plugin detected. The 3.8.1 tab fix exposed it: the panels had been unreachable while the tab JavaScript was CSP-blocked. The function is now defined beside its sibling `rodolfo_seo_plugin_active()` in `inc/meta-tags.php` so the two constant lists cannot drift, and the call site is guarded with `function_exists()` so a load-order surprise degrades to a generic label instead of a fatal.
- The SEO Framework was never detectable: the `class_exists()` check used a doubled namespace separator (`The_SEO_Framework\\Load`), present since the detection was first written. Corrected in both the detector and the namer.

### Added
- **Whole-theme call-graph assertion in the test harness**: every `rodolfo_*` function called anywhere in the theme must be defined somewhere in it, with the offending names printed on failure. This is precisely the class of bug above — invisible to per-file checks because the call is syntactically valid and only explodes at runtime under a condition (here, an active SEO plugin).

## [3.8.1] - 2026-07-22

### Fixed
- **Theme Options tabs could be completely dead** on installs where a security plugin enforces a Content-Security-Policy without `unsafe-inline` for scripts — common in wp-admin. The switcher was an inline `<script>` and the tab anchors were bare `#` fragments, so when the CSP silently blocked the script there was no fallback: clicking focused the tab and nothing else. Two-layer fix:
  1. Tab anchors now carry **real `?tab=` URLs**, so with JavaScript blocked or disabled the tabs work through a normal server round-trip — the page already rendered the correct panel from `$_GET['tab']`.
  2. The switcher moved to an **enqueued file** (`assets/js/admin.js` + min twin), which passes `script-src` by URL where inline scripts are refused. When it runs, it upgrades the same anchors to instant client-side switching.
- An unknown `?tab=` value now falls back to General instead of rendering a page with no active panel.

## [3.8.0] - 2026-07-22

### Changed
- **`inc/options-page.php` split into three modules** — a pure move, no behavioural change. At ~1,250 lines it had grown two clearly separable concerns: the settings *contract* and the settings *screen*. Now:
  - **`inc/settings.php`** (244 lines) — option registry, sanitizer map, social platform registry, `register_setting()` calls. This is the contract every other module writes against: an option only persists if it appears in `rodolfo_option_sanitizers()`. Readable now without wading through 700 lines of markup.
  - **`inc/options-actions.php`** (270 lines) — admin CSS, export / import / reset handlers, menu registration. Everything around the screen except the screen.
  - **`inc/options-page.php`** (768 lines) — the rendered page: tabs, panels, fields.
  Load order is `settings` → `options-actions` → `options-page`, since the latter two read the registry. All functions keep their names and signatures, so child themes are unaffected.
- Test harness `$funcs` extended to cover the two new files, and new assertions pin each module's ownership (sanitizer map in settings, export/import in actions, render in page) plus a guard that `options-page.php` stays under 800 lines.

## [3.7.1] - 2026-07-22

### Fixed
- **Coming-soon pages emitted a raw `<meta name="robots">` tag**, introduced in 3.6.0. Every SEO plugin emits its own robots tag, so a coming-soon page carried two — and crawlers may honour the most restrictive, meaning the theme could silently override a plugin's deliberate `index`. Now filters core's shared `wp_robots` array instead, so exactly one tag is emitted and a plugin filtering the same array still has the final say.

### Changed
- The System Info report now **names** the detected SEO plugin (Yoast, Rank Math, All in One SEO, SEOPress, The SEO Framework, or "via filter") rather than reporting only that one is active — the point of the row is knowing *what* took over the theme's meta output.

### Notes
- Full coexistence audit of every module that writes to `wp_head`: `meta-tags.php` and `schema.php` suppress themselves entirely under a detected SEO plugin; `breadcrumbs.php` calls the plugin's own trail when present; `aeo.php` keeps emitting FAQPage JSON-LD by design, since the Q/As come from Rodolfo's `[faqs]` shortcodes that no plugin knows about (documented opt-out: `add_filter( 'rodolfo_faq_jsonld', '__return_false' )`); `favicon.php` outputs a site icon, not an SEO signal. The maintenance holding page's robots tag is safe because it replaces the entire document — no plugin is running at that point.

## [3.7.0] - 2026-07-22

### Added
- **Structured data across every major view.** Schema previously covered only the front page, single posts and breadcrumbs — Pages, archives, author pages and search results emitted nothing, so crawlers and AI tools had no machine-readable description of most of the site. Now:
  - **Pages** — `WebPage` with `datePublished`/`dateModified`, narrowed to `ContactPage`, `AboutPage` or `FAQPage` by slug. Coming-soon pages are skipped: they have no content to describe and are noindexed anyway.
  - **Category / tag / custom taxonomy / date archives** — `CollectionPage` with a `mainEntity` `ItemList` of the posts actually shown, in order, so a crawler can see what a term covers without re-crawling each URL.
  - **Author archives** — `ProfilePage` plus a `Person` entity.
  - **Search results** — `SearchResultsPage`. Noindexed for search engines, but AI crawlers still read the page and benefit from knowing what it is.
  - **Posts in a series** — `isPartOf` a `CreativeWorkSeries` with `numberOfItems`, plus the post's `position`. Multi-part guides are exactly the content AI tools mis-summarise by treating part 3 as standalone; this states the sequence explicitly.
- FAQ questions are still emitted solely by `inc/aeo.php` from the `[faqs]` shortcode content. This module only claims the `FAQPage` type on an FAQ-slugged page and never duplicates the Q/As, so the two cannot disagree.

## [3.6.2] - 2026-07-22

### Added
- **Site-level dates in homepage schema** (`inc/schema.php`). Per-post `Article` schema already carried `datePublished` and `dateModified`, but the homepage `WebSite` node had no dates at all — so the visible footer stamp had no machine-readable counterpart for crawlers. The node now emits `datePublished` (first published post) and `dateModified` (newest modified post), both ISO 8601. Emitted **regardless of whether the visible stamp is enabled**, since schema costs nothing and helps even when the stamp is hidden.
- **Choice of which date the stamp reports.** `rodolfo_site_updated_mode` offers *Last post* (publication date of the newest post, the default) or *Updated* (most recently edited post). The label follows the mode, so the wording never misdescribes the date. Each mode caches under its own transient key.

### Fixed
- Schema `dateModified` is computed by a dedicated `rodolfo_site_modified_time()` rather than reusing the stamp's value. `dateModified` means "content last changed" and must not follow the owner's cosmetic choice of what the footer displays — previously selecting *Last post* mode would have reported a publication date as `dateModified`.

## [3.6.1] - 2026-07-22

### Changed
- The footer date stamp now reports the newest post's **publication** date rather than the most recently *modified* post, and is labelled "Last post" to match. Editing a typo in an old article should not make the whole site read as freshly updated. (3.6.2 makes this a choice.)

## [3.6.0] - 2026-07-22

### Added
- **Numbered keyboard navigation** (off by default). With `rodolfo_keynav_enabled` on, pressing 1–9 jumps to the matching header link — a small digit badge renders beside each on wide screens. Applied after the nav is rendered, so it works for both a WordPress menu and the Page-driven fallback; only the first nav on the page is numbered, since `header.php` renders the menu twice (desktop + mobile panel) and duplicate indices would make the lookup ambiguous. The handler ignores keystrokes while typing, while a modifier is held (so ⌘1 for browser tabs still works), and while the ⌘K palette is open.
- **Coming-soon pages.** Any Page can be marked planned-but-unwritten from a box in the editor sidebar, with a one-line note about what it will contain. The page stays in the navigation and shows the note instead of an empty body — the deliberate inverse of `inc/unlist.php`, which hides a published post while keeping its URL live. Coming-soon pages are set `noindex, follow`, since indexing an empty page is worse than not indexing it. Notice markup filterable via `rodolfo_coming_soon_notice`.
- **Site date stamp** (off by default). Appends a date beside the footer copyright line. Cached for an hour and flushed on save, delete, and `transition_post_status`, so a scheduled post going live updates the stamp immediately rather than lagging by up to an hour. Filterable via `rodolfo_site_updated_time` and `rodolfo_site_updated_stamp`. The theme already surfaces per-post dates because answer engines quote visible dates rather than schema; this is the site-level equivalent.

## [3.5.3] - 2026-07-22

### Changed
- **Documented why the Markdown parser fallback is retained.** Tier 3 of `rodolfo_docs_parser()` never executes in a correctly packaged release, so it reads as dead code and was one `git blame` away from deletion. It exists because Parsedown 1.8.0 is unmaintained (2019; CI stops at PHP 8.1): if a future PHP release breaks it at instantiation, the catch falls through to the fallback and the Documentation tab degrades instead of fatalling wp-admin. That mitigation is what made bundling an unmaintained library acceptable. The rationale is now recorded in both `inc/docs.php` and `docs/ssot.md`, with its known limit stated (the guard wraps construction, not every parse call), and pinned by two test assertions.

## [3.5.2] - 2026-07-22

### Changed
- **Release checklist now names all five documentation files individually** (`docs/changelog.md`, `readme.txt`, `docs/ssot.md`, `docs/readme.md`, `docs/upgrading.md`). "Update all docs" had been the instruction for several releases, and `readme.md`/`upgrading.md` still went stale three separate times — naming `package.json` explicitly is what stopped the same drift there, so the same fix is applied here.
- **Archived the completed 1.0.2 remediation roadmap** in `docs/upgrading.md`. All 25 items had been ticked since 1.4.0, but a finished plan sitting in a living document invites re-reading it as outstanding work. Replaced 78 lines of per-phase checklist with a 21-line summary of what the phases covered, pointing at `changelog.md` 1.0.3–1.4.0 as the durable record. `upgrading.md` is now unambiguously about upgrade paths, not planning.

## [3.5.1] - 2026-07-22

### Added
- **Theme diagnostics report** (`inc/system-report.php`), replacing the six-field System Info tab. Scope is deliberately limited to troubleshooting *this theme* — WordPress already ships Site Health for general environment auditing, so the report does not restate server paths, plugin inventories, database tables or unrelated constants. Four sections: **Environment** (only versions a theme feature depends on, plus whether minified or `SCRIPT_DEBUG` sources are being served), **Feature health** (can each optional feature actually run — GD/FreeType for social cards, pretty permalinks for the virtual `/tags/` and `/llms.txt` routes, which Markdown tier the docs viewer resolved, self-hosted Inter presence, and whether an active SEO plugin has stood the theme's meta tags and schema down), **Active settings** (values that explain reported behaviour — notably an assigned WordPress menu silently overriding the Navigation tab, and a sidebar enabled but empty), and **Utility pages** (present / empty / missing).
- **Download report (JSON)** via `admin_post`, capability-gated to `manage_options` and nonce-checked, in the `{ section: { label, results: [ {header, value} ] } }` shape support workflows expect.
- The tab links to **Tools → Site Health** for the general environment checks it intentionally omits. Sections filterable via `rodolfo_system_report`.

## [3.4.0] - 2026-07-19

### Changed
- **`functions.php` split into structural modules** — a pure move, no behavioural change. It had grown to 2,092 lines / 59 functions (41% of all theme PHP), with at least five coherent modules trapped inline. Extracted verbatim into the `inc/` pattern the theme already uses: `icons.php` (Lucide SVG library), `nav.php` (Page-driven nav, privacy resolver, footer utility links), `options-page.php` (Theme Options screen, settings registration, sanitizers, social registry), `maintenance.php` (holding page), `performance.php` (emoji/embed/jQuery-Migrate toggles + script injection). `functions.php` is now ~300 lines: constants, theme supports, enqueues, the module loader, and small template helpers (excerpt, reading-time wrapper, category meta, customizer, author card, comment callback). Structural modules load first since templates and other modules call their helpers at render time. All functions keep their names and signatures, so child themes are unaffected.
- Test harness `$funcs` now concatenates `functions.php` with the five structural modules, so assertions test behaviour rather than file layout; new assertions pin each module's existence, registration, and key ownership (e.g. `rodolfo_icon` lives in `icons.php`), plus a guard that `functions.php` stays under 400 lines.

## [3.3.2] - 2026-07-19

### Fixed
- **Duplicate reading-time estimates.** 3.2.0 added a new reading-time engine without noticing the theme already had one (`rodolfo_get_reading_time()`, 200wpm, always shown on index and single views) — so single posts could show two different estimates, and the new "enable" option was meaningless because the old display ignored it. Consolidated: one engine (`inc/reading-time.php`, 220wpm, code-weighted, filterable); `rodolfo_get_reading_time()` is now a back-compat wrapper delegating to it, so templates and child themes keep working; the option now governs every display and **defaults on**, preserving the theme's historical always-on behaviour; and both display sites guard their surrounding markup so switching it off leaves no orphaned clock icon or separator dot.

## [3.3.1] - 2026-07-19

### Fixed
- **Site setup could never fill pages that were already empty.** Starter content arrived in 2.12.0, but only for pages setup created itself — the loop hit `continue` on any existing page, so utility pages created by an earlier version stayed blank forever and "Create anything missing" could not repair them. Setup now backfills starter content into a page that exists but has empty content. The never-overwrite guarantee is unchanged: any page with real content is left strictly alone, and nothing is ever deleted. The button is relabelled "Create missing pages & fill empty ones" to match.

## [3.3.0] - 2026-07-19

### Changed
- **Light is now the default appearance everywhere.** Previously the site followed the visitor's operating-system `prefers-color-scheme` setting on a first visit, so a visitor with a dark OS saw a dark site before touching anything. Dark mode is now strictly opt-in: the site starts light and only turns dark if the visitor chooses it with the toggle. Their choice still persists in `localStorage` across visits, and the toggle itself is unchanged.
- The maintenance / coming-soon holding page no longer follows `prefers-color-scheme` either — it is pinned light to match. (The block editor was already pinned light.)
- **Social share cards now default to light**, matching the site's own default appearance. The dark slate→blue gradient remains available under Theme Options → Content → Social share cards.

> **Note on the trade-off:** defaulting to light means overriding a visitor's stated OS preference on their first visit. That is a deliberate design choice — the theme's typography and colour work are tuned for light — rather than an oversight. Sites that would rather respect the OS setting can restore the old behaviour by filtering the head script, or by setting `localStorage.theme` from their own code.

## [3.2.0] - 2026-07-19

### Added
- **Block editor parity** (`inc/editor.php`, `assets/css/editor.css`). The front end renders posts through Tailwind Typography, but the editor knew nothing about it — authors composed in one typeface and published in another, so long-form posts only looked right after a preview. `editor-styles` now loads the compiled theme CSS into the editor canvas, with a shim mapping `.editor-styles-wrapper` and `.wp-block` to the same scale, content width and serif headings used on the front end.
- **Editor colour palette and font sizes.** `editor-color-palette` registers the brand cobalt, the neutral ramp and all eight category accents — derived from `rodolfo_category_palette()` so the two can never drift. `editor-font-sizes` mirrors the front-end type scale. Gradients (which the theme never uses) are disabled; custom colour picking stays available. Both filterable via `rodolfo_editor_palette` and `rodolfo_editor_font_sizes`.
- **Estimated reading time** (`inc/reading-time.php`), off by default. Derived from word count at a filterable 220 wpm, with code blocks weighted at half rate since scanning code is slower than reading prose. Shown in the post meta line beside the date. Companion to the existing progress bar.
- **Post series** — a `rodolfo_series` taxonomy for multi-part guides, with a "Part N of M" box listing every part and linking previous/next. Ordered by `menu_order` then date, cached per term and flushed on save. Categories group by subject and tags by topic; neither expresses sequence, which is the gap this fills. A series of one renders nothing.
- **`searchform.php`** — without it, `get_search_form()` fell back to core's unstyled markup, so any plugin or block calling it rendered something visibly foreign to the theme.
- **Full-content RSS** option, off by default. Serves complete posts to feed readers, matching the indie-web posture the theme already takes elsewhere.
- **Light social-card variant.** The generated share image was always the dark slate→blue gradient; `rodolfo_card_variant` adds a light alternative with ink-on-light text.
- **Optional widget sidebar** (`inc/sidebar.php`, `sidebar.php`), off by default and scoped (archives / posts / everywhere). The single centred column is what makes the theme comfortable to read, so the setting says plainly that a sidebar works against the design. It also stays hidden when enabled but empty, rather than leaving a dead column.

### Fixed
- **Threaded comments never nested.** `comment-reply` was never enqueued, so clicking Reply only jumped to the form and every comment landed flat at depth 1 regardless of the Discussion settings. Now enqueued on singular views when threading is on.

## [3.1.1] - 2026-07-19

### Changed
- Maintenance mode reverted to `wp_die()` as the entry point (per maintainer preference), with the themed presentation preserved via the `wp_die_handler` filter rather than a standalone renderer. Output is visually identical to 3.1.0; the difference is that core's bail-out path and plugin `wp_die` filters are back in play. `rodolfo_maintenance_page()` is replaced by `rodolfo_maintenance_die_handler()`.

## [3.1.0] - 2026-07-19

### Changed
- **Maintenance mode keeps `wp_die()` but is fully themed.** `wp_die()` prints its own complete document with a hardcoded stylesheet (Open Sans, `#f1f1f1` page, thin-bordered white box) and emits that `<style>` *after* the message body, so styles passed in the message are overridden — the default look cannot be changed from the message argument. The supported route is the `wp_die_handler` filter, which replaces the handler wholesale: `rodolfo_maintenance_die_handler()` now owns the entire document while `wp_die()` remains the entry point, so core's bail-out semantics and any plugin filters on `wp_die` still apply. The handler renders the site logo when one is set, a centred card, the brand cobalt accent, system dark-mode support, and a gently animated fish suppressed under `prefers-reduced-motion`. The filter is registered only for the maintenance bail-out, so it affects nothing else.
- **Default copy is warmer.** Maintenance now reads "The fish are swimming away", coming-soon "The fish are still being arranged". The headline carries the personality; the subtext always states plainly that the site is returning, so a visitor is never left wondering whether it is permanently broken.

### Added
- `rodolfo_maintenance_title` and `rodolfo_maintenance_body` options (Theme Options → General → Maintenance mode) override the headline and message. Both blank by default, falling back to the built-in copy.
- `rodolfo_maintenance_copy` filter for programmatic overrides, receiving the mode so maintenance and coming-soon can differ.
- The holding page sends `noindex, nofollow` on 503 and `noindex, follow` on 200, plus `nocache_headers()`, so a cached holding page cannot outlive the outage. Logged-in users below the bypass capability get a Dashboard link.

## [3.0.1] - 2026-07-18

### Removed
- **GitLab social link** (per maintainer selection). The `gitlab` entry is dropped from `rodolfo_social_platforms()`, its `rodolfo_social_gitlab` option removed from the sanitizer map, and the glyph removed from `rodolfo_icon()`. A stored value lingering in the database is harmless — it is simply never read or rendered. Fifteen platforms remain, including GitHub.

## [3.0.0] - 2026-07-18

Milestone release consolidating the 2.10–2.14 line: documentation is now readable
inside the dashboard, Theme Options is reorganised into eight tabs, and the
Donate button can place itself. **No breaking changes** — no option was renamed
or removed, the settings export/import format is unchanged, and upgrading from
any 2.x release preserves every stored value. The major bump marks the scope of
the work, not a compatibility break.

### Added
- **Email as a social link.** A new `rodolfo_social_email` option adds a mail icon to the footer social row, completing the set used by the blog the theme is modelled on (GitHub, LinkedIn, Twitter, email). The field takes a bare address — `you@example.com` — and the `mailto:` scheme is built at render time by the new `rodolfo_social_href()` resolver, so users are never asked to type a scheme.
- `rodolfo_social_platforms()` entries now accept an optional third element, the field type (`url` by default, or `email`). This drives both the admin `<input type>` and href construction, so future non-URL platforms need no new branching in the footer or options page.

### Fixed
- **`package.json` version drift.** It had been left at 2.10.0 through four releases because the release checklist did not list it. Now synced to 3.0.0, and the checklist in `ssot.md` names all four version locations.
- **Broken design-reference URL.** `docs/readme.md` linked the inspiration blog as `rodolfo.berrios.com`, which is not a valid domain; the correct URL is `https://rodolfoberrios.com/`. The canonical URL is now recorded in `ssot.md` and cited consistently in `style.css`, `readme.txt` and `docs/readme.md`, and the wording clarifies the homage is aesthetic rather than architectural (that site runs VuePress, not WordPress).

### Changed
- The email link omits `target="_blank"`/`rel="noopener"`, which would leave an empty tab behind after handing off to a mail client. URL platforms are unchanged.
- `rodolfo_sanitize_social_email()` tolerates a pasted `mailto:` prefix and stores the bare address, since every other field in the Social Links tab expects a full URL and typing the scheme is a natural mistake. An invalid address is stored empty and the icon is skipped, so a malformed entry can never render a broken link.

## [2.13.2] - 2026-07-18

### Changed
- **Parsedown is now the sole parser in normal operation**; the built-in renderer was cut from a parallel ~208-line implementation down to a minimal fallback (~120 lines) used only when no Parsedown is available at all. Maintaining two full Markdown implementations meant two sets of edge cases to keep in step; the fallback now covers just enough for the docs to stay readable (headings, lists, code, blockquotes, rules, links, emphasis) and lets tables and task lists degrade gracefully rather than reimplementing them.
- Table styling retargeted from the fallback's `.rodolfo-docs-table` class to plain `<table>` inside `.rodolfo-docs-body`, since Parsedown emits unclassed tables. Without this, every table in the Reference doc rendered unstyled.

### Fixed
- **Task lists rendered as literal `[x]` / `[ ]` text.** Parsedown 1.8.0 has no task-list support, so the 25 checkbox items in the Upgrading doc showed their raw markers once Parsedown became the primary engine. They are now converted to styled check/box glyphs in a post-processing pass over Parsedown's output, leaving Parsedown authoritative for parsing the list itself.

### Removed
- `rodolfo_docs_table_cells()`, orphaned by the fallback no longer parsing tables.

## [2.13.1] - 2026-07-18

### Changed
- **Documentation now renders with Parsedown** when available, replacing the built-in renderer as the primary engine. Parsedown handles Markdown the built-in renderer does not — nested lists, setext headings, reference links, indented code blocks, emphasis edge cases — so the tab no longer degrades if the docs grow beyond their current subset.
- Engine resolution is three-tier: an already-loaded `Parsedown` class wins, then the bundled `vendor/parsedown/Parsedown.php`, then the built-in renderer. Preferring an existing copy is essential — Parsedown declares a global, unguarded `class Parsedown`, so loading a second copy is a fatal error; the `class_exists()` guard in `rodolfo_docs_parser()` is what makes bundling safe. Keeping tier 3 means the tab still works if `vendor/` is stripped from a distribution.
- Parsedown runs with `setSafeMode(true)` and `setMarkupEscaped(true)`. Output from either engine passes through the shared `rodolfo_docs_kses()` allowlist, which now also covers the tags Parsedown can emit (`h1`, `del`, `img`, table alignment attributes).
- Cache key now includes the engine name, so switching engines cannot serve stale output rendered by the other.

### Added
- Bundled Parsedown 1.8.0 (MIT, Emanuil Rusev) at `vendor/parsedown/`, with its licence file, and a Credits section in `readme.txt`.

## [2.13.0] - 2026-07-18

### Added
- **Documentation in the dashboard** (`inc/docs.php`) — a new **Theme Options → Documentation** tab renders the theme's own Markdown files (`docs/readme.md`, `upgrading.md`, `changelog.md`, `ssot.md`) as formatted HTML, with a sub-nav to switch between them. The `.md` files remain the single source of truth: nothing is duplicated or hardcoded, and editing a file updates the tab on the next load. No new admin page is added — it is a panel in the existing options screen.
- Self-contained Markdown renderer covering the subset the docs use (ATX headings, pipe tables, ordered/unordered/task lists, blockquotes, fenced code, rules, inline code/links/emphasis). No library or Composer dependency: WordPress ships no Markdown parser, and bundling one would add third-party code to a WP.org submission for an admin-only convenience.
- Rendered HTML is cached in a transient keyed on the file's `filemtime()` and the theme version, so edits invalidate the cache automatically without a manual flush.
- Registry is filterable via `rodolfo_docs_files` so a child theme can add or remove documents.

### Security
- The renderer escapes every text node before emitting tags, then passes the assembled HTML through `wp_kses()` with an explicit allowlist, so raw HTML in a Markdown source is inert. Link targets are restricted to `http`, `https` and `mailto`. Doc paths are resolved against the theme directory with a containment check, so a filtered entry cannot traverse outside it.

## [2.12.0] - 2026-07-18

### Fixed
- **Privacy Policy at `/privacy` and `/privacy-policy` are now treated as the same page.** A new shared resolver `rodolfo_privacy_page()` checks Settings → Privacy first, then falls back to a published page at either slug. It is used consistently by nav exclusion (so the privacy page is no longer duplicated in the header when Settings → Privacy is unset), the footer utility link (which previously vanished silently in the same situation), and site setup (which previously created a second page if one already existed at `/privacy`). Setup now also recognises alternate slugs for the other scaffolded pages (`faqs`, `terms-of-use`, `accessibility-statement`, etc.) and will not create duplicates.

### Added
- **Scaffolded pages now ship starter content** instead of being created blank (`inc/setup.php`). FAQ and Accessibility get genuinely useful skeletons; Privacy and Terms get a clearly-marked draft outline — flagged "replace this before publishing" and explicitly not legal advice — with the Privacy page pointing at WordPress's own generator at Settings → Privacy. Content is emitted as block markup so it opens cleanly in the editor.
- **Site setup now distinguishes "Present but empty"** from "Present", with an inline link to edit the page, so a blank scaffolded page is no longer reported as done.
- **Menu-override warning on the Navigation tab.** When a WordPress menu is assigned to the Primary or Secondary location it silently overrides every per-page Header/Footer/Hidden setting — the long-standing behaviour, but invisible from the settings screen. The Navigation tab now shows a callout naming the affected area(s) and linking to Appearance → Menus → Manage Locations.

## [2.11.1] - 2026-07-17

### Fixed
- **Reading progress bar could render invisibly.** A saved bar height of 1px (or blank/0) produced a hairline that was effectively invisible, making the feature look broken. Height is now clamped to 2–20px by a dedicated sanitizer (`rodolfo_sanitize_progress_height`, replacing `absint`) — values below 2 fall back to the 4px default. The render path also floors at 2px (`max( 2, … )`), and the admin input's `min` is raised to 2 with an explanatory hint. No change to the stored default (4px). Behaviour otherwise unchanged: the bar still shows only on singular posts/pages and only when there's enough content to scroll.

## [2.11.0] - 2026-07-17

### Added
- **Automatic Donate placement** (`inc/donate.php`) — a new `rodolfo_donate_auto` option (Theme Options → Content → Donate button) appends the donate button to the end of the content automatically, with no shortcode or block: `posts` (posts only) or `posts_pages` (posts and pages). Guarded to the main query in the loop, skips feeds, and renders nothing unless a PayPal email or custom URL is configured. New shared helper `rodolfo_donate_button()` centralises URL resolution + escaping for both the auto-placement filter and the `[donate]` shortcode. Filter `rodolfo_donate_auto_block` customises/suppresses the auto-placed markup per view. Whitelist sanitizer `rodolfo_sanitize_donate_auto()`; `.rodolfo-donate-auto` spacing in style.css.
- **GitHub social field restored** — `rodolfo_social_github` is back in `rodolfo_social_platforms()` and the sanitizer map (`esc_url_raw`), so it renders as a footer social circle again (hidden when blank), reversing its 2.6.0 removal. The GitHub glyph was already present in `rodolfo_icon()`.

### Changed
- **Theme Options tab reorganisation** — the overloaded General tab is split into three: **General** (footer text, sitemap override, site setup, maintenance mode), a new **Navigation** tab (per-page header/footer/hidden placement + order, built-in footer links), and a new **Content** tab (reading progress bar, Donate button). No settings were renamed or removed — only regrouped — so existing stored values and the export/import format are unchanged. The tab switcher is data-driven, so the two new tabs wired up without JS changes.

## [2.10.0] - 2026-07-17

### Added
- **Visible breadcrumbs** (`inc/breadcrumbs.php`) — `rodolfo_breadcrumbs()` renders Home › Category › Title on single posts (called from `single.php`); defers to `rank_math_the_breadcrumbs()` / `yoast_breadcrumb()` when present so the visible trail always matches the plugin's BreadcrumbList schema. Gate/extend via `rodolfo_breadcrumbs_enabled` filter.
- **AI-crawler robots.txt guidance** (`inc/llms-txt.php`) — appends Allow rules for GPTBot, OAI-SearchBot, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended, CCBot plus a pointer to /llms.txt, to the WordPress-generated virtual robots.txt only (a physical file is never touched). Opt out: `add_filter( 'rodolfo_ai_crawlers_welcome', '__return_false' )`.

### Changed
- **LCP image handling** — the homepage hero and single-post featured images now load with `loading="eager"` + `fetchpriority="high"` + `decoding="async"` instead of being lazy-loaded, improving Largest Contentful Paint.



## [2.9.0] - 2026-07-17

### Added
- **Visible "Updated" date** (`content-single.php`) — shows `Updated {modified date}` beside the publish date when the post was revised > 1 day after publishing, both wrapped in `<time datetime>` for machine extraction. AEO freshness signal; schema alone isn't what AI engines quote.
- **llms.txt** (`inc/llms-txt.php`) — virtual `/llms.txt` route (llmstxt.org convention) serving a live Markdown site summary: name, tagline, primary-nav pages, 20 recent posts with trimmed excerpts, top-10 topics. `text/plain` + `X-Robots-Tag: noindex`; one-time rewrite flush (`rodolfo_llms_rewrite_v`); a physical llms.txt still wins. Filters `rodolfo_llms_txt_enabled` / `rodolfo_llms_txt`.

### Fixed (WCAG pass)
- **Contrast (1.4.3 AA)** — meaningful text set in `text-slate-400` (≈2.9:1 on white) raised to `text-slate-500` (4.76:1) with the stronger `dark:text-slate-400` kept for dark mode: card topic line + card date, header tagline, author-card "Written by" label, comment dates + Reply link, tag-cloud counts. Placeholder/decorative tints unchanged (exempt).
- **Reduced motion (2.3.3)** — global `prefers-reduced-motion: reduce` override in style.css kills theme transitions/animations (incl. the reading-progress fill's inline transition, via !important).
- **Focus management (2.4.3)** — the ⌘K palette now saves `document.activeElement` on open and restores focus on close; `main.min.js` regenerated.
- **Focus visible (2.4.7)** — explicit `:focus-visible` outlines for the FAQ summary, click-to-tweet action, donate button, and proof citation links.



## [2.8.0] - 2026-07-17

### Added
- `rodolfo_faq_jsonld` filter — the [faq] FAQPage JSON-LD intentionally stays on under an SEO plugin (it derives from Rodolfo's own shortcodes, which the plugin can't see); return false to suppress it on pages that mix [faq] with the plugin's own FAQ block.
- **Usage-proof callout** (`inc/aeo.php`) — `[proof metric="" label="" cite="" cite_label=""]optional body[/proof]` renders a verifiable-result aside (display-serif metric in brand accent, measure label, optional backing link) for placing a specific customer result within the first 200 words. Completes the AEO set from 2.7.0 (answer/faqs/compare). Styles `.rodolfo-proof*` (light/dark).



## [2.7.0] - 2026-07-16

### Added
- **AEO content blocks** (`inc/aeo.php`) — on-page structures AI answer engines cite: `[answer label=""]` quick-answer box (2-3 sentence direct answer above the fold); `[faqs title=""]` + `[faq q="" open=""]` accessible `<details>` FAQ items that also emit **FAQPage JSON-LD** in `wp_footer` built from the exact on-page Q/As (only on pages where [faq] rendered — never duplicates an SEO plugin's site-level schema); `[compare a="" b="" caption=""]` comparison table (one "Factor | A | B" row per line). Styles `.rodolfo-answer` / `.rodolfo-faq*` / `.rodolfo-compare` in style.css (light/dark).



## [2.6.0] - 2026-07-16

### Added
- **Data-driven social platform registry** — `rodolfo_social_platforms()` (label + placeholder per key) now drives the Social Links tab fields, the option sanitizers, and the footer icon row, so adding a platform is a one-line change. Platforms: twitter, facebook, instagram, youtube, tiktok, threads, linkedin, pinterest, reddit, whatsapp, telegram, gitlab, medium, substack (options `rodolfo_social_{key}`, all `esc_url_raw`; icon renders only when a URL is set). Brand glyphs added to `rodolfo_icon()` (stroke-based where Lucide has them; fill-based paths otherwise; Threads uses an at-sign glyph).

### Changed
- **Theme Options page redesign** (previously unversioned): refined header gradient with theme-accent cobalt, segmented tab control, soft-carded sections/tables, consistent input/focus styling, sticky-feel form footer, responsive stacking; version badge shows "v{n}" without the theme name; text fields fill the control column.

### Removed
- **GitHub social field** (per maintainer selection). A stored `rodolfo_social_github` value is no longer displayed; the option is dropped from the sanitizer map (harmless if it lingers in the DB).



## [2.5.0] - 2026-07-16

### Changed
- **Built-in footer links expanded to five, all editable.** `rodolfo_footer_util_config()` now covers FAQ, Terms of Use, Privacy Policy, Sitemap, RSS. Each carries **show**, a **custom label** (rename; blank = default via `rodolfo_footer_util_default_label()`), and **order**. The Navigation panel's built-in-links table gains a Label column. A link auto-skips when its destination is absent (FAQ → page slug `faq`; Terms → `terms`/`terms-of-use`; Privacy → Settings → Privacy; Sitemap → override or core; RSS → feed). Sanitizer `rodolfo_sanitize_footer_util()` now keeps a sanitized `label` and the five keys.
- **Site setup** (`rodolfo_setup_pages()`) now scaffolds FAQ + Terms of Use (slugs `faq`/`terms`) alongside Privacy Policy + Accessibility. Still create-only; never overwrites or deletes.


## [2.4.0] - 2026-07-16

### Changed
- **Navigation is now fully controllable.** Replaced the single "tick to move to footer" checkbox model with a per-page control: each top-level Page has a **Location** (Header menu / Footer links / Hidden) and an **Order** number, plus a **Built-in footer links** table giving Privacy Policy / Sitemap / RSS their own show toggle and order. Footer links (footer-assigned Pages + enabled built-in links) render in one shared ascending-order space, so the owner controls exactly what appears and in what order. New options `rodolfo_nav_locations`, `rodolfo_nav_order`, `rodolfo_footer_util` (sanitizers `rodolfo_sanitize_nav_locations` / `rodolfo_sanitize_int_map` / `rodolfo_sanitize_footer_util`); helpers `rodolfo_nav_location()`, `rodolfo_nav_order_val()`, `rodolfo_nav_pages_in()`, `rodolfo_footer_util_config()`, `rodolfo_footer_nav_items()`.

### Fixed
- **Duplicate "Privacy" inconsistency.** The WordPress-designated Privacy Policy page is now excluded from the editable Pages list and represented once by the built-in "Privacy Policy" footer link (labelled from the actual page title), so it can no longer appear twice or under two names. Navigation and Site setup now refer to it consistently.

### Migration
- One-time `rodolfo_migrate_nav_excluded()` (admin_init, gated by `rodolfo_nav_migrated_v`) converts any legacy `rodolfo_nav_excluded_pages` IDs to `footer` in the new location map, then removes the old option.


All notable changes to this project will be documented in this file.

## [2.3.1] - 2026-07-16

### Added
- **PayPal-by-email donations** (`inc/donate.php`) — new `rodolfo_paypal_email` + `rodolfo_donate_currency` options. `rodolfo_paypal_donate_url()` builds PayPal's hosted `/donate/` URL (business + item_name "Support {site}" + currency_code) from a validated email, so owners only enter their PayPal email. Resolution order in `rodolfo_donate_url()`: explicit `rodolfo_donate_url` override → PayPal email. Shortcode gains `email`/`currency` attrs. `rodolfo_sanitize_currency()` added. Still no card/token/webhook handling in the theme — PayPal hosts the flow.

## [2.3.0] - 2026-07-16

### Added
- **Reading progress bar** (`inc/reading-progress.php`) — a fixed scroll-fill bar on singular posts/pages, self-contained (prints its own scoped CSS + vanilla JS in the footer; no main.js dependency). Options: `rodolfo_progress_enabled` (default on), `rodolfo_progress_height` (px), `rodolfo_progress_position` (top/bottom); brand-accent fill. Absorbs the "Reading Progress Bar" plugin.
- **Donate surface** (`inc/donate.php`) — `[donate url="…" label="…"]` shortcode rendering an accented button (heart icon) that links to the owner's configured gateway (`rodolfo_donate_url`/`rodolfo_donate_label` options). New `heart` icon; `.rodolfo-donate` styles.
  - **Scope note:** only the donation *surface* is nativised. Payment PROCESSING (PayPal/Stripe gateways, orders, IPN/webhooks, PCI-sensitive handling — i.e. the Easy Donation engine) is intentionally NOT reimplemented in the theme; it belongs in a plugin so it survives theme switches and follows its own security/update cadence. The button integrates with that gateway by URL.

## [2.2.1] - 2026-07-16

### Changed
- **Safelisted the layout utilities in `input.css`** via Tailwind v4 `@source inline(...)` (gap/margin/padding/width/height scales, positions, responsive grid-cols). The next `npm run build` bakes them into `theme.min.css`, after which the temporary `@backfill` block in `style.css` can be removed. No runtime change (the backfill still covers them until then). Full conflict/integrity sweep passed: 89 unique `rodolfo_*` functions with no double-declares, balanced braces across all templates/modules, no duplicate option keys or shortcode/hook collisions, no remote asset references.

## [2.2.0] - 2026-07-16

Natively absorbs four standalone plugins so their features survive without extra installs. (WPS Hide Login was deliberately NOT merged — changing the login URL from a theme is fragile and risks lockouts on theme switch; keep it as a plugin.)

### Added
- **Click-to-Tweet** (`inc/click-to-tweet.php`) — `[bctt]` shortcode (attrs `tweet`/`via`/`url`/`prompt`/`nofollow`; also wraps enclosed content), rendering an accented pull-quote with an X intent link and optional UTM tagging on the shared URL. Registers only if the "Better Click To Tweet" plugin isn't already providing `[bctt]`. New `rodolfo_twitter_handle` option (Social tab). Styles `.rodolfo-ctt*` in style.css (light/dark).
- **Unlist posts** (`inc/unlist.php`) — "Unlist this post" checkbox in the Publish box (nonce-guarded). Unlisted IDs kept in `rodolfo_unlisted_posts` (mirrors `_rodolfo_unlisted` meta) and excluded from all front-end listing queries via `pre_get_posts` (`post__not_in`); singular URL stays live and gets `noindex` via `wp_robots`. Cleaned up on delete.
- **Branded login** (`inc/login.php`) — replaces the wp-login logo with the Custom Logo (or Site Icon), links it home, titles it with the site name, and tints the primary button/focus rings with the brand accent. Uses Customizer media — no hand-placed file needed.

### Changed
- **Search/thin-archive noindex** folded into `inc/meta-tags.php`: emits `noindex,follow` on search, paged, author, and date views (search returns early). Absorbs the "WP SEO Search" plugin; still self-disables under a general SEO plugin.

## [2.1.1] - 2026-07-15

### Changed
- **Footer "Explore Topics" column is now content-only.** It always lists the site's top categories; the `footer` menu-location branch (which could surface Page links like Terms/Accessibility/Tags, duplicating the primary/secondary nav) was removed, and the `footer` nav-menu location is unregistered. Setup no longer creates or reports a Footer menu; `rodolfo_setup_status()` and admin/setup copy updated accordingly.

## [2.1.0] - 2026-07-15

### Added
- **Page-driven primary navigation.** The header menu now builds automatically from top-level published Pages (when no `primary` menu is assigned). New Theme Options → **Navigation** section lists every top-level Page with a checkbox; ticking one removes it from the header and shows it only in the secondary (footer) nav. Backed by option `rodolfo_nav_excluded_pages` (`rodolfo_sanitize_id_list`, int[]), helpers `rodolfo_nav_pages()` / `rodolfo_primary_nav_pages()` / `rodolfo_secondary_nav_pages()` / `rodolfo_nav_excluded_page_ids()`, and a hidden `[0]` sentinel so "untick all" clears the option.

### Changed
- **Footer secondary row** now lists the excluded Pages first, then Privacy · Sitemap · RSS.
- **Removed the Tags nav link.** The all-tags page is already reachable from the header Tags icon, so it is no longer injected into the primary nav. The virtual `/tags/` route and template remain (that is what the icon opens).
- **Retired the Terms/Accessibility footer-URL options** (`rodolfo_link_terms`, `rodolfo_link_accessibility`) and their setup wiring: those are ordinary Pages now, managed under Navigation. The Sitemap override and Privacy (Settings → Privacy) are unchanged.

## [2.0.4] - 2026-07-15

### Changed
- **Larger search palette.** Panel `max-width` 40rem → 52rem, opens slightly higher (`12vh` → `10vh`), input row padding `1rem/1.25rem` → `1.35rem/1.5rem`, and input font `1.05rem` → `1.25rem`.

## [2.0.3] - 2026-07-15

### Fixed
- **Search palette contrast.** The overlay backdrop was too light (`rgba(15,23,42,.55)`, `blur(3px)`), leaving the page faintly visible and the panel hard to discern. Strengthened to `rgba(15,23,42,.78)` with `blur(6px)` so the palette reads clearly against any page content.

## [2.0.2] - 2026-07-15

### Fixed
- **Post cards broke layout (nested anchors).** The card wrapped the entire card in `<a href=permalink>` while the topic-tag line (added 1.11.1) placed `<a href=term_link>` links inside it. Nested `<a>` is invalid HTML, so browsers force-closed the outer anchor and reparented the rest as siblings — ballooning the topic line and overlapping the footer over the excerpt. Rebuilt `content-card.php` with a stretched-link: the card is a positioned `<div>`, the title link carries a `::after` overlay covering the whole card, and `.rodolfo-card-topics` / `.rodolfo-card-meta` get a higher z-index so tag links stay independently clickable. Zero nested anchors.
- **Cards/grid overlap and collapsed spacing.** The shipped `assets/css/theme.min.css` predates markup added in 1.9-2.0 (redesigned card, comma topic line, responsive grids), so utilities like `.mt-4`, `.px-1`, `.gap-x-*`, `.gap-y-*`, `.h-1`, and responsive `grid-cols` were absent - grid rows lost their gap and the card footer lost its top spacing, causing overlap. Added an auto-generated **compiled-utility backfill** block to `style.css` (loads after `theme.min.css`) emitting canonical values for every layout utility (margins, padding, gaps, grid-cols, sizes, inset) used across templates, including responsive breakpoints. Regenerate/remove after `npm run build`.
- Bundled UI layer (Bootstrap) is now ancestor-scoped under `.rodolfo-ui` (its `:root`/`[data-bs-theme]` variables kept global), replacing the 2.0.1 `@layer` approach - cascade layers invert `!important` precedence, so Bootstrap's `!important` utilities were overriding the theme. Scoping confines all UI-layer rules to opt-in `.rodolfo-ui` containers. Stale source maps removed.

## [2.0.1] - 2026-07-15

### Fixed
- **Eliminated core/UI-layer style conflicts.** The entire UI-layer stylesheet (`assets/lib/ui.min.css`) is now wrapped in `@layer rodolfo-ui` (`@charset` kept first; source-map comment kept outside the layer). Per the CSS cascade, unlayered rules beat layered ones, so every core/theme rule (Tailwind Preflight, compiled utilities, `style.css`) now wins collisions with the UI layer regardless of specificity or load order — resolving the overlapping resets and shared utility names (spacing, `.d-none`/`.hidden`, border/rounded/shadow) noted as a 2.0.0 trade-off. The UI layer still applies wherever the core does not compete. Enqueue comment in `functions.php` updated; no markup change.

## [2.0.0] - 2026-07-15

Major release: the theme now bundles a full UI layer alongside its core styles.

### Added
- **Bundled UI layer** (MIT; Bootstrap 5.3.8 under the hood) at `assets/lib/ui.min.css` + `assets/lib/ui.bundle.min.js` (Popper included) — grid, components, and JS widgets (modals, dropdowns, collapse, carousel, tabs, tooltips). Vendored locally; nothing is fetched from the internet.
- Enqueued in `rodolfo_scripts()` under native handles: `rodolfo-ui` loads FIRST and is a dependency of the core `rodolfo-theme` sheet, so the core Preflight reset wins equal-specificity collisions (later-loaded wins). `ui.bundle.min.js` loads in the footer.

### Changed
- Theme-facing naming is native throughout: files `assets/lib/ui.*`, handles `rodolfo-ui`, versioned with the theme (no hard-coded `5.3.8`). The underlying framework and its internal class names (`.btn`, `.row`, `.col`, `.d-*`) are unchanged.

### Known trade-offs
- The core (Tailwind) and UI (Bootstrap) layers ship overlapping resets and some utility class names (spacing, `.d-none`/`.hidden`, border/rounded/shadow); they are NOT namespaced. Scope UI-layer classes to markup you build against the UI layer, and use the core utilities elsewhere. Combined CSS/JS payload is larger. Documented at maintainer request.

## [1.11.1] - 2026-07-15

### Fixed
- **Logo presentation** — uploaded custom logos were forced through `.rodolfo-brand-mark` (fixed square, `border-radius:9999px`, `object-fit:cover`, border), cropping non-circular logos into a bordered disc. Added `.rodolfo-logo-img` (auto width, capped height 2.25rem header / 3rem footer, `object-fit:contain`, no border/circle) and switched both header and footer `<img>` to it; source size changed `thumbnail` → `full` so the aspect ratio isn't pre-cropped. The initial-disc fallback keeps `.rodolfo-brand-mark`.
- **Utility pages in main nav** — pre-1.11.0 setup created a "Footer" menu (Terms/Accessibility/…) that could remain assigned to the `primary` (and `footer`) location, surfacing those links in the header and footer column. Added a one-time `admin_init` migration (`rodolfo_menu_locations_cleanup`, gated by `rodolfo_menu_cleanup_v`) that unassigns ONLY our own auto-created "Footer" menu from those locations — never a user's own menu, never deletes.

### Changed
- **Primary nav fallback curated** — when no `primary` menu is assigned, the header now shows the built-in Tags page + top-level Pages (excluding privacy/terms/accessibility), matching the original Rodolfo "Tags · About" nav, instead of dumping the first 5 categories.
- **Card topic line** — `content-card.php` replaced the coloured category pill with a muted comma-separated topic line (post tags, else categories; up to 3, lowercase, linked), per the original Rodolfo cards. Avatar + author + date footer and the category-colour image hairline are retained.

## [1.11.0] - 2026-07-14

### Added
- **Built-in Tags page** — new `inc/tags.php` registers a virtual `/tags/` route (rewrite rule + query var, one-time auto flush via `rodolfo_tags_rewrite_v` option, `pre_handle_404` guard, `template_include` → bundled `tags-page.php`). Lists every non-empty tag as a linked pill cloud ordered by count. Zero setup: no page, no menu. `rodolfo_tags_url()` handles pretty/plain permalinks; header Tags button always points to it (dropped the old get_page_by_path('tags') dependency, and 'tags' removed from the setup pages).
- **`secondary` nav-menu location** — registered alongside `primary` (header) and `footer`. The below-copyright row now renders a menu assigned to `secondary` when present, else the auto legal/utility links (Privacy/Terms/Accessibility/Sitemap/RSS). Menu location labels clarified.

### Changed
- **Footer tertiary column is automatic** — when no `footer` menu is assigned, the "About the Site" column now fills from the site's top categories under an "Explore Topics" heading (was hardcoded About/Colophon/Privacy/Contact with dead `#` links).
- **Setup no longer creates/assigns a menu** — `rodolfo_setup_run()` wires the scaffolded Terms/Accessibility pages into the Theme Options link fields instead of building a "Footer" menu (which previously caused one menu to appear in several locations). Page creation and privacy-page wiring unchanged.
- **Article card redesign** — `content-card.php` is now a bordered, rounded card (`.rodolfo-card` hover lift/shadow in style.css) with an avatar + author + date footer under a hairline; category pill retained. Inspired by a supplied reference, adapted to Rodolfo's palette.

## [1.10.5] - 2026-07-14

### Changed
- Footer brand mark enlarged to 3.5rem via a `.rodolfo-brand-mark--lg` modifier (header stays at the compact 2.5rem `.rodolfo-brand-mark`), applied to both the logo image and initial-disc fallback.

## [1.10.4] - 2026-07-14

### Fixed
- **Article grid rows collapsed** — 1.10.3 set the grid to `gap-x-8 gap-y-10`, but neither arbitrary utility is in the shipped `theme.min.css` (only `gap-8`), so row spacing dropped to 0 and the card rows touched. Reverted to `gap-8` and added a `.rodolfo-cards` hook in `style.css` (`row-gap:2.5rem; column-gap:2rem`) so spacing never depends on un-compiled utilities.
- **Footer logo mismatched the header** — the footer `<img>` used `w-10 h-10 rounded-full object-cover` with no border while the header carried a border/ring, and both leaned on utilities that could render inconsistently. Introduced a shared `.rodolfo-brand-mark` class (fixed 2.5rem circle, `object-fit:cover`) applied to the logo image and initial-disc fallback in BOTH header and footer, so the mark is pixel-identical in each. Verified: header and footer marks both 40×40, `border-radius:9999px`; row gap 40px.

## [1.10.3] - 2026-07-14

### Fixed
- **Search palette blocked all clicks** — `.rodolfo-search` sets `display:flex`, which overrode the `hidden` attribute's `display:none` (author rule beats the UA `[hidden]` rule). The invisible (`opacity:0`) full-viewport `position:fixed; inset:0` overlay therefore sat on top of every page and swallowed all pointer events, making every link and button appear dead. Added `.rodolfo-search[hidden] { display: none; }`. Verified via `elementFromPoint`: clicks now reach page links. Regression test added.

### Changed
- Tightened homepage vertical rhythm to address excess whitespace: `main` top padding `pt-8/12` → `pt-6/8`; featured hero bottom margin `mb-12` → `mb-10`; Latest Articles heading `text-3xl mb-8` → `text-2xl/3xl mb-6`, wrapper `mb-16` → `mb-10`; card grid gap `gap-8` → `gap-x-8 gap-y-10`. Featured image capped via `.rodolfo-hero-cover { max-height: 440px }`.

## [1.10.2] - 2026-07-14

### Changed
- **Homepage hero rebalanced** — replaced the asymmetric magazine hero (featured + secondary rail) with a single full-width two-column featured story (`md:grid-cols-2`, `items-center`), consuming only the first post. The 2–3 stories that fed the rail now flow into the denser Latest Articles grid. Fixes the large right-side whitespace and height imbalance a tall featured image created beside a short thumbnail rail. Uses only utilities present in the shipped `theme.min.css`. The `.rodolfo-mag*` CSS in `style.css` is now unused (left in place, harmless; safe to prune on the next recompile).

## [1.10.1] - 2026-07-14

### Fixed
- **Magazine-hero secondary column collapsed** — the shipped `theme.min.css` predates the hero's arbitrary utilities (`lg:grid-cols-3`, `lg:col-span-*`, `w-28`/`w-32`, `aspect-[4/3]`), so on real sites the secondary thumbnails rendered at full size and starved their titles to one word per line. Added structural CSS in `style.css` under `.rodolfo-mag*` class hooks (fixed 8rem thumb, `min-width:0` body, the two-column grid, responsive dividers) so the layout holds regardless of the compiled sheet. A recompile makes it redundant but harmless.

## [1.10.0] - 2026-07-14

Design refinements following a visual review. No breaking changes.

### Added
- **Category-gradient placeholder** — `rodolfo_post_thumbnail()` (in `inc/category-color.php`) renders image-less posts as a subtle category-coloured gradient carrying the category emoji (or the site initial) instead of an empty grey box. Wired into the card, homepage hero (featured + secondary), and related-posts grid.
- **Author card** — `rodolfo_render_author_card()` outputs an “About the author” block (avatar, name, bio, archive link) below single posts; skipped when the author bio is empty.

### Changed
- Homepage featured hero now shows an author byline (avatar + name + reading time) beneath the excerpt — adds a mid type/identity tier between the large headline and the body.

## [1.9.0] - 2026-07-14

Completes the roadmap's “Do later” set. No breaking changes.

### Added
- **⌘K / live search command palette** — `inc/search-palette.php` renders a `wp_footer` overlay; `main.js` opens it on ⌘K / Ctrl-K (or “/” when not typing, or by clicking a header search field) and queries the core `wp/v2/search` REST endpoint as you type, with arrow-key/enter navigation. Progressive enhancement — the plain search form still works without JS. Styles in `style.css`.
- **Editorial magazine hero** — the homepage (page 1) now leads with a large featured story beside a column of up to two secondary stories, all consumed from the MAIN query (`the_post()`), so grid/pagination integrity from 1.4.x is preserved. The “Latest Articles” grid only renders when posts remain.
- **Richer social cards** — `inc/social-card.php` now draws the post's category-colour top bar and label, and the author's circular avatar (fetched via `wp_remote_get`, 3s cap, graceful fallback) with name + site name.

### Changed
- Comments heading refreshed with a `message-circle` icon and the display serif; added the `message-circle` icon to `rodolfo_icon()`.
- `assets/js/main.min.js` regenerated.

## [1.8.0] - 2026-07-14

Roadmap release: build-tooling migration, brand identity, and two long-form reading features. No breaking changes.

### Changed
- **Tailwind v4 CLI migration**. Added a real `package.json` (`@tailwindcss/cli`, `@tailwindcss/typography`, `esbuild`) with `build` / `watch` / `minify:js` / `test` scripts, and a CSS-first `assets/css/input.css` (`@import "tailwindcss"`, `@source` scanning, `@theme` tokens, `@plugin` typography). No `tailwind.config.js` (v4 is CSS-first). This ends the “edit-a-class-but-can't-recompile” friction — `npm run build` regenerates `theme.min.css`. (Earlier docs referenced a `package.json`/`tailwind.config.js` that did not actually ship; both are now correct.)
- **Brand accent** — replaced Tailwind's default blue with an owned cobalt-indigo (`#3d54d6`). `input.css` remaps the `blue-*` colour ramp to the brand ramp so every existing utility recolours on recompile; `style.css` mirrors the overrides at runtime so it shows immediately. Per-category accents are unaffected and layer on top.

### Added
- **Display serif titles** — `--font-display` serif stack applied via `.font-display` to the homepage hero, post titles (single + card hero), category headers, and section headings, paired with the Inter body for an editorial voice.
- **`inc/toc.php`** — auto table of contents. Filters `the_content` to inject stable `h2`/`h3` anchor IDs and renders a TOC above the article on single posts with ≥3 headings (threshold filterable). Smooth-scroll + sticky-header scroll offset in `style.css`.
- **`inc/related-posts.php`** — up to 3 related posts by shared tags (topped up from shared categories), rendered as a card grid below single posts, reusing the card visual language and per-category hairline.

## [1.7.1] - 2026-07-14

### Added
- **Inter is now bundled** (`assets/fonts/`, OFL, with `OFL.txt`): `Inter-Variable.ttf` (upright) + `Inter-Italic-Variable.ttf` (italic) variable fonts, and `Inter-Bold.ttf` (static) for the social card. `inc/fonts.php` emits upright + italic `@font-face` (weights 100–900, `font-display:swap`) and preloads the upright file; no remote request. TTF variable fonts ship rather than woff2 (no build step, WP.org-permitted); convert to woff2 later to shrink the download. Silent fallback to the system stack if the files are removed.

## [1.7.0] - 2026-07-14

Adapts infrastructure patterns from the Murtadd theme plus a per-category accent inspired by the Remotive Media palette. No layout or structural changes to existing views.

### Added
- **`inc/schema.php`** — JSON-LD: `Article` on single posts (headline, dates, author, publisher, image), `WebSite` + `Organization` on the front page (with `SearchAction`), and `BreadcrumbList` on singular views. Self-disables when an SEO plugin is detected.
- **`inc/meta-tags.php`** — per-view meta description, Open Graph, and Twitter Card tags on `wp_head` @ 5. `rodolfo_seo_plugin_active()` detects Yoast / Rank Math / AIOSEO / SEOPress / The SEO Framework and stands both this and schema down to avoid duplicate tags; overridable via the `rodolfo_seo_plugin_active` filter.
- **`inc/social-card.php`** — GD-rendered 1200×630 share card carrying the post title on the slate→blue gradient, served from `?rodolfo_card=<id>` and used as the OG image when a post has no featured image. Degrades to the site icon if GD is unavailable; uses `assets/fonts/Inter-Bold.ttf` when present, else the GD bitmap font.
- **`inc/category-color.php`** — per-category accent colour. Author-picked (`category_color` term meta, `<input type=color>` on both category term screens, saved via the existing `rodolfo_save_category_icon` nonce) with a stable auto-assignment from an 8-colour muted palette keyed by term ID. Applied to category pills (`rodolfo_category_pill_style()`, a tinted background + solid-hue text via 8-digit hex alpha) and a hairline on cards and the category header. Slate/blue remains the primary UI colour.
- **`inc/favicon.php`** — serves the bundled `assets/icons/site-icon.*` mark (SVG + 512/192/180/32/16 PNGs) on `wp_head`/`admin_head`, short-circuited by `has_site_icon()`.
- **`inc/fonts.php`** — self-hosted Inter via `@font-face` + preload, emitted only when `assets/fonts/Inter-Variable.woff2` exists; otherwise silent (system stack). Completes the Google-Fonts removal from 1.5.0.
- **`inc/setup.php`** — idempotent scaffolding (never overwrites, never deletes): creates Privacy / Terms / Accessibility / Tags pages, wires `wp_page_for_privacy_policy`, and builds a Footer menu assigned to the `footer` location. Runs on `after_switch_theme` and is re-runnable from Theme Options → General → “Create anything missing”, with a present/missing status table.
- **`tests/test-theme.php`** — no-WordPress static test harness (`php tests/test-theme.php`). Every assertion encodes a shipped regression (pagination `.navigation` selector, constrained header logo, empty-menu nav fallback, no CDN icons/fonts, no admin-email Gravatar, `md:flex-row`, option sanitizers, module wiring, `.min` twin).

### Changed
- **Asset-suffix discipline**: `RODOLFO_ASSET_SUFFIX` (`''` under `SCRIPT_DEBUG`, else `.min`) now drives the script enqueue; `assets/js/main.min.js` ships as the live twin of `main.js`. `RODOLFO_VERSION` constant added.
- `functions.php` requires the seven `inc/` modules; `str_word_count()`-era comment untouched.

## [1.6.1] - 2026-07-13

### Fixed
- **Pagination CSS never applied**: passing a custom `class` to `the_posts_pagination()` REPLACES the default `pagination` class on the `<nav>`, so the 1.5.1 selectors (`.pagination …`) matched nothing. Selectors retargeted to `.navigation` / `.page-numbers`, which core always outputs.
- **Header custom logo rendered at native size**: `the_custom_logo()` outputs the full-size image wrapped in its own home link — nesting an `<a>` inside the header's link (invalid HTML) and blowing up the sticky header. Replaced with a manually constrained 40px thumbnail `<img>`, matching the footer's approach.
- **Primary nav blank with an empty menu**: same bug fixed in the footer in 1.5.1 — an assigned-but-empty primary menu rendered nothing. Desktop nav and mobile panel now share `rodolfo_primary_nav_links()`, which falls back to category links when the menu output is empty.

## [1.6.0] - 2026-07-13

### Changed
- **Theme Options redesigned** (modern-minimalist): gradient hero header (slate→blue) displaying the live theme version as a badge, pill-style tabs, rounded 16px panel cards, softened inputs with focus rings, gradient Save button. All CSS remains inline-enqueued only on the options screen.
- Theme Options labels clarified and ordered by setup flow: tabs are now General → Social Links → Performance → Custom Scripts → System Info; "Footer text override" → "Copyright line"; "Legal links" → "Footer links".
- Documentation reorganised: all maintainer/developer `.md` docs moved to `docs/` (`readme.md`, `changelog.md`, `upgrading.md`, `ssot.md`); `readme.txt` stays in the theme root per WordPress.org requirements.

## [1.5.1] - 2026-07-13

### Fixed
- **Pagination was unstyled**: `the_posts_pagination()` markup (`nav.pagination > .nav-links > .page-numbers`) had no styles — numbers rendered as bare inline text. Added proper pill-style pagination CSS (current state, hover, dots, dark mode) plus the WordPress core `.screen-reader-text` class to `style.css`.
- **Footer bottom bar stacked on desktop**: `md:flex-row` was missing from the stale compiled Tailwind sheet; backfilled in `style.css`.
- **Orphaned "About the Site" heading**: an assigned-but-empty footer menu left the heading with no links under it. The column's content is now built first and the heading renders only when links exist.

## [1.5.0] - 2026-07-12

### Fixed
- **Homepage pagination duplicate (bug)**: the featured hero now consumes the first post of the main query on page 1 instead of running a secondary `WP_Query` — the last card on page 1 no longer repeats as the first card on page 2, and page counts always come from the query WordPress actually paginates.
- **Nested form in Theme Options (bug)**: the Reset Settings form sat inside the main settings form — invalid HTML that made browsers drop the inner form tag. Reset now lives outside the settings form and the button targets it via the HTML5 `form` attribute.
- **Dead code**: removed the unreachable `is_category()` / `is_archive()` / `is_search()` branches from `index.php` (dedicated templates have handled these since 1.2.0), including an unescaped `category_description()` echo.
- **Reading time on multibyte content**: `str_word_count()` replaced with Unicode-aware `preg_match_all('/\S+/u')` — CJK and other non-ASCII content no longer reports 0-minute reads.

### Security
- Every option registered with a `sanitize_callback`: URLs via `esc_url_raw()`, footer text via `sanitize_text_field()`, checkboxes coerced to `'1'`/`''`, maintenance mode whitelisted.
- Header/Body/Footer script fields now require the `unfiltered_html` capability (matters on multisite, where regular admins lack it); without it the previous value is kept.
- Settings import sanitises every value with the same per-key callbacks — a crafted JSON file can no longer bypass sanitisation; script fields are skipped for users without `unfiltered_html`.
- Admin-email Gravatar fallback removed from header and footer — the MD5 hash of the admin email no longer appears in public HTML. The fallback is now the site's initial in a neutral disc.
- Maintenance mode bypass tightened from `is_user_logged_in()` (subscribers saw the live site) to `current_user_can('edit_theme_options')`; a `Retry-After: 3600` header is sent with the 503.
- `$_POST` values in the category-icon save handler are now unslashed and sanitised before use.

### Changed
- **Icons are now inline SVGs** rendered server-side via `rodolfo_icon()` in `functions.php` (Lucide artwork, ISC licence). The unpkg CDN script is gone — WordPress.org disallows CDN-loaded scripts — and icons no longer flash in late or vanish without JS.
- **Google Fonts removed** (GDPR; remote font loading is also flagged by WordPress.org). The `font-sans` stack falls back to system-ui; self-host Inter woff2 files and add `@font-face` to `input.css` to restore it.
- `date('Y')` → `wp_date('Y')` in the footer start-year fallback; footer text template is now translatable.

### Added
- **Mobile navigation**: hamburger toggle in the header opens a panel with the primary menu and a search field — previously phones had no menu and no search at all.
- **Skip-to-content link** for keyboard users (visually hidden until focused), with a matching `id="content"` on every template's `<main>`.
- Block editor support: `align-wide` and `responsive-embeds`.
- i18n: previously hardcoded strings ("Site Info", "Latest Articles", search placeholder, footer fallback links, aria-labels, article counts via `_n()`) are now translatable.
- **Footer redesign**: brand block with wordmark (last word accented in blue) and site tagline, "About the Site" link column, accent divider with centred star ornament, © line with current year, dot-separated Privacy · Terms · Accessibility · RSS legal row, and circular-bordered social icons. Keeps the Rodolfo slate/blue palette.
- Theme Options → General: **Legal links** fields for the footer's Terms and Accessibility URLs, plus a **Sitemap URL override** (sanitised with `esc_url_raw`, included in export/import/reset). Links render only when set; Privacy comes from Settings → Privacy; the Sitemap link falls back to WordPress core's `/wp-sitemap.xml` automatically.

## [1.4.0] - 2026-04-15

### Added
- Theme Options page completely rebuilt with a tabbed interface (General, Social, Performance, Scripts, System Info), matching the layout pattern of professional WordPress plugins with left-label / right-control section rows.
- **Export Settings** — downloads all theme options as a dated JSON file.
- **Import Settings** — restores options from a JSON export file via file upload.
- **Reset Settings** — wipes all options back to defaults with a confirmation prompt, per-tab aware.
- **Performance tab** — three opt-in toggles: Disable Emojis (removes emoji scripts and DNS prefetch), Disable Embeds (removes oEmbed scripts and rewrites), Disable jQuery Migrate. Each implemented as a proper WordPress hook, not just a UI element.
- **Scripts tab** — three textareas for injecting arbitrary HTML/scripts: Header scripts (before `</head>` via `wp_head`), Body scripts (after `<body>` via `wp_body_open`), Footer scripts (before `</body>` via `wp_footer`).
- **Maintenance mode** — dropdown in the General tab: Disabled / Maintenance (503) / Coming Soon (200). Bypassed automatically for logged-in users.
- **System Info tab** — read-only table displaying theme name/version/author, WordPress version, site URL, debug status, multisite status, active plugin count, PHP version, MySQL version, server software, PHP memory limit, WP memory limit, and max upload size.
- Admin stylesheet scoped to the Theme Options page only (`appearance_page_rodolfo-theme-options` hook).

### Changed
- Social links fields moved to a dedicated Social tab with placeholder examples.
- Footer text override moved to the General tab alongside Maintenance mode.
- All four previous flat callback functions (`rodolfo_footer_text_cb` etc.) removed — fields are now rendered inline in the page function.

## [1.3.0] - 2026-04-15

### Added
- `template-parts/content/content-card.php` — single source of truth for the post card markup. All loop templates now call `get_template_part('template-parts/content/content-card')` rather than repeating the markup inline.
- `template-parts/content/content-single.php` — single post article header (featured image, meta, title, author block, Share/Bookmark buttons, content, wp_link_pages, tags). `single.php` is now 24 lines.
- `add_theme_support('custom-logo')` declared in `rodolfo_setup()`. Users can now set a site logo via Appearance → Customize → Site Identity.

### Changed
- `index.php`, `archive.php`, `category.php`, `tag.php`, `search.php` — card loop markup replaced with `get_template_part('template-parts/content/content-card')`.
- `single.php` — article content replaced with `get_template_part('template-parts/content/content-single')`.
- `header.php` — logo area now uses `has_custom_logo()` / `the_custom_logo()` with admin email Gravatar as a fallback. The MD5 hash of the admin email is no longer unconditionally embedded in every public page load.
- `footer.php` — footer avatar now uses the custom logo image when set, with admin email Gravatar as a fallback.

## [1.2.0] - 2026-04-15

### Added
- `archive.php` — dedicated template for date, author, and custom post type archives. Handles `is_author()`, `is_year()`, `is_month()`, `is_day()`, and generic archive fallback, each with a contextual header.
- `category.php` — dedicated category archive template, extracted from the `is_category()` branch in `index.php`. Reads the `category_icon` term meta field for the emoji display.
- `tag.php` — dedicated tag archive template with `#tag-name` heading, `tag_description()`, and article count.
- `assets/css/` directory — CSS assets now have a dedicated directory per WordPress and front-end best practices.
- `assets/css/input.css` — Tailwind CSS source file with `@tailwind` directives and pagination component styles.
- `assets/css/theme.min.css` — compiled and minified Tailwind CSS output (53 KB), generated from all theme PHP and JS files. Replaces the CDN JIT runtime.
- `tailwind.config.js` — build config matching the inline configuration previously inlined in `functions.php` (darkMode: `class`, Inter font extension, `@tailwindcss/typography` plugin).
- `load_theme_textdomain()` registered as the first call in `rodolfo_setup()` — all `__()` and `_e()` calls are now functional for translations.

### Changed
- Tailwind CSS is now compiled at build time via the CLI and served as a static stylesheet (`assets/css/theme.min.css`). The `cdn.tailwindcss.com` CDN script and its inline `tailwind.config` block have been removed from `functions.php`.
- Lucide Icons URL pinned from `@latest` to `@0.383.0/dist/umd/lucide.min.js` — prevents silent breakage from upstream icon renames or API changes.
- `lucide.createIcons()` moved from a raw `<script>` block after `wp_footer()` in `footer.php` into `wp_add_inline_script('lucide-icons', ...)` in `functions.php`. It now executes inside the WordPress hook system with `lucide-icons` declared as a dependency of `rodolfo-script`.
- Pagination CSS rules extracted from the inline `<style>` block in `index.php` into `assets/css/input.css`, compiled into `theme.min.css`. The mid-body `<style>` tag is removed.

## [1.1.0] - 2026-04-15

### Fixed
- **Pagination (critical)**: Replaced hardcoded `offset:1` in the homepage grid `WP_Query` with `post__not_in` to prevent posts from being silently skipped on pages 2 and beyond. `the_posts_pagination()` is now passed `total: $query->max_num_pages` so page counts are calculated from the correct query.
- **Comments (critical)**: Created `comments.php` — its absence caused a fatal include error on any post with comments open or with existing comments.
- **404 handling (critical)**: Created `404.php` — WordPress was silently falling back to `index.php`, rendering a full article grid on every not-found URL.
- **Search handling (critical)**: Created `search.php` — search results were rendering through the homepage template with no results-specific layout or count.
- **CSRF on category meta save (security)**: Added `wp_nonce_field()` to both category form callbacks and `wp_verify_nonce()` + `current_user_can()` checks to the save handler in `functions.php`.
- **XSS via search query (security)**: `get_search_query()` in the search heading was echoed without escaping; fixed with `esc_html()`.
- **XSS via search input value (security)**: Search input `value` attribute in `header.php` was missing `esc_attr()`.
- **Reverse tabnapping (security)**: Added `rel="noopener noreferrer"` to all three `target="_blank"` social links in `footer.php`.
- **Unescaped RSS URL (security)**: Replaced `bloginfo('rss2_url')` with `esc_url(get_bloginfo('rss2_url'))` in `header.php`.
- **Unescaped `$term->count` (security)**: Wrapped with `absint()` in `index.php`.
- **Tags nav link (404)**: Replaced hardcoded `home_url('/tags')` with a conditional `get_page_by_path('tags')` — the button now only renders when a page with slug `tags` actually exists.
- **Non-functional Share button**: Implemented Web Share API with `navigator.clipboard` fallback and visual confirmation in `main.js`.
- **Non-functional Bookmark button**: Implemented `localStorage` persistence per post ID with `aria-pressed` state and colour feedback in `main.js`.
- **Multi-page posts unnavigable**: Added `wp_link_pages()` after `the_content()` in both `single.php` and `page.php`.
- **Unescaped `get_the_date()` output**: Wrapped with `esc_html()` in `index.php` (×2), `single.php`, and `search.php`.
- **Unescaped `get_template_directory_uri()` output**: Wrapped with `esc_url()` in `header.php` and `index.php`.
- **Unescaped reading time output**: `rodolfo_get_reading_time()` return value now escaped with `esc_html()` at the function definition.

### Added
- `comments.php` — threaded comment list and styled comment form using a custom `rodolfo_comment()` callback registered in `functions.php`.
- `404.php` — clean not-found page with a back-home button and inline search form.
- `search.php` — dedicated search results template with result count, no-results state with retry form, and pagination.
- `rodolfo_comment()` callback in `functions.php` for fully custom comment rendering with avatar, threading, moderation notice, and reply link.
- `id`, `data-title`, `data-url`, and `data-post-id` attributes on Share and Bookmark buttons in `single.php` to pass post context to JavaScript without globals.
- `aria-pressed` attribute on the Bookmark button for accessibility state.
- Nonce fields (`wp_nonce_field()`) in both `rodolfo_category_add_meta_field()` and `rodolfo_category_edit_meta_field()` form callbacks.

## [1.0.2] - 2026-03-11

### Added
- A dedicated **Theme Options** page (`Appearance > Theme Options`) giving users control over settings not suited for the Customizer.
- Input fields in Theme Options for social media links (GitHub, Twitter, LinkedIn) which render automatically in the footer using Lucide icons.
- Input field to override the automatic dynamic footer text entirely.

### Changed
- Rebuilt the `footer.php` layout to match the final mockup design perfectly, splitting the navigation and copyright/social sections into two rows.
- Bumped version to 1.0.2.

## [1.0.1] - 2026-03-11

### Added
- Native support for assigning emojis to categories via a new meta field on the Edit Category screen.
- Category archive pages now prominently display the assigned emoji icon (with a fallback if none is set).

### Changed
- Bumped version to 1.0.1.
- Footer text is now completely dynamic: pulls the Site Name and automatically detects the year of the first published post.
- Significantly reduced the opacity of the static background art to make it even more subtle.
- Ensured background art overlay is consistently rendered across all theme templates (`header.php`, `index.php`, `single.php`, `page.php`).

## [1.0.0] - 2026-03-11

### Added
- Initial theme creation.
- Tailwind CSS v4 integration.
- Responsive layout with desktop, tablet, and mobile support.
- Dark mode toggle with `localStorage` preference saving.
- WordPress Customizer support for `.mp4` background videos.
- Reading time calculation function in `functions.php`.
- Dynamic fallback gravatar generation for the site admin in the header.