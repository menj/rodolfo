# SSOT — Rodolfo Theme (Single Source of Truth)

Canonical reference for every setting, hook, template, and convention in the theme.
**If this file and another doc disagree, this file wins — fix the other doc.**

**Accessibility baseline (2.9.0).** Meaningful text never lighter than slate-500 on light backgrounds (slate-400 reserved for dark mode / decorative); global `prefers-reduced-motion` override in style.css — which also hides the autoplay background video entirely (`.rodolfo-bg-video video`); both decorative background wrappers in `header.php` are `aria-hidden`; ⌘K palette restores focus on close; :focus-visible outlines on non-Tailwind interactive elements.

Current version: **3.10.0** (version is read from `style.css` via `wp_get_theme()->get('Version')`, exposed as the `RODOLFO_VERSION` constant; readme.txt `Stable tag` must match).

**Design reference.** The theme is named after and visually modelled on [Rodolfo Berrios' blog](https://rodolfoberrios.com/). The homage is **aesthetic only** — that site runs VuePress, not WordPress, so nothing about its architecture carries over. Concretely, the borrowed elements are the "Name blogging since YEAR" footer line, the deliberately minimal header nav, and the tag-driven structure. When these docs or the theme description reference the blog, use the canonical URL `https://rodolfoberrios.com/` (an earlier `rodolfo.berrios.com` in readme.md was a typo, fixed in 3.0.0).

---

## Feature modules (`inc/`)

`functions.php` requires these on load. Each is self-contained and hooks itself.

| Module | Responsibility | Key guard |
|---|---|---|
| `meta-tags.php` | description / OG / Twitter tags | `rodolfo_seo_plugin_active()` — stands down under Yoast/Rank Math/AIOSEO/SEOPress/TSF |
| `schema.php` | JSON-LD (Article, WebSite+Organization, BreadcrumbList) | same SEO-plugin guard |
| `social-card.php` | generated OG card at `?rodolfo_card=<id>`, per-category tint + logo lockup | GD required; else `rodolfo_social_card_url()` returns ''; the gradient's far endpoint blends toward the category colour (light 22% / dark 55%); custom logo bottom-right from the local attachment file (no network), degrading to nothing |
| `search-palette.php` | ⌘K palette markup + REST endpoint exposure | overlay in wp_footer; searches posts+pages AND categories in parallel (core search endpoint + its term type, wired in main.js); last five queries shown as "recent" rows on open, reader's `localStorage` only; progressive enhancement — the plain search form still works without JS |
| `favicon.php` | bundled site-icon links | `has_site_icon()` short-circuit |
| `category-color.php` | per-category accent (meta + palette) | picked hex validated `^#[0-9a-f]{6}$`, else palette by term ID |
| `toc.php` | auto TOC (h2/h3) on long-form posts + scroll-spy + mobile collapse | only `is_singular('post')` with ≥`rodolfo_toc_min_headings()` (3) headings; heading doubles as a disclosure button (collapses below md only — desktop always open); spy is IntersectionObserver-driven, `is-current` on the accent |
| `related-posts.php` | related posts: pinned → tag overlap → category | manual override via `_rodolfo_related_ids` meta (comma IDs, order kept, published-only); tag stage ranks by overlap COUNT scored in PHP over a bounded pool (SQL can't order by intersection size), recency tiebreak; renders nothing when none found |
| `fonts.php` | self-hosted Inter @font-face + preload | only if `assets/fonts/Inter-Variable.ttf` exists |
| `icons.php` | inline SVG icon library (`rodolfo_icon`) | Lucide, ISC; server-rendered, loads first |
| `nav.php` | Page-driven nav, `rodolfo_privacy_page()`, footer utility links | extracted from functions.php in 3.4.0 |
| `system-report.php` | **theme-scoped** diagnostics + JSON export for System Info | `rodolfo_system_report()` single source; sections Environment / Feature health / **Recommendations** (advice rows, ✓/⚠-prefixed: permalinks, debug output, minification, comment pagination, autoload weight, view-data state — `rodolfo_system_recommendations` filter) / Active settings / Utility pages. **Does not restate Site Health** — no server paths, plugin inventory, DB tables or unrelated constants; a row earns its place only if a Rodolfo feature depends on it or its value explains reported behaviour. `admin_post_rodolfo_system_report` download, `manage_options` + nonce; filterable; loads before options-page |
| `settings.php` | option registry + sanitizer map + social platform registry + `register_setting()` | **the persistence contract** — an option not listed in `rodolfo_option_sanitizers()` does not save |
| `options-actions.php` | admin asset enqueues (`assets/js/admin.js`, `assets/css/admin.css` — both files, never inline: strict-CSP-safe, cacheable), export/import/reset handlers, menu registration | all handlers nonce-checked + `manage_options`; import additionally verifies `is_uploaded_file()` before reading |
| `options-page.php` | Theme Options screen markup (tabs, panels, fields) | render only; reads the registry from `settings.php` |
| `maintenance.php` | maintenance / coming-soon holding page (`wp_die_handler`) | extracted from functions.php in 3.4.0 |
| `performance.php` | emoji/embed/jQuery-Migrate toggles + header/body/footer script injection | extracted from functions.php in 3.4.0 |
| `editor.php` | block editor parity | `editor-styles` + `assets/css/editor.css` shim; palette derived from `rodolfo_category_palette()`; font sizes mirror front-end scale; gradients disabled |
| `reading-time.php` | reading estimate, `rodolfo_series` taxonomy, feed content | code blocks weighted at half rate; series ordered by `menu_order` then date, cached per term, flushed on `save_post` |
| `views.php` | post view counter + popular/trending helpers | off by default; `_rodolfo_views` all-time + `_rodolfo_views_daily` 7-day rolling array (date-keyed, pruned on write — **no cron**); bots + editors + throttled repeats excluded; `rodolfo_popular_posts( $count, $scope, $exclude, $window )` — `$window` 1–7 ranks by views *received* in the window via `rodolfo_trending_posts()`, distinct from `$scope` which restricts publish date; both cached per key, flushed on save |
| `author-social.php` | per-user contact fields (Twitter/GitHub/LinkedIn/Mastodon/Bluesky/website) | via native `user_contactmethods`; rendered on the author card |
| `privacy-embeds.php` | consent-gated iframe embeds (YouTube/Vimeo/SoundCloud/Spotify) | **off by default**; `embed_oembed_html` facade — generic placeholder, deliberately **no provider thumbnail** (that would itself be a third-party request); "always allow" per provider in reader's `localStorage` only; script-based embeds (X) pass through ungated by design; `rodolfo_privacy_embed_providers` filter |
| `comment-guard.php` | comment-form bot protection | **off by default**; honeypot (off-screen, `aria-hidden`, tabindex −1) + signed minimum-time trap (`wp_hash` + `hash_equals`, page-cache-safe: minimum age only) on `preprocess_comment`; never checks logged-in users or ping/trackbacks; Turnstile only when BOTH keys set — script injected on first comment-field focus, never on page load; theme stores only the two keys |
| `accent.php` | Customizer accent colour (Colors → "Accent colour") | the accent system is variable-driven end to end: compiled CSS ships stock blues, style.css re-points blue utilities to `--rodolfo-accent(-700/-500/-400)`, and this module overrides those four vars — **prints nothing at the default cobalt**; shades derived from the one picked hue (never a second stored value); consumed by maintenance page, login, progress fill, embed play button, and the options screen (a one-line dynamic var override — the sole permitted inline admin style) |
| `post-display.php` | per-post hide-title / hide-featured / full-width flags | side meta box, own nonce, `edit_post` capability; a hidden title stays in the accessibility tree as `screen-reader-text`; hidden featured images still feed share cards; templates guard the helper with `function_exists` |
| `page-states.php` | coming-soon Pages + site-updated stamp | post meta `_rodolfo_coming_soon` / `_rodolfo_coming_soon_note`; noindex on coming-soon pages; `rodolfo_site_updated` transient (publish date of newest post — not modified, so old-post edits do not reset it) flushed on save/delete/`transition_post_status` |
| `sidebar.php` | optional widget area | **off by default**; requires enabled + scope match + non-empty, else renders nothing |
| `docs.php` | renders `docs/*.md` in Theme Options → Documentation | parser tiers: existing `Parsedown` → bundled `vendor/parsedown/` → **minimal** fallback (not a second full parser; tables/task lists degrade); **never redeclares the global `Parsedown` class** (`class_exists()` guard prevents a fatal); safe mode + markup escaped; all output through `rodolfo_docs_kses()`; transient cached on `filemtime()` + engine; paths contained to the theme dir |
| `setup.php` | idempotent page scaffolding + starter content | never overwrites, never deletes; creates no menus; alias-aware (won't duplicate `/privacy` vs `/privacy-policy`, `terms` vs `terms-of-use`) |
| `click-to-tweet.php` | `[bctt]` shortcode + native `rodolfo/click-to-tweet` block (X intent + optional UTM) | shortcode registers only if the BCTT plugin isn't providing `[bctt]`; the **block always registers** and renders through `do_shortcode`, so whichever `[bctt]` implementation is active serves it — block and shortcode cannot drift; editor script `assets/js/bctt-block.js` is buildless vanilla `wp.blocks`, dynamic (`save: null`) |
| `unlist.php` | hide posts from listings (Publish-box toggle + list-table column, quick-edit toggle, filter) | single URL stays live + `noindex`; excluded via `pre_get_posts`; quick-edit checkbox is prefilled by `assets/js/unlist-quick-edit.js` from the column's `data-unlisted` attribute — without the prefill, quick-edit saves would silently re-list; admin filter forces `post__in` `array(0)` when no posts are unlisted (an empty `post__in` would list everything) |
| `llms-txt.php` | virtual /llms.txt summary (GEO) + AI-crawler robots.txt Allow rules | physical files win; serves under **plain permalinks** too (early `REQUEST_URI` check — rewrite rules alone would 404 the literal path); **unlisted posts excluded** from the body; `rodolfo_llms_txt*` / `rodolfo_ai_crawlers_welcome` filters |
| `breadcrumbs.php` | visible Home › Category › Title trail on posts | defers to Rank Math/Yoast renderers; `rodolfo_breadcrumbs_enabled` |
| `aeo.php` | AEO blocks: [answer], [proof], [faqs]/[faq] + FAQPage JSON-LD, [compare] | JSON-LD only where [faq] rendered; stays on under SEO plugins (their blocks can't see our shortcodes) — `rodolfo_faq_jsonld` filter suppresses |
| `login.php` | branded wp-login (Custom Logo/Site Icon + accent) | no output if no logo/icon set |
| `reading-progress.php` | scroll reading-progress bar + sticky title strip | self-contained JS/CSS; options: enabled/height/position + `rodolfo_sticky_title` (off by default); the strip is `IntersectionObserver`-driven — deliberately no second scroll handler — sits below the site header (height measured at runtime), and appears without animation under reduced motion |
| `donate.php` | `[donate]` button + optional auto-placement; PayPal-by-email or URL override | builds PayPal hosted `/donate/` URL from `rodolfo_paypal_email`; `rodolfo_donate_auto` appends button via `the_content` on singular views; NO card/token/webhook handling |

**SEO ownership.** `meta-tags.php` + `schema.php` own head SEO output only while no SEO plugin is active. If one is installed, both self-disable; the JSON-LD is generic Article/BreadcrumbList that any plugin also emits, so there is nothing plugin-specific to preserve.

**Build.** Tailwind **v4**, CSS-first — no `tailwind.config.js`. Source `assets/css/input.css` (`@import "tailwindcss"`, `@source` globs, `@theme` tokens, `@plugin "@tailwindcss/typography"`); `npm run build` (from `package.json`) compiles `theme.min.css`. The `@theme` block remaps the `blue-*` ramp to the brand cobalt (`#3d54d6`) and defines `--font-sans` (Inter) + `--font-display` (serif). `style.css` mirrors these as runtime overrides so brand + serif render before any recompile; never let the two diverge.

**Asset discipline.** `RODOLFO_ASSET_SUFFIX` = `''` under `SCRIPT_DEBUG`, else `.min`. Every source asset has a `.min` twin (`main.js`, `admin.js`, `resume.js`, and `assets/css/admin.css` → their `.min` counterparts). Editing a source without regenerating its `.min` twin changes nothing on a live site — the single most likely way to waste time here. `tests/test-theme.php` asserts the twin exists.

**Category colour.** Author-picked `category_color` term meta (saved via the shared `rodolfo_save_category_icon` nonce, validated with `sanitize_hex_color`) overrides an auto-assignment from the 8-colour muted palette (`rodolfo_category_palette`, filterable). `rodolfo_category_pill_style( $term_id )` returns `--rc` + tinted background + solid-hue text; consumed by the card, single header, index hero, and category archive. Never introduce a second stored value for a dark-mode variant — the mid-tone hue + alpha tint is designed to work on both grounds.

**Setup invariants.** `inc/setup.php` never overwrites and never deletes; re-runnable from Theme Options. `rodolfo_setup_status()` reports present/missing/empty without side effects; `rodolfo_setup_run()` creates what is absent **and backfills starter content into pages that exist but are empty**, returning a list of what it did. Never-overwrite applies to *non-empty* content only — an empty page has nothing to protect, which is what makes the backfill safe. Existing pages are matched via `rodolfo_setup_find_page()`, which honours alternate slugs, so re-running never produces duplicates. New pages are seeded with block-markup starter content; Privacy/Terms bodies are explicitly-labelled drafts, not legal advice.

**Privacy page resolution.** `rodolfo_privacy_page()` is the single source of truth: Settings → Privacy first, then a published page at `/privacy-policy`, then `/privacy`. Nav exclusion, the footer utility link, and setup all defer to it so they cannot disagree.

**Social platform registry.** `rodolfo_social_platforms()` entries are `array( label, placeholder, type )` where `type` defaults to `url`. The type drives the admin `<input type>` and, via `rodolfo_social_href()`, how a stored value becomes an href — so a non-URL platform (currently only `email`) needs no branching in `footer.php` or the options page. Adding a platform means: registry entry, sanitizer map entry, and a glyph in `rodolfo_icon()` keyed by the same slug.

**Maintenance page.** Goes through `wp_die()` (canonical path — core status handling and plugin `wp_die` filters still apply), themed via the `wp_die_handler` filter. `wp_die()`'s own stylesheet cannot be overridden from the message argument because it prints `<style>` after the body; replacing the handler is the supported workaround. `rodolfo_maintenance_die_handler()` renders the whole document and `exit`s; the filter is added only inside `rodolfo_maintenance_mode()`, so it never affects other `wp_die()` calls. Because no stylesheet is enqueued on that request, all CSS is inline; it is pinned light to match the site default (no `prefers-color-scheme` block), honours `prefers-reduced-motion`, uses `custom_logo` when set, and sends `noindex` plus `nocache_headers()` so a cached holding page cannot outlive the outage. Copy comes from `rodolfo_maintenance_copy()`: built-in defaults, overridden by the two options above, then filterable via `rodolfo_maintenance_copy`. Bypass remains `edit_theme_options`, not merely logged-in.

**Options module split (3.8.0).** `settings.php` → `options-actions.php` → `options-page.php`, loaded in that order because the latter two read the registry. Adding an option means editing `settings.php` (sanitizer entry) and `options-page.php` (the field); forgetting the former means it silently never saves.

**functions.php scope (3.4.0).** Deliberately lean (guarded by a test, budget 420 since the feature-toggle loader landed): constants, theme supports, enqueues, module loader, and small template helpers only. **Feature toggles**: `RODOLFO_OPTIONAL_MODULES` (slugs; labels via `rodolfo_optional_modules()` in settings.php) — the loader skips a module whose `rodolfo_disable_<slug>` option is truthy, so disabled features are never even required. Only whitelisted modules are skippable; every optional helper a template calls is wrapped in `function_exists`, kept true by the undefined-call test. The Features tab stores **inverted** (a hidden input submits the disable flag, the checkbox — value `''` — overrides it), so an install that never saved the form has everything on. New behaviour belongs in an `inc/` module, not in functions.php. The five structural modules (`icons`, `nav`, `options-page`, `maintenance`, `performance`) load before feature modules because their helpers are called at render time.

**Appearance default.** Light everywhere unless the visitor opts into dark: front end (head script), maintenance page (no `prefers-color-scheme` block), social cards (`rodolfo_card_variant` defaults to `light`), and the editor canvas (pinned light). Adding an OS-preference fallback to any one of these would reintroduce the inconsistency 3.3.0 removed.

**Docs parser fallback — keep it.** Tier 3 (`rodolfo_docs_markdown_to_html()`) never executes in a correctly packaged release, because `vendor/parsedown/` always ships. It is retained deliberately, and should not be deleted as dead code:

- **Parsedown 1.8.0 is unmaintained** (2019; CI stops at PHP 8.1). If a future PHP release breaks it at instantiation, `rodolfo_docs_parser()` catches the `Error` and falls through — the Documentation tab degrades instead of fatalling the admin screen. This is the mitigation that made bundling an unmaintained library acceptable in the first place.
- It also covers `vendor/` being stripped downstream by a build pipeline or repackager.
- Cost is nil: zero runtime overhead when Parsedown loads, and a test assertion pins it at "minimal" so it cannot regrow into a second full parser.

Known limit: the guard wraps *construction*, not every `->text()` call, so a fatal thrown mid-parse would still surface. Good insurance, not total insurance. Removing tier 3 would require replacing it with a graceful notice — deleting it outright would fatal `rodolfo_docs_render()` whenever Parsedown is absent.

**SEO plugin coexistence (invariant).** Detection is `rodolfo_seo_plugin_active()` in `inc/meta-tags.php` — Yoast / Rank Math / AIOSEO / SEOPress / The SEO Framework, plus a filter to force either way. Rules:

| Module | Under an SEO plugin |
|---|---|
| `meta-tags.php` | suppressed entirely |
| `schema.php` | suppressed entirely — two competing graphs is worse than one |
| `breadcrumbs.php` | calls the plugin's own trail |
| `aeo.php` | **keeps emitting** FAQPage — Q/As come from `[faqs]` shortcodes no plugin knows about; opt out via `rodolfo_faq_jsonld` |
| `page-states.php` | filters `wp_robots`, never echoes a robots tag |
| `favicon.php` | unaffected — site icon, not an SEO signal |

**Edit-verification invariant (3.8.2).** When an automated edit inserts a function, verify the *definition* landed (`grep "^function name"`), not merely that the name appears — a call site alone matching is exactly how 3.7.1 shipped a conditional fatal. The harness now enforces the whole-theme call graph: every `rodolfo_*` call must have a definition.

**Never echo a raw `<meta name="robots">` from a `wp_head` hook** — filter `wp_robots` so exactly one tag is emitted. (The maintenance page is the sole exception: it replaces the whole document, so no plugin is running.)

**Schema coverage.** One `@graph` per page, assembled in `rodolfo_schema_output()`: front page → `WebSite`+`Organization`; post → `Article` (+`CreativeWorkSeries` when in a series); Page → `WebPage`/`ContactPage`/`AboutPage`/`FAQPage` by slug; category/tag/tax/date → `CollectionPage`+`ItemList`; author → `ProfilePage`+`Person`; search → `SearchResultsPage`; singular → `BreadcrumbList`. **FAQ Q/As belong to `inc/aeo.php`** — `schema.php` claims the FAQPage type but never emits `Question` nodes, so the two cannot disagree. Whole module self-disables under an SEO plugin.

**Site freshness dates.** Three related but distinct values, deliberately not shared: the footer stamp (`rodolfo_site_updated_time()`, follows `rodolfo_site_updated_mode`), schema `dateModified` (`rodolfo_site_modified_time()`, **always** true modified time — it means "content last changed" and must not follow a display preference), and schema `datePublished` (oldest post). Homepage schema emits its dates whether or not the visible stamp is enabled.

**Editor parity.** The front end styles post content with Tailwind Typography (`prose-lg prose-slate dark:prose-invert`); the editor canvas is a separate document that never receives those classes or the `.dark` toggle. `assets/css/editor.css` restates the scale against `.editor-styles-wrapper` / `.wp-block`. **Keep it in step with `content-single.php`** — if the front-end prose modifier changes, the shim must change too. The editor is pinned light rather than shipping a broken half-dark state.

**Sidebar policy.** The single centred column is the intended reading experience. The sidebar exists for sites that need widgets and is off by default; `rodolfo_has_sidebar()` requires enabled + scope match + non-empty, so neither enabling it alone nor adding widgets alone changes the layout.

**Nav precedence.** An assigned WordPress menu (`primary`/`secondary`) always overrides the per-page location settings — the Navigation tab surfaces a warning callout when this is in effect.

## Documentation ownership

All maintainer/developer docs live in `docs/`; only `readme.txt` stays in the theme root (WordPress.org requires it there). All doc filenames are lowercase.

`upgrading.md` documents **upgrade paths between versions only** — plans and roadmaps belong in `changelog.md` or an issue tracker (the completed 1.0.2 roadmap was archived to a summary in 3.5.2). The four `docs/*.md` files are **also rendered in the dashboard** by `inc/docs.php` (Theme Options → Documentation). They are the single source of truth for that tab — there is no HTML copy — so a malformed table or heading in Markdown shows up in the admin UI. The tab's sub-nav order and labels come from `rodolfo_docs_files()`; adding a file to `docs/` does not surface it until it is registered there (or added via the `rodolfo_docs_files` filter).

| File | Audience | Owns |
|---|---|---|
| `docs/ssot.md` | maintainers | canonical inventory (this file) |
| `docs/readme.md` | developers | architecture, dev setup, feature internals |
| `readme.txt` | WP.org / end users | feature blurb, install steps, user-facing changelog |
| `docs/changelog.md` | maintainers | full technical changelog |
| `docs/upgrading.md` | maintainers + site owners | **roadmap** for extending/enhancing the theme (extension points, planned work), plus per-version migration history |

Every functional change updates: `changelog.md` (always), `readme.txt` changelog (user-visible), `readme.md` (if architecture/API changed), `upgrading.md` (if action needed on upgrade), and this file (if inventory changed).

---

## Options (wp_options)

All keys live in the map returned by **`rodolfo_option_sanitizers()`** in `functions.php`.
That map is THE source of truth — settings registration (`rodolfo_register_settings()`),
export, import, and reset all derive their key list from it. To add an option: add it to
the map, add the field to the Theme Options page, done — never hardcode key lists elsewhere.

| Option | Sanitizer | Tab | Rendered in |
|---|---|---|---|
| `rodolfo_footer_text` | `sanitize_text_field` | General | footer © line |
| `rodolfo_nav_locations` | `rodolfo_sanitize_nav_locations` (id⇒header/footer/hidden) | Navigation | per-page location |
| `rodolfo_nav_order` | `rodolfo_sanitize_int_map` (id⇒int) | Navigation | per-page order within its location |
| `rodolfo_footer_util` | `rodolfo_sanitize_footer_util` (key⇒{show,label,order}) | Navigation | built-in footer links (FAQ/Terms of Use/Privacy Policy/Sitemap/RSS) — show, rename, order |
| `rodolfo_link_sitemap` | `esc_url_raw` | General | footer legal row (falls back to `get_sitemap_url('index')`) |
| `rodolfo_maintenance_mode` | whitelist: `'' \| maintenance \| coming_soon` | General | `template_redirect` 503/200 holding page |
| `rodolfo_maintenance_title` | `sanitize_text_field` | General | holding-page headline (blank = built-in copy) |
| `rodolfo_maintenance_body` | `sanitize_textarea_field` | General | holding-page message (blank = built-in copy) |
| `rodolfo_reading_time_enabled` | checkbox (**default on**) | Content | reading estimate in post meta; single engine in `inc/reading-time.php`, `rodolfo_get_reading_time()` is a back-compat wrapper |
| `rodolfo_series_enabled` | checkbox | Content | series nav box on posts |
| `rodolfo_feed_full_content` | checkbox | Content | full posts in RSS (overrides Settings → Reading) |
| `rodolfo_card_variant` | whitelist: `dark \| light` (default **light**) | Content | social share card gradient |
| `rodolfo_sidebar_enabled` | checkbox | Content | master switch for the widget area |
| `rodolfo_keynav_enabled` | checkbox | Content | numbers header nav 1–9 (`data-keynav`, first nav only) |
| `rodolfo_site_updated_enabled` | checkbox | Content | footer date stamp beside the copyright line |
| `rodolfo_views_enabled` | checkbox | Content | count post views (self-hosted) |
| `rodolfo_views_show_meta` | checkbox | Content | show view count in post meta line |
| `rodolfo_resume_enabled` | checkbox | Content | offer "Resume where you left off" pill on return visits |
| `rodolfo_excerpt_length_card` | `absint` (default 24) | Content | excerpt length for card contexts |
| `rodolfo_excerpt_length_search` | `absint` (default 40) | Content | excerpt length for search results |
| `rodolfo_site_updated_mode` | whitelist: `published \| modified` (default `published`) | Content | which date the stamp reports; label follows the mode |
| `rodolfo_sidebar_scope` | `rodolfo_sanitize_sidebar_scope` (`archives \| posts \| everywhere`) | Content | where the sidebar shows |
| `rodolfo_progress_enabled` / `_height` / `_position` | checkbox / `rodolfo_sanitize_progress_height` (2–20px, <2→4) / whitelist `top\|bottom` | Content | reading-progress bar (`inc/reading-progress.php`) |
| `rodolfo_donate_auto` | whitelist: `'' \| posts \| posts_pages` | Content | auto-appends `[donate]` button to content end (`the_content` @ 20) |
| `rodolfo_paypal_email` / `_donate_url` / `_donate_currency` / `_donate_label` | `sanitize_email` / `esc_url_raw` / `rodolfo_sanitize_currency` / `sanitize_text_field` | Content | Donate button destination + label |
| `rodolfo_social_github` | `esc_url_raw` | Social | footer social circle (hidden when blank) |
| `rodolfo_social_email` | `rodolfo_sanitize_social_email` (strips a pasted `mailto:`, then `sanitize_email`) | Social | footer social circle; stored as a **bare address**, rendered as `mailto:` by `rodolfo_social_href()`; no `target="_blank"` |
| `rodolfo_social_twitter` | `esc_url_raw` | Social | footer social circle (hidden when blank) |
| `rodolfo_social_linkedin` | `esc_url_raw` | Social | footer social circle (hidden when blank) |
| `rodolfo_disable_emojis` | checkbox → `'1' \| ''` | Performance | `init` |
| `rodolfo_disable_embeds` | checkbox → `'1' \| ''` | Performance | `init` |
| `rodolfo_disable_jquery_migrate` | checkbox → `'1' \| ''` | Performance | `wp_default_scripts` |
| `rodolfo_header_scripts` | raw, gated on `unfiltered_html` | Scripts | `wp_head` @ 99 |
| `rodolfo_body_scripts` | raw, gated on `unfiltered_html` | Scripts | `wp_body_open` @ 99 |
| `rodolfo_footer_scripts` | raw, gated on `unfiltered_html` | Scripts | `wp_footer` @ 99 |

Security invariants:
- Scripts options: users without `unfiltered_html` cannot change them (sanitize callback returns the previous value); import skips them.
- Import runs every value through the same sanitizer map.
- Maintenance bypass requires `current_user_can('edit_theme_options')` — NOT `is_user_logged_in()`. 503 responses send `Retry-After: 3600`.
- Admin email / Gravatar must never appear in public HTML — logo fallback is the site's initial in a disc.

## Other stored data

| Data | Storage | Registered / written by |
|---|---|---|
| `category_icon` (emoji) | term meta on `category` | form: `category_add_form_fields`, `category_edit_form_fields`; save: `edited_category`, `create_category` (nonce `rodolfo_save_category_icon` + `manage_categories`) |
| `rodolfo_background_video` | theme mod (Customizer) | `customize_register` → section `rodolfo_background_section`; fallback `assets/theme-bg.jpg` |
| `custom_logo` | core theme mod | Customizer → Site Identity |
| `rodolfo_bookmarks` | visitor `localStorage` | `main.js` bookmark button |
| `theme` (`dark`/`light`) | visitor `localStorage` | inline `<head>` script + `main.js` toggle. **Light is the default**: the head script sets dark *only* when `localStorage.theme === 'dark'`; OS `prefers-color-scheme` is deliberately not consulted, so dark mode is strictly opt-in |
| `rodolfo_docs_{hash}` | transient (1 day) | `rodolfo_docs_render()`; hash covers doc slug + `filemtime()` + theme version + engine, so an edited `.md`, a version bump, or an engine change all invalidate it automatically — no manual flush |

## Theme supports, menus, admin pages

- Supports: `automatic-feed-links`, `title-tag`, `post-thumbnails`, `html5`, `custom-logo` (80×80 flex), `align-wide`, `responsive-embeds`.
- Menu locations (two tiers): `primary` (header desktop nav + mobile panel via `rodolfo_primary_nav_links()`); `secondary` (below-copyright footer row via `rodolfo_footer_nav_items()`). Both are Page-driven: each top-level Page has a **location** (header/footer/hidden) and an **order**, set in Theme Options → Navigation (`rodolfo_nav_location()` / `rodolfo_nav_order_val()`). The footer row merges footer-assigned Pages with the enabled built-in links (Privacy Policy / Sitemap / RSS, each with show+order via `rodolfo_footer_util_config()`), all sorted by order. **Bottom-bar wrap contract**: the ©/legal/social row stays stacked-and-centred below `lg`; every `·` separator shares a `whitespace-nowrap` unit with the link that follows it; the freshness stamp wraps whole; social circles wrap into a centred width-capped grid (pinned by tests — the three-groups-squeezed regression). The designated Privacy Policy page is excluded from the Pages list so it appears once, via the built-in link. The footer "Explore Topics" column is separate and content-driven (top-6 categories). Setup creates no menus.
- Tags page: built-in virtual `/tags/` route (`inc/tags.php` + `tags-page.php`) listing every non-empty tag as a linked pill cloud; no page/menu setup. `rodolfo_tags_url()` handles pretty/plain permalinks; reached via the header Tags icon only (no nav-menu link).
- Admin page: Appearance → Theme Options (`rodolfo-theme-options`), tabs General / Navigation / Content / Social Links / Performance / Custom Scripts / System Info / Documentation (ordered by setup flow). General carries footer text, sitemap override, the **Site setup** block (present/missing/empty table + “Create missing pages & fill empty ones”, posting to `admin_post_rodolfo_run_setup`; creates absent pages and backfills starter content into empty ones, never touching non-empty content). Settings group `rodolfo_options_group`. Tabs are **progressively enhanced**: anchors carry real `?tab=` URLs (server renders the right panel from `$_GET['tab']`, clamped to known tabs), upgraded to client-side switching by the enqueued `assets/js/admin.js`. **Never put admin JS in an inline `<script>`** — a CSP without `unsafe-inline` (common via security plugins) silently kills it, which is exactly what 3.8.1 fixed. Styled as a modern-minimalist card UI: gradient hero header showing the live theme version (from `wp_get_theme()`), pill tabs, rounded (16px) panel cards, gradient primary button — all CSS inline via `rodolfo_admin_enqueue()`, loaded only on this screen. Reset form is separate from the settings form (nested forms are invalid HTML) — button targets it via the HTML5 `form` attribute. The **System Info** tab renders `rodolfo_system_report()` and offers a nonce-guarded JSON download (`admin_post_rodolfo_system_report`); the export mirrors the on-screen data exactly. It links out to Tools → Site Health rather than duplicating general environment checks. The **Documentation** tab is read-only: the Save/Reset footer is hidden on it, server-side on load and by the tab-switcher JS when switched to client-side.
- `admin_post_*` handlers: `rodolfo_export_settings`, `rodolfo_import_settings`, `rodolfo_reset_settings` (all nonce-checked, `manage_options`).

## Templates

| Template | Handles | Notes |
|---|---|---|
| `index.php` | home | page 1: **magazine hero** — featured + up to 2 secondary via `the_post()`, grid continues from the 4th; never a secondary WP_Query. No archive/search branches (dead since 1.2.0). |
| `single.php` → `template-parts/content/content-single.php` | posts | share/bookmark buttons |
| `page.php` | pages | |
| `archive.php` | date/author/CPT | |
| `category.php` | category | emoji icon header; description escaped w/ `wp_strip_all_tags` |
| `tag.php` | tag | same escaping |
| `search.php`, `404.php`, `comments.php` | | |
| all loops | | card via `template-parts/content/content-card.php` |

Every `<main>` has `id="content"` (skip-link target from `header.php`).

## Frontend conventions

- **Custom logo**: never render via `the_custom_logo()` inside a link — it outputs its own home anchor (nested `<a>`) and the full-size image. Both header and footer output a constrained `<img>` from `wp_get_attachment_image_src( $id, 'thumbnail' )`.
- **Pagination**: `the_posts_pagination()` with a custom `class` arg REPLACES the default `pagination` class — CSS must target `.navigation` / `.page-numbers` (style.css), never `.pagination`.
- **Icons**: inline SVG via `rodolfo_icon( $name, $class )` in `functions.php` (Lucide artwork, ISC). Never `<i data-lucide>`, never a CDN script. Add new icons to the `$icons` array.
- **Fonts**: system stack only. No remote font requests (GDPR / WP.org). To restore Inter: self-host woff2 in `assets/fonts/`, `@font-face` in `input.css`, recompile.
- **CSS**: Tailwind compiled to `assets/css/theme.min.css` (see readme.md Development setup). `style.css` carries theme header + plain-CSS backfills (skip link, mobile nav, footer utilities, pagination, `.screen-reader-text`) for classes missing from the stale compiled sheet — remove backfills after recompiling (pagination + screen-reader-text styles stay; core markup doesn't use Tailwind utilities).
- **JS**: `assets/js/main.js` only (dark mode, mobile menu, share, bookmark). No external scripts. Works-without-JS is the baseline; JS enhances.
- **Escaping**: every output escaped (`esc_html`, `esc_attr`, `esc_url`); `$_POST` unslashed + sanitised before use; translatable strings in text domain `rodolfo` with translator comments; counts via `_n()` + `number_format_i18n()`; dates via `wp_date()` not `date()`. **JS strings** are localized through the `rodolfoL10n` object (`wp_localize_script` on `rodolfo-script`) — never hardcode user-facing English in `main.js`; the literals after `||` there are last-resort fallbacks only.
- **Reading time**: `rodolfo_get_reading_time()` — Unicode `preg_match_all('/\S+/u')`, 200 WPM, min 1.

## Footer composition (top → bottom)

1. Brand block: custom logo or initial-disc; wordmark with LAST word in blue accent; tagline.
2. "Explore Topics": content-driven only — top-6 categories (never Pages; those live in the primary/secondary nav). Built BEFORE the heading, which renders only when categories exist.
3. Divider: hairlines + centred blue star SVG ornament.
4. Bottom bar: © `wp_date('Y')` + footer text (auto: "Site Name blogging since {first post year}") · legal row (Privacy from Settings → Privacy, Terms, Accessibility, Sitemap, RSS — each hidden when unset, dot separators generated between rendered links only) · social circles.

## Release checklist

1. Bump `Version` in `style.css` + `Stable tag` in `readme.txt` (must match), `Current version` in this file, and `version` in `package.json` — all four. (`package.json` silently drifted from 2.10.0 to 2.14.0 because it was not on this list.)
2. Update the docs — **all five, by name**, since the last two drift most:
   - `docs/changelog.md` — the release entry
   - `readme.txt` — plain-language changelog entry (check parity with the above **across the full history, not just recent entries** — a 2.2.1–2.3.1 gap survived twenty releases because checks only compared recent ranges)
   - `docs/ssot.md` — option table, module table, invariants
   - `docs/readme.md` — feature descriptions, tab list, `inc/` file tree
   - `docs/upgrading.md` — a migration note if behaviour or defaults changed
   Naming them individually is deliberate: "update all docs" was the instruction for several releases and `readme.md`/`upgrading.md` still went stale three times.
3. Recompile Tailwind if templates changed; remove `style.css` backfills if recompiled.
4. `screenshot.png` stays 1200×900.
5. Zip folder name `rodolfo/` (no version suffix).
6. Ship `vendor/parsedown/` (both `Parsedown.php` and `LICENSE.txt`). Stripping it does not break the Documentation tab — `inc/docs.php` falls back — but tables and task lists degrade to plain lines. Keep the vendored file byte-identical to upstream so it stays diffable; the `class_exists()` guard lives in `rodolfo_docs_parser()`, not in their file.
7. Run `php tests/test-theme.php` (no WordPress required) and `php -l` over changed files.
