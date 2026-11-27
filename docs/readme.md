# Rodolfo Theme

Rodolfo is a modern, developer-friendly WordPress theme designed for high performance, rapid iteration, and clean typography. It utilises modern utility-first CSS and native browser APIs to deliver a premium user experience without bloated dependencies.

**Version:** 3.10.1
**Compatibility baseline:** WordPress 6.4 and PHP 7.4. PHP 7.4 is legacy compatibility only; use a currently supported PHP version in production.
**Author:** [MENJ](https://github.com/menj)
**Inspired by:** [Rodolfo Berrios' personal blog](https://rodolfoberrios.com/) — an aesthetic reference, not a technical one (that site runs VuePress, not WordPress). The homage is visual: the "blogging since YEAR" footer line, the minimal nav, and the tag-driven structure.

## Tech stack & architecture

- **Core CSS layer** (`rodolfo-theme`): the theme's compiled utility/design-token stylesheet. Source `assets/css/input.css` (`@import` + an `@theme` token block); compiled to `assets/css/theme.min.css` via CLI. No config file — CSS-first. Built on Tailwind CSS v4 under the hood (see Development setup for the literal build commands). See below.
- **UI layer** (`rodolfo-ui`, `assets/lib/ui.*`): the bundled grid/components/utilities + JS (modals, dropdowns, collapse, carousel, tabs, tooltips; Popper included). Vendored locally (MIT; Bootstrap 5.3.8 under the hood). `ui.min.css` enqueues FIRST and is a dependency of the core sheet, so the core Preflight reset wins reset collisions; `ui.bundle.min.js` loads in the footer. ⚠️ The two layers share overlapping resets and utility class names — they are not namespaced; scope UI-layer classes to markup you build against it.
- **Icons**: [Lucide](https://lucide.dev/) artwork (ISC licence) inlined as SVG server-side via `rodolfo_icon()` in `functions.php` — no CDN request, no icon flash, works without JS.
- **Typography**: **Inter is bundled** (`assets/fonts/`, OFL) for body text; a **display serif** stack (`--font-display`, applied via `.font-display`) sets hero and post titles for an editorial voice. `inc/fonts.php` emits Inter's `@font-face` + preload with no remote request.
- **Brand accent**: an owned cobalt-indigo (`#3d54d6`), NOT Tailwind's default blue. `input.css` remaps the `blue-*` colour ramp to the brand so existing utilities recolour on recompile; `style.css` mirrors the same overrides at runtime so it shows without one. Per-category accents (`inc/category-color.php`) layer on top.
- **Asset suffix**: `RODOLFO_ASSET_SUFFIX` serves un-minified sources under `SCRIPT_DEBUG`, `.min` twins otherwise. Every source asset ships with a `.min` counterpart (`main.js` → `main.min.js`); regenerate the twin after editing a source or nothing changes live.
- **Feature modules**: `functions.php` requires self-contained modules from `inc/` (SEO, social card, favicon, category colour, TOC, related posts, search palette, tags, click-to-tweet, unlist, AEO blocks, llms.txt, breadcrumbs, login, reading-progress, donate, fonts, setup, views, privacy-embeds, comment-guard) — see §7.
- **Dark mode**: Tailwind `class` strategy, **opt-in**. Light is the default everywhere; the inline `<head>` script adds `.dark` only when `localStorage.theme === 'dark'`, so the OS `prefers-color-scheme` setting is deliberately not consulted (see 3.3.0). The script runs before paint to prevent FOUC.

## Key developer features

### 1. Custom term meta — category emojis

The theme extends the category taxonomy with a custom `category_icon` term meta field, registered entirely in `functions.php`.

- Form hooks: `category_add_form_fields`, `category_edit_form_fields`
- Both form callbacks include a `wp_nonce_field()` for CSRF protection.
- Save hooks: `edited_category`, `create_category`
- Save handler verifies nonce and `current_user_can('manage_categories')` before writing.
- Data sanitised with `sanitize_text_field()` before database insertion.

### 2. Customizer API — background media

- Registered via `customize_register` hook.
- Setting: `rodolfo_background_video` — accepts an `.mp4` URL.
- Falls back to the static `assets/theme-bg.jpg` when no video is set.
- Rendered in `header.php` via native `<video autoplay loop muted playsinline>`.

### 3. Theme Options page

A dedicated admin page at `Appearance → Theme Options` (registered via `add_theme_page()`) exposes settings not suited to the Customizer, organised in eight tabs (ordered by setup flow):

- **General**: copyright line; Sitemap URL override; Site setup (present/missing/empty scaffolding table); maintenance mode (503 Maintenance / 200 Coming soon) with an optional custom headline and message
- **Navigation**: per-Page placement (header menu / footer row / hidden) and order; built-in footer links (FAQ, Terms of Use, Privacy Policy, Sitemap, RSS) with show/rename/reorder. Shows a warning callout when an assigned WordPress menu is overriding these settings
- **Content**: post view counter + "Resume where you left off" pill; per-context excerpt length (cards vs search); reading progress bar (enable, height 2–20px, top/bottom); numbered keyboard nav; footer date stamp (last-post or updated); reading-time estimate; post-series navigation; full-content RSS; social-card style (light default / dark); optional widget sidebar with scope; Donate button (PayPal email or custom URL, currency, label, and automatic placement after posts/pages)
- **Social Links**: GitHub, Twitter, LinkedIn and eleven other platform URLs, plus an email address (rendered as inline SVG icons in the footer). Entries are `array( label, placeholder, type )`; `type` defaults to `url`, and `email` stores a bare address that `rodolfo_social_href()` renders as a `mailto:` link.
- **Performance**: disable emojis, oEmbeds, jQuery Migrate
- **Custom Scripts**: header/body/footer script injection — requires the `unfiltered_html` capability
- **System Info**: theme-scoped diagnostics — Environment, Feature health (GD for social cards, permalinks for virtual routes, SEO-plugin standdown, docs Markdown tier), Active settings (including a menu overriding the Navigation tab), and Utility pages — with a JSON download. Links to Tools → Site Health for general environment checks rather than duplicating them
- **Documentation**: the theme's `docs/*.md` rendered as HTML, with a sub-nav (Overview / Upgrading / Changelog / Reference)

Tabs are data-driven: the `$tabs` array drives both the pill nav and the `data-panel` wrappers, and the switcher JS matches them generically, so adding a tab needs no JS changes.

**Maintenance mode.** Goes through `wp_die()`, themed via the `wp_die_handler` filter — `wp_die()`'s own stylesheet can't be overridden from the message argument, since it emits `<style>` after the body. `rodolfo_maintenance_die_handler()` renders the full document: site logo when set, brand accent, light palette to match the site default, and a fish that's suppressed under `prefers-reduced-motion`. Copy comes from `rodolfo_maintenance_copy()` — built-in defaults, overridden by `rodolfo_maintenance_title`/`_body`, then filterable via `rodolfo_maintenance_copy`. Bypass is `edit_theme_options`, not merely logged-in.

**Navigation precedence.** An assigned WordPress menu in the `primary` or `secondary` location always overrides the per-Page settings above — `rodolfo_primary_nav_links()` only falls back to the Page-driven list when no menu is assigned. The Navigation tab surfaces this explicitly rather than failing silently.

The page uses a modern-minimalist design: a gradient hero header displaying the current theme version (read live from `wp_get_theme()`), pill tabs, rounded panel cards, and a gradient primary button. Styles are inline-enqueued via `rodolfo_admin_enqueue()` and load only on this screen.

Settings registered via `register_setting()` under the `rodolfo_options_group` group, each with a `sanitize_callback`. The option-key→sanitizer map (`rodolfo_option_sanitizers()`) is the single source of truth shared by settings registration, export, import, and reset. Export/Import as JSON and a Reset button are built in.

### 4. Dynamic footer

The footer is composed of a brand block (custom logo or the site's initial in a neutral disc, wordmark with the last word accented in blue, tagline), an "Explore Topics" column driven by the site's top categories (content, not Pages), an accent divider with a centred star ornament, and a bottom bar with:

- **© line**: current year plus the footer text override, or the auto-generated "Site Name blogging since YEAR" — the start year is derived from the oldest published post via `get_posts()`, falling back to `wp_date('Y')`.
- **Legal row**: dot-separated Privacy · Terms · Accessibility · Sitemap · RSS. Privacy resolves via `rodolfo_privacy_page()` — Settings → Privacy first, then a page at `/privacy-policy` or `/privacy` — so it no longer disappears when Settings → Privacy is unset. Terms and Accessibility are ordinary Pages (their old URL options were removed in 2.1.0) — Terms resolves via the `terms` or `terms-of-use` slug, and both can be placed via Theme Options → Navigation; Sitemap uses the Theme Options override or falls back to core's `get_sitemap_url('index')` (`/wp-sitemap.xml`).
- **Social icons** in circular borders, rendered only for configured URLs.

### 5. Reading time

`rodolfo_get_reading_time()` in `functions.php` strips HTML, counts words with a Unicode-aware `preg_match_all('/\S+/u')`, and calculates reading time at 200 WPM. Returns an `esc_html()`-escaped, translatable string.

### 6. Share and Bookmark

Both buttons on single post pages are fully implemented in `main.js`:

- **Share**: calls `navigator.share()` with a `navigator.clipboard.writeText()` fallback. Shows a 2-second colour confirmation on clipboard copy. Reads post title and URL from `data-title` / `data-url` attributes on the button.
- **Bookmark**: persists an array of post IDs in `localStorage` under the key `rodolfo_bookmarks`. Restores state on page load. Toggles `aria-pressed` and a colour class for visual feedback. Reads the post ID from `data-post-id`.

### 7. Feature modules (`inc/`)

Required by `functions.php`; each hooks itself. See `docs/ssot.md` for the binding rules.

- **`meta-tags.php`** — meta description, Open Graph, Twitter Card on `wp_head` @ 5. `rodolfo_seo_plugin_active()` detects Yoast / Rank Math / AIOSEO / SEOPress / The SEO Framework and self-disables (filterable via `rodolfo_seo_plugin_active`).
- **`schema.php`** — JSON-LD: `Article` (posts), `WebSite`+`Organization`+`SearchAction` (front page), `BreadcrumbList` (singular). Same SEO-plugin guard.
- **`social-card.php`** — GD-rendered 1200×630 title card at `?rodolfo_card=<id>`, used as the OG image when a post has no featured image. Uses `assets/fonts/Inter-Bold.ttf` if present, else the GD bitmap font; returns '' (falls back to the site icon) when GD is missing. Cards tint toward their category colour and carry the site logo.
- **`favicon.php`** — links the bundled `assets/icons/site-icon.*` mark; short-circuits on `has_site_icon()`.
- **`category-color.php`** — per-category accent. `rodolfo_category_color()` returns the validated `category_color` term meta or an auto colour from `rodolfo_category_palette()` (8 muted hues, filterable) keyed by term ID; `rodolfo_category_pill_style()` returns the tinted-pill inline style. Colour picker on both category term screens, saved via the existing `rodolfo_save_category_icon` nonce.
- **`toc.php`** — auto table of contents. Filters `the_content` @ 7 to inject stable IDs into `h2`/`h3` and stash TOC markup, shown by `rodolfo_the_toc()` above the article body — only on single posts with ≥3 headings (threshold filterable via `rodolfo_toc_min_headings`). The current section highlights as you read, and on phones the TOC collapses to its heading.
- **`related-posts.php`** — `rodolfo_get_related_posts()` finds up to 3 posts by shared tags, topping up from shared categories; `rodolfo_render_related_posts()` outputs a card grid on single view (nothing when none).
- **`fonts.php`** — self-hosted Inter `@font-face` (upright + italic) + preload; bundled TTFs, silent fallback to system stack if removed.
- **`editor.php`** — block editor parity: loads the compiled theme CSS into the editor canvas via `editor-styles`, plus a shim (`assets/css/editor.css`) mapping the editor's wrappers to the front-end type scale, content width and serif headings. Registers `editor-color-palette` from `rodolfo_category_palette()` and `editor-font-sizes` from the front-end scale, and disables gradients.
- **`reading-time.php`** — word-count reading estimate (code blocks weighted at half rate), the `rodolfo_series` taxonomy with "Part N of M" navigation, and the optional full-content RSS filter.
- **`sidebar.php`** — optional widget area, off by default and hidden when empty. The theme is a single centred column by design; this exists for sites that need widgets.
- **`docs.php`** — renders the theme's own `docs/*.md` as HTML inside Theme Options → Documentation. The Markdown files stay the single source of truth (no duplicated HTML copy); output is cached per `filemtime()` so edits appear immediately. Parses with Parsedown (bundled at `vendor/parsedown/`, MIT) when available, preferring any copy a plugin has already loaded — Parsedown's class is global and unguarded, so redeclaring it would be fatal — and falls back to a deliberately minimal renderer if `vendor/` is absent **or Parsedown fails to load** (the fallback remains useful if the parser is missing or incompatible — see `ssot.md`) — enough to keep the docs readable, not a second full Markdown implementation. Task lists are converted in a post-processing pass, since Parsedown 1.8 does not support them. Output from either engine passes through `rodolfo_docs_kses()` with an explicit allowlist.
- **`setup.php`** — idempotent scaffolding (never overwrite, never delete): FAQ/Terms/Privacy/Accessibility pages on `after_switch_theme`, re-runnable from Theme Options. Alias-aware via `rodolfo_setup_find_page()`, so an existing `/privacy` or `/terms-of-use` is recognised rather than duplicated. New pages are seeded with block-markup starter content, and existing pages that are still empty are backfilled with it (3.3.1) — never-overwrite applies to non-empty content only; FAQ and Accessibility ship usable skeletons, while Privacy and Terms ship an explicitly-labelled draft outline (not legal advice) pointing at WordPress's own generator at Settings → Privacy. Creates no menus (nav is Page-driven).
- **`click-to-tweet.php`** — `[bctt]` shortcode (attrs `tweet`/`via`/`url`/`prompt`/`nofollow`) rendering an accented pull-quote with an X intent link + optional UTM tagging; registers only if the Better Click To Tweet plugin isn't already providing `[bctt]`. Handle from the `rodolfo_twitter_handle` option. Also ships as a native editor block (`rodolfo/click-to-tweet`) — a visual quote block with via/prompt controls that renders server-side through the shortcode.
- **`privacy-embeds.php`** — consent-gated embeds (off by default): YouTube / Vimeo / SoundCloud / Spotify iframes render as a click-to-load placeholder, so no third-party connection happens until the reader presses play. The placeholder is deliberately generic — a provider thumbnail would itself be a third-party request. "Always load X embeds" is remembered in the reader's own browser only. Script-based embeds (X/Twitter) pass through ungated.
- **`comment-guard.php`** — comment-form bot protection (off by default): a hidden honeypot plus a signed minimum-time trap, zero dependencies and invisible to readers; Cloudflare Turnstile as an optional second layer whose script loads only when the comment field is focused.
- **`accent.php`** — one Customizer colour control (Colors → Accent colour) recolours the whole theme: links, buttons, pills, the progress bar, login and maintenance pages. Hover/focus shades derive from the picked hue automatically; at the default cobalt the module outputs nothing.
- **`post-display.php`** — per-post Display box: hide the title (kept for screen readers), hide the featured image (share cards still use it), or go full width.
- **`unlist.php`** — Publish-box "Unlist this post" toggle; unlisted IDs (`rodolfo_unlisted_posts` option, mirrors `_rodolfo_unlisted` meta) excluded from front-end listing queries via `pre_get_posts`; singular URL stays live with `noindex`. The Posts list table gains an Unlisted column, an \"Unlisted only\" filter, and a quick-edit toggle prefilled with the real state.
- **`login.php`** — brands wp-login with the Custom Logo (or Site Icon), links it home, tints button/focus with the brand accent.
- **`breadcrumbs.php`** — `rodolfo_breadcrumbs()`: visible trail on single posts, deferring to Rank Math/Yoast breadcrumb renderers when active (trail always matches plugin schema); own Home › Category › Title fallback.
- **`llms-txt.php`** — virtual `/llms.txt` (llmstxt.org) Markdown summary for AI crawlers: pages, recent posts, topics. Built live like the `/tags/` route and served on **any permalink structure** (plain permalinks answered via a direct request-path check); unlisted posts never appear in it; `rodolfo_llms_txt_enabled`/`rodolfo_llms_txt` filters; a physical file in the web root takes precedence.
- **`aeo.php`** — AI-answer (AEO) content blocks: `[answer label]` quick-answer box; `[proof metric label cite cite_label]` verifiable-result callout; `[faqs title]` + `[faq q open]` accessible `<details>` items that also emit **FAQPage JSON-LD** in `wp_footer` from the exact on-page Q/As (only where [faq] rendered; stays on under SEO plugins since their FAQ blocks can't see our shortcodes — suppress via the `rodolfo_faq_jsonld` filter); `[compare a b caption]` table, one `Factor | A | B` row per line.

  **Writing for answer engines — author guidance.** The AEO blocks only work as well as what you put in them:
  - *Comparisons*: pick 3–4 factors readers actually decide on (price, setup time, lock-in, support), not ten trivia rows — answer engines quote small honest tables and skip sprawling ones. Name a winner per factor when there is one; "it depends" rows teach nothing.
  - *FAQs*: source questions from real objections — support tickets, reviews, comment threads, the question a reader asks *before* buying the argument. One crisp answer per `[faq]`, front-loaded: verdict first, reasoning second. Never pad with keyword-bait questions nobody asks; FAQPage JSON-LD amplifies whatever you write, filler included.
  - *`[answer]`*: put the direct answer in the first sentence, scoped and dated where it matters ("As of 2026…"). It exists to be quoted whole.
  - *`[proof]`*: one verifiable number beats three adjectives — cite the source with `cite`/`cite_label` or leave the block out.
- **`reading-progress.php`** — fixed scroll-fill bar on singular posts/pages; self-contained (own CSS + footer JS). Options `rodolfo_progress_enabled`/`_height`/`_position`.
- **`donate.php`** — `[donate url label]` shortcode → accented button linking to the owner's gateway URL (`rodolfo_donate_url`/`_label`). Can also place itself automatically: the `rodolfo_donate_auto` option (`posts` / `posts_pages`) appends the button to the content end via `the_content`, no shortcode needed; `rodolfo_donate_button()` is the shared render helper and `rodolfo_donate_auto_block` filters the auto-placed markup. Presentational only; payment processing stays in a dedicated plugin.

### 8. Test harness

`tests/test-theme.php` runs without WordPress (`php tests/test-theme.php`): static source checks, one per shipped regression. Add a check in the same release a regression is fixed.

## Template hierarchy

```text
rodolfo/
├── style.css                        # Theme header + skip link, mobile nav, footer utilities
├── functions.php                    # Enqueues, setup, hooks, Theme Options, rodolfo_icon()
├── readme.txt                       # WordPress.org readme (end users) — must stay in root
├── screenshot.png                   # 1200×900 theme screenshot
├── header.php                       # <head>, nav, background, logo
├── footer.php                       # Footer, social links, wp_footer()
├── index.php                        # Home loop + magazine hero
├── single.php                       # Single post (delegates to content-single)
├── page.php                         # Static page
├── archive.php                      # Date, author, CPT archives
├── category.php                     # Category archives (emoji icon header)
├── tag.php                          # Tag archives
├── search.php                       # Search results
├── 404.php                          # Not-found page
├── comments.php                     # Comment list + form
├── searchform.php                   # Themed get_search_form() markup
├── sidebar.php                      # Optional widget column (guarded)
├── tags-page.php                    # Virtual /tags/ index template
├── inc/                             # Feature modules (required by functions.php)
│   ├── aeo.php                   # AEO shortcodes + FAQPage JSON-LD
│   ├── breadcrumbs.php           # Breadcrumb trail (defers to Rank Math)
│   ├── category-color.php        # Per-category accent colour
│   ├── click-to-tweet.php        # [tweet] pull-quote shortcode
│   ├── docs.php                  # Renders docs/*.md in Theme Options → Documentation
│   ├── donate.php                # [donate] button + optional auto-placement
│   ├── editor.php                # Block editor parity: styles, palette, font sizes
│   ├── favicon.php               # Site-icon fallback
│   ├── fonts.php                 # Self-hosted Inter (if present)
│   ├── icons.php                 # Inline SVG icon library (Lucide, ISC) — loads first
│   ├── llms-txt.php              # Virtual /llms.txt (llmstxt.org)
│   ├── accent.php                # Customizer accent colour
│   ├── post-display.php          # Per-post display flags
│   ├── privacy-embeds.php        # Consent-gated iframe embeds
│   ├── comment-guard.php         # Honeypot + time trap + optional Turnstile
│   ├── login.php                 # Branded wp-login screen
│   ├── maintenance.php           # Maintenance / coming-soon holding page (wp_die_handler)
│   ├── meta-tags.php             # SEO description / OG / Twitter
│   ├── nav.php                   # Page-driven nav, privacy resolver, footer utility links
│   ├── system-report.php          # Theme-scoped diagnostics + JSON export
│   ├── options-actions.php        # Admin CSS, export/import/reset, menu
│   ├── options-page.php          # Theme Options screen, settings registration, sanitizers
│   ├── performance.php           # Perf toggles + header/body/footer script injection
│   ├── page-states.php            # Coming-soon Pages + latest-post stamp
│   ├── reading-progress.php      # Scroll progress bar
│   ├── reading-time.php          # Reading estimate, series taxonomy, feed content
│   ├── related-posts.php         # Related posts on single view
│   ├── schema.php                # JSON-LD structured data
│   ├── search-palette.php        # ⌘K search palette
│   ├── settings.php               # Option registry + sanitizers (persistence contract)
│   ├── setup.php                 # Idempotent page scaffolding + starter content
│   ├── sidebar.php               # Optional widget area (off by default)
│   ├── social-card.php           # Generated OG card (?rodolfo_card=<id>)
│   ├── tags.php                  # Virtual /tags/ route
│   ├── toc.php                   # Auto table of contents
│   └── unlist.php                # Per-post unlisting
├── tests/
│   └── test-theme.php               # No-WordPress regression checks
├── docs/
│   ├── readme.md                    # This file — developer documentation
│   ├── changelog.md                 # Full technical changelog
│   ├── upgrading.md                 # Roadmap (extension points + planned work) + migration history
│   └── ssot.md                      # Canonical inventory — wins on conflict
├── template-parts/
│   └── content/
│       ├── content-card.php         # Post card — used by all loop templates
│       └── content-single.php       # Single post article — used by single.php
└── assets/
    ├── css/
    │   ├── input.css                # Core CSS source (@import, @theme tokens, @plugin)
    │   └── theme.min.css            # Compiled core CSS output — enqueued by WordPress
    ├── lib/                         # UI layer (MIT; Bootstrap 5.3.8) — ui.min.css + ui.bundle.min.js (+ maps)
    ├── fonts/                       # Inter (OFL) — variable upright+italic TTFs, static bold, OFL.txt
    ├── icons/                       # site-icon.svg + PNG sizes (favicon fallback)
    ├── js/
    │   ├── main.js                  # Source — dark mode, mobile menu, share, bookmark
    │   └── main.min.js              # Minified twin served live
    └── theme-bg.jpg                 # Fallback background image
```

## Development setup

Tailwind v4 CLI. To recompile after editing templates or design tokens:

1. `npm install` (installs `@tailwindcss/cli`, `@tailwindcss/typography`, `esbuild` from `package.json`)
2. `npm run build` — compiles `assets/css/input.css` → `assets/css/theme.min.css` (minified). Use `npm run watch` while developing.
3. `npm run minify:js` — regenerates `assets/js/main.min.js` from `main.js` (keep the `.min` twin in sync; `RODOLFO_ASSET_SUFFIX` serves it live).

There is **no `tailwind.config.js`** — v4 is CSS-first. Class scanning (`@source`) and design tokens (`@theme`: brand colour ramp, `--font-sans`, `--font-display`) live in `input.css`. The `@tailwindcss/typography` plugin is loaded there via `@plugin`.

> **Note:** `style.css` carries plain-CSS rules that the shipped `theme.min.css` predates (skip link, mobile nav, footer utilities, pagination, brand-accent + serif runtime overrides). They are written to survive without a recompile; after `npm run build` picks up the `@theme` tokens, the accent/serif overrides become redundant but remain harmless.

## Roadmap

See `docs/upgrading.md` for the roadmap (near/mid-term enhancements, extension points) and the per-version migration history.
