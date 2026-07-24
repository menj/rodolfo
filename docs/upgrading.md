# Upgrading & Roadmap — Rodolfo

This document is the **forward-looking roadmap** for extending, augmenting, and
enhancing the theme, followed by the **migration history** (what to do when
moving between shipped versions).

---

## Roadmap

Organised in phases, ordered strictly by impact: repair before features,
batched quick wins before design work. Each phase merges and tests as a unit,
lands with matching assertions in `tests/test-theme.php`, and follows the
five-file version bump. Items are `[ ]` pending, `[x]` complete; finished
phases gain a `✓` on the heading.

---

### Phase 1 — Re-land the lost 3.3.x batch ✓

> A 3.9.0 state audit found the 3.3.x remediation batch never merged into this
> lineage — 3.4.0 onward was built from the pre-batch working copy, so all ten
> fixes regressed silently. Re-landed in full (confirmed not a deliberate
> drop); assertions for all ten are pinned permanently in the test suite so
> the batch cannot regress unnoticed a second time.

- [x] **1.1** `'sanitize_callback' => 'esc_url_raw'` on the
  `rodolfo_background_video` Customizer setting — the only setting storing raw
  input; a WordPress.org hard fail.
- [x] **1.2** Guard the settings import with `is_uploaded_file()`; stop passing
  the tmp path through `sanitize_text_field()`.
- [x] **1.3** Plain-permalink fallback for `/llms.txt` — rewrite rules only fire
  with pretty permalinks and crawlers request the literal path; add an early
  `REQUEST_URI` check in `inc/llms-txt.php`, path-compared against `home_url()`
  so subdirectory installs resolve.
- [x] **1.4** Exclude unlisted posts from the llms.txt body — the raw
  `get_posts()` call bypasses the unlist module's main-query filter; pass
  `post__not_in` from `rodolfo_unlisted_ids()`.
- [x] **1.5** Localise the hardcoded `aria-label` strings in `main.js`
  (`Share`, `Link copied!`, `Bookmark`, `Remove bookmark`) via
  `wp_localize_script()` as `rodolfoL10n`; regenerate `main.min.js`.
- [x] **1.6** Correct the stale enqueue comment claiming `style.css` "contains
  only the theme header, no rules".
- [x] **1.7** Use `RODOLFO_VERSION` in `rodolfo_scripts()` and the options-page
  version badge instead of re-reading `style.css` per request.
- [x] **1.8** Key the one-time rewrite-flush flags in `inc/tags.php` and
  `inc/llms-txt.php` to `RODOLFO_VERSION` instead of the literal `'1'`, so
  routing changes re-flush on every release.
- [x] **1.9** Hide the autoplay background video under
  `prefers-reduced-motion: reduce` (and drop smooth-scroll to `auto`).
- [x] **1.10** `aria-hidden="true"` on both decorative background wrappers in
  `header.php`.

---

### Phase 2 — Test-suite repair ✓

> Eight assertions fail on the pristine 3.9.0 package; all are false positives
> tripping on comments, inline CSS, or renamed markup — sharpen the
> assertions, do not change theme code. Plus one real test-file bug (2.1).
> Batches with Phase 1 in a single session.

- [x] **2.1** `report guards the namer call` — `$sr` is used two lines before
  it is defined (the source of the PHP warning at line ~275); move the
  `rd_read( 'inc/system-report.php' )` assignment above its first use.
- [x] **2.2** `front end defaults light` — trips on the *comment* explaining
  `prefers-color-scheme` is deliberately not consulted; assert on the inline
  script instead (e.g. `window.matchMedia` absent).
- [x] **2.3** `nav table not in general panel` — position check broken by
  `rodolfo-navtable` appearing first in the inline admin CSS; anchor to the
  first occurrence after `data-panel="general"` (Phase 3 also fixes it).
- [x] **2.4** `docs renders tables` — asserts a `rodolfo-docs-table` class the
  renderer no longer emits; update to the current markup.
- [x] **2.5** `donate does NOT handle card data/webhooks` — the docblock
  *mentioning* IPN/webhooks trips the substring check; assert on function
  calls, not comments.
- [x] **2.6** Triage and sharpen the remaining three 3.4–3.5-era failures
  (`active tab clamped to known tabs`, `coming-soon pages noindex`,
  `site updated cached`) — adjacent assertions on the same files pass, so
  these are likely the same drift pattern.

---

### Phase 3 — Finish the admin asset separation ✓

> The JS half shipped (`assets/js/admin.js`); the options-page CSS is still an
> inline PHP string — uncacheable, unoverridable, and hostile to a strict CSP.

- [x] **3.1** Extract it to `assets/css/admin.css`, enqueued behind the
  options-page hook check. Incidentally repairs test 2.3.

---

### Phase 4 — Build hygiene (local)

> Requires `npm run build` on the maintainer's machine, so it phases alone.

- [ ] **4.1** Fold the CSS backfill into the compiled sheet: run
  `npm run build` (the layout utilities are already safelisted in `input.css`
  via `@source inline`), then delete the temporary `@backfill` block at the
  end of `style.css`. Ends the stale-compiled-CSS class of bugs for good.

---

### Phase 5 — Quick-wins feature batch ✓

> The four small, independent modules from the Avada 7.15.6 feature review
> (July 2026) — one session together. Concepts only: Avada is split-licence,
> so everything is implemented from scratch.

- [x] **5.1** **Consent-gated embeds** (`inc/privacy-embeds.php`). Wrap known
  providers via `embed_oembed_html` in a click-to-load facade — a generic
  accent-coloured placeholder (title + play glyph), *not* the provider
  thumbnail, so the reader's browser contacts no third party before consent.
  Optional localStorage "always allow" per provider. Privacy and performance
  in one: same instinct as the self-hosted fonts and llms.txt.
- [x] **5.2** **Comment protection** (`inc/comment-guard.php`). Honeypot field
  plus a minimum-time trap on `preprocess_comment` as the zero-dependency
  default; Cloudflare Turnstile as an opt-in site-key field, its script
  lazy-loaded only when the comment field gains focus. The form currently has
  no bot protection at all.
- [x] **5.3** **Recommendations in System Report.** Extend diagnostics with
  advice: each check a boolean + one-line explanation + fix link.
  Rodolfo-specific first — plain permalinks detected (breaks `/tags/` and
  `/llms.txt` until 1.3 lands), `SCRIPT_DEBUG`/`WP_DEBUG` on a live site,
  comment pagination off, autoloaded-options bloat, views enabled with
  nothing consuming `rodolfo_popular_posts()`.
- [x] **5.4** **Rolling daily views** (`inc/views.php`). Alongside the all-time
  counter, a 7-day date-keyed array in one meta key, pruned on write — no
  cron, no midnight reset. Add a `$window` argument to
  `rodolfo_popular_posts()` to enable "trending today" and "popular this
  week" surfaces.

---

### Phase 6 — Near-term features (pre-review backlog) ✓

> The items already on the roadmap before the Avada review; each is
> self-contained and ready to build.

- [x] **6.1** **AEO author guidance**: expand docs with tips on honest 3-4
  factor comparisons and objection-driven FAQs pulled from support
  tickets/reviews (the `[proof]` callout shipped in 2.8.0).
- [x] **6.2** **Click-to-Tweet Gutenberg block.** Ship a native block wrapping
  the `[bctt]` shortcode (`inc/click-to-tweet.php`) so editors get a visual
  insert, not just a shortcode — mirror the block in the original plugin's
  `assets/block/`.
- [x] **6.3** **Unlist Posts: bulk + column.** Add a "Unlisted" quick-edit
  toggle and an admin list-table column/filter, on top of the current
  Publish-box checkbox.
- [x] **6.4** **Author card everywhere.** Reuse `rodolfo_render_author_card()`
  on author archives, not just single posts.

---

### Phase 7 — Design-decision features ✓

> From the same Avada review — each needs design decisions, so one focused
> session apiece rather than a batch.

- [x] **7.1** **Accent colour picker.** One Customizer control writing a single
  `--rd-accent` variable, collapsing the four hardcoded cobalt sites
  (`style.css` tokens, `input.css`, admin CSS, maintenance page). Fully
  delivers configurable colour schemes rather than today's partial credit
  (dark mode + category colours).
- [x] **7.2** **Feature-toggles panel.** A "Features" tab with one switch per
  optional module, the module loop checking the option before `require` —
  sites that don't use a feature don't pay for it. Advises nothing (that's
  5.3); this *disables*.
- [x] **7.3** **Per-post display options.** Hide-title / hide-featured-image /
  full-width toggles extending the unlist metabox pattern.
- [x] **7.4** **Sticky title strip on scroll.** Slim post-title header on long
  reads, sharing the `reading-progress.php` scroll handler (one rAF-throttled
  listener updating both), IntersectionObserver on the post `<h1>`, appearing
  without animation under `prefers-reduced-motion`. Pairs with the resume
  pill: title up top, position memory below.

---

### Phase 8 — Mid-term polish ✓

> Larger design/feature work; sequence within the phase is open.

- [x] **8.1** **Table of contents affordances.** Sticky/scroll-spy highlight
  for the current section in `inc/toc.php`; collapse on mobile.
- [x] **8.2** **Related posts relevance.** Weight shared tags over categories
  and de-dupe against the same-author list; optional manual override via post
  meta.
- [x] **8.3** **Richer OG cards.** Per-category background treatment and a site
  logo lockup in `inc/social-card.php`.
- [x] **8.4** **⌘K search surfaces.** Index pages and categories (not just
  posts), and add recent-searches history.
- [x] **8.5** **Comment experience.** Optional threaded-reply affordances and a
  lighter, theme-native comment form skin.

---

### Extension points (how to build the above)

- **New feature module.** Drop `inc/<name>.php`, add `'<name>'` to the module
  loop in `functions.php`. Each module is self-contained and hook-driven.
- **New Theme Option.** Add the key + sanitizer to `rodolfo_option_sanitizers()`
  (export/import/reset derive from it automatically), then a field in the
  matching tab. Array options use `rodolfo_sanitize_id_list`.
- **New tweak/toggle.** Follow the `pre_get_posts` / `wp_robots` patterns in
  `inc/unlist.php`; gate risky output behind `current_user_can()` + nonces.
- **Filters for site owners.** `rodolfo_seo_plugin_active`, `rodolfo_bctt_utm`,
  `rodolfo_bctt_atts`, `rodolfo_unlist_enabled`, `rodolfo_progress_active`,
  `rodolfo_faq_jsonld`, `rodolfo_llms_txt_enabled`, `rodolfo_llms_txt`, `rodolfo_breadcrumbs_enabled`, `rodolfo_ai_crawlers_welcome` — extend behaviour without
  editing the theme.
- **Always update the docs** per audience (see `docs/ssot.md`), add a test
  assertion in `tests/test-theme.php`, and bump the version in all five files.

### Deliberately out of scope

- **PWA manifest / install prompt** (from the Avada review) — ~30 lines behind
  a toggle if ever wanted, but install prompts only pay off with genuine
  return visitation; last by reader value. Also considered and rejected from
  Avada: the page builder, mega menus, bundled commercial sliders, WooCommerce
  layer, portfolio CPTs (content loss on theme switch), and demo importers.
  Already shipped independently: branded auth (`inc/login.php`), view counting
  (`inc/views.php`), local fonts, maintenance mode.
- **Changing the login URL** (WPS Hide Login) — security infrastructure; belongs
  in a plugin/mu-plugin, not a theme (theme switch would relock the door).
- Newsletter/email capture, comment reactions, a dedicated RSS page, and a full
  `theme.json` block-editor mapping — revisit only if product direction calls
  for them.

---

## Migration history

## Upgrading to 3.10.0

No action required. Two optional features are off until enabled in Theme
Options → Content: consent-gated embeds and the comment guard. The re-landed
3.3.x fixes, the block, the unlist affordances, trending rankings and the
System Info recommendations are all automatic. The rewrite-flush flags are now
version-keyed, so /tags/ and /llms.txt re-flush themselves on this update.

## Upgrading to 3.9.0

No action required — every new feature is off by default. Under **Theme Options → Content**: **Reader engagement** covers post view counts and the "Resume where you left off" pill; **Excerpt length** sets card vs search word counts. On the WordPress profile screen, authors will find new fields for their social accounts — populated ones appear as icons on the author card at the end of their posts. The old global 20-word excerpt is now 24 for cards and 40 for search results; if you preferred the previous behaviour, set both fields to 20.

## Upgrading to 3.8.2

**Update immediately if you run an SEO plugin.** Since 3.7.1 the System Info tab produced a critical error (and Documentation appeared blank) whenever an SEO plugin was active; the tab-navigation fix in 3.8.1 made the pages reachable and therefore made the error visible. 3.8.2 fixes the underlying missing function.

## Upgrading to 3.8.1

No action required. If the Theme Options tabs did not respond on your site — typical when a security plugin enforces a strict Content-Security-Policy — they now work regardless: each tab is a real link that functions without JavaScript, and the instant switching loads from a proper script file that strict policies permit.

## Upgrading to 3.8.0

**No action required — internal reorganisation only.** `inc/options-page.php` was split into `inc/settings.php` (option registry and sanitizers), `inc/options-actions.php` (export/import/reset, menu) and `inc/options-page.php` (the screen itself). Every function keeps its name and signature, so child themes and snippets are unaffected; only the file a given function lives in has changed. If you maintain patches against `options-page.php`, re-target them.

## Upgrading to 3.7.1

No action required. If you use an SEO plugin, a page marked "coming soon" could previously emit a second robots tag alongside the plugin's own; the theme now works through WordPress's shared `wp_robots` setting so only one is produced. System Info also now names which SEO plugin has taken over the theme's meta tags and structured data.

## Upgrading to 3.7.0

No action required. Structured data now covers pages, archives, author pages, search results and post series in addition to posts and the homepage — it is emitted automatically. If an SEO plugin is active the theme continues to stand down entirely, as before, so nothing conflicts.

## Upgrading to 3.6.0

No action required — all three additions are off by default. Under **Theme Options → Content → Navigation & freshness** you can switch on numbered keyboard navigation (press 1–9 for header links) and a footer date stamp — choose *Last post* (publication date of your newest post, so editing an old post will not change it) or *Updated* (most recently edited post). Your homepage also now carries published and modified dates in its structured data, independently of whether the visible stamp is on. Separately, any Page can be marked **Coming soon** from a box in the editor sidebar: it stays in your navigation and shows a note about what is planned, and is set to `noindex` while in that state.

## Upgrading to 3.5.0

No action required. The System Info tab now reports on the theme itself — whether each feature has what it needs to run, which settings are affecting the site, and whether the utility pages exist — with a **Download report (JSON)** button for support requests. It deliberately does not duplicate Tools → Site Health, which remains the place for server, database and plugin-conflict checks.

## Upgrading to 3.4.0

**No action required — internal reorganisation only.** `functions.php` was split into five `inc/` modules (`icons`, `nav`, `options-page`, `maintenance`, `performance`). Every function keeps its name and signature, so child themes and snippets calling theme functions continue to work unchanged. The only thing that moved is *where* the code lives; if you maintain patches against `functions.php`, re-target them at the module files.

## Upgrading to 3.3.1

If your FAQ, Terms, Privacy or Accessibility pages are blank — which they will be if they were created before 2.12.0 — go to **Theme Options → General → Site setup** and press **Create missing pages & fill empty ones**. Starter content is added to any that are still empty. Pages that already have content are not touched.

## Upgrading to 3.3.0

**Your site will start in light mode from now on.** Previously it followed the visitor's operating-system dark-mode setting on a first visit; now it always starts light and only switches if the visitor uses the toggle. Anyone who has already chosen dark keeps that choice — it is stored in their browser and is unaffected by this upgrade.

If you preferred the old behaviour, it lived in the inline script in `header.php`; a child theme can restore the `window.matchMedia('(prefers-color-scheme: dark)')` check there.

Social share cards and the maintenance page now default to light to match. The dark card style is still available under Theme Options → Content → Social share cards; existing sites that never set the option will see newly generated cards change from dark to light.

## Upgrading to 3.2.0

**Nothing changes on your site until you opt in.** Every new feature in 3.2.0 is off by default except the editor work, which only affects the admin.

**Block editor.** The editor canvas now uses the theme's typography, content width and colour palette, so drafts look like the published page. This is automatic and admin-only — no front-end output changes. If you have a child theme overriding post typography, check that `assets/css/editor.css` still matches; the shim restates the front-end scale and the two must be kept in step.

**Threaded comments** now nest properly. If replies previously landed flat despite threading being on in Settings → Discussion, that was a missing `comment-reply` enqueue and is fixed. Existing comments keep their stored parent, so old threads may visibly re-nest — that is the correct structure finally rendering.

**New opt-in settings** under Theme Options → Content: reading-time estimate, post-series navigation, full-content RSS, social-card style, and the widget sidebar.

**Post series.** Adds a `rodolfo_series` taxonomy under Posts → Series. Assign posts to a series, order them with the page-attributes order field, and enable the setting. A series with fewer than two posts renders nothing.

**Sidebar.** Off by default, and deliberately so — the single centred column is what makes the theme comfortable to read. It needs three things to appear: the setting enabled, the current view matching the chosen scope, and at least one widget placed. An enabled-but-empty sidebar renders nothing rather than leaving a dead column.

## Upgrading to 3.1.0

No action required. The maintenance / coming-soon holding page is now themed: it picks up your site logo when one is set, uses the brand accent, and shows friendlier default copy ("The fish are swimming away" for maintenance, "The fish are still being arranged" for coming soon). The subtext always states plainly that the site is returning, so a visitor is never left thinking it is permanently broken.

If you prefer your own wording, set **Headline** and **Message** under Theme Options → General → Maintenance mode. Both are blank by default and fall back to the built-in copy. For programmatic control, filter `rodolfo_maintenance_copy` — it receives the mode, so maintenance and coming-soon can differ.

The 503 / 200 status distinction is unchanged. The page now also sends `noindex` and `nocache_headers()`, so a cached holding page cannot outlive the outage.

> **3.1.1**: internal only. The page is still produced by `wp_die()` — themed through the `wp_die_handler` filter rather than a standalone renderer — so core's bail-out path and any plugin filters on `wp_die` apply again. Output is unchanged. If you wrote code against `rodolfo_maintenance_page()`, it is now `rodolfo_maintenance_die_handler()`.

## Upgrading to 3.0.0

**No action required, and nothing breaks.** Despite the major version bump, 3.0.0 is a
milestone marker for the 2.10–2.14 line rather than a compatibility break. No option was
renamed or removed, the settings export/import JSON is unchanged, and every stored value
survives the upgrade. If you are coming from any 2.x release you can update in place.

What you gain, briefly — see the entries below for detail:

- **Documentation tab** — the theme's own docs render inside Theme Options.
- **Eight tabs** — the overloaded General tab is split into General, Navigation and Content.
- **Automatic Donate placement** — no shortcode required.
- **Email and GitHub social links**, plus privacy-slug tolerance and a reading-bar fix.

The only behavioural change to be aware of: a reading-bar height previously saved as 0 or
1px now displays at 2px, because such values rendered an invisible hairline. Re-save the
field to store a valid value. See the 2.11.1 note below.

## Upgrading to 2.13.0

No action required. Theme Options gains a **Documentation** tab that renders the theme's own `docs/*.md` files as formatted HTML, so the docs are readable from the dashboard without opening the theme folder. Nothing is duplicated — the Markdown files remain the single source of truth, and editing one updates the tab on the next load.

> **2.13.1**: Markdown is now parsed by Parsedown (bundled at `vendor/parsedown/`, MIT-licensed, credited in `readme.txt`). If a plugin or another theme has already loaded a `Parsedown` class, that copy is used and the bundled one is skipped — Parsedown's class is global and unguarded, so this check is what prevents a fatal redeclaration. If `vendor/` is absent, a built-in fallback renderer keeps the tab working.

> **2.13.2**: the fallback renderer was reduced to a minimal safety net now that Parsedown does the real work. If you strip `vendor/` from a distribution, the docs still render, but tables and task lists degrade to plain lines. Ship `vendor/parsedown/` to get full fidelity.

**Child themes.** The document list is filterable via `rodolfo_docs_files`, so a child theme can add or remove entries without editing `inc/docs.php`.

## Upgrading to 2.12.0

Mostly automatic, but three things are worth knowing.

**Privacy page.** `rodolfo_privacy_page()` is now the single resolver for the Privacy Policy page: **Settings → Privacy** first, then a published page at `/privacy-policy`, then `/privacy`. If you previously had a page at `/privacy` that the theme ignored, it will now be picked up — used for the footer link and excluded from the automatic Page menu. If you ended up with *two* privacy pages under earlier versions (one you made, one setup created), the theme now consistently prefers the one registered under Settings → Privacy; delete or unpublish the duplicate at your convenience. Nothing is deleted for you.

**Scaffolded page content.** New pages created by **Create anything missing** now ship with starter content. Setup still never overwrites, so pages created by earlier versions stay exactly as they are — they will simply be reported as *"Present but empty"* in the Site setup table, with a link to edit. Privacy and Terms bodies are deliberately an outline flagged as a draft, not usable legal text; use **Settings → Privacy** to generate a real Privacy Policy draft.

**Navigation warning.** If a WordPress menu is assigned to the Primary or Secondary location, it overrides the per-Page Navigation settings entirely. This has always been true; 2.12.0 just says so, via a callout on the Navigation tab. If your Navigation settings appear to do nothing, go to **Appearance → Menus → Manage Locations** and set the location to "— Select —".

## Upgrading to 2.11.0

No action required. Theme Options gained two tabs — **Navigation** and **Content** — carved out of the crowded General tab. No option was renamed or removed, so stored values and the JSON export/import format are unchanged; settings have only moved between tabs. The GitHub social field (retired in 2.6.0) is available again under Social Links; if you still had a `rodolfo_social_github` value in the database it becomes visible once more.

The Donate button can now place itself: set **Theme Options → Content → Donate button → Automatic placement** to append it after every post, or every post and page. The `[donate]` shortcode continues to work unchanged. Note that a custom donate URL still takes priority over the PayPal email when both are filled in.

> **2.11.1**: the reading-progress bar height is now clamped to 2–20px. A previously-saved height of 0 or 1 rendered an invisible hairline; such values now display at 2px, and re-saving the field stores a valid value.

## Upgrading to 2.2.0

No action required — four standalone plugins are now built in. If you currently run any of them, you can deactivate/remove them (Rodolfo takes over):

- **Better Click To Tweet** → use `[bctt tweet="Your quote" via="handle"]`. Set a default handle under **Appearance → Theme Options → Social**. (If you keep the plugin active, Rodolfo stands aside so `[bctt]` isn't double-registered.)
- **Login Logo** → your Custom Logo (or Site Icon) now brands the login screen automatically — no `wp-content/login-logo.png` needed.
- **Unlist Posts** → tick **Unlist this post** in the Publish box. Hidden from listings/search/feeds; the direct link still works.
- **WP SEO Search** → search results and thin archives are noindexed automatically (skipped if a general SEO plugin is active).

> **Not merged: WPS Hide Login.** Changing the login URL from a theme is fragile and can lock you out on theme switch — keep it as a plugin.

> **2.2.1**: the layout utilities are safelisted in `input.css`. If you rebuild CSS (`npm run build`), you can then delete the temporary `@backfill` block at the end of `style.css`. Nothing to do otherwise.

## Upgrading to 2.1.0

No action required. The main menu now lists your top-level Pages automatically. To move a page into the footer instead, go to **Appearance → Theme Options → Navigation** and tick it. The old "Terms page URL" and "Accessibility page URL" options were removed — create/keep those as normal Pages and they appear in the menu automatically (or tick them under Navigation to keep them in the footer). The Tags link was dropped from the menu; the tag list is still reached via the Tags icon by the search box.


---

## Version history

- [Upgrading from 1.0.0 to 1.0.1](#upgrading-from-100-to-101)
- [Upgrading from 1.0.1 to 1.0.2](#upgrading-from-101-to-102)
- [Upgrading from 1.0.2 to 1.1.0](#upgrading-from-102-to-110)
- [Upgrading from 1.1.0 to 1.2.0](#upgrading-from-110-to-120)
- [Upgrading from 1.2.0 to 1.3.0](#upgrading-from-120-to-130)
- [Upgrading from 1.3.0 to 1.4.0](#upgrading-from-130-to-140)
- [Upgrading from 1.4.0 to 1.5.0](#upgrading-from-140-to-150)
- [Upgrading from 1.5.x to 1.6.0](#upgrading-from-15x-to-160)
- [Upgrading from 1.6.x to 1.7.0](#upgrading-from-16x-to-170)
- [Upgrading from 1.7.x to 1.8.0](#upgrading-from-17x-to-180)
- [Upgrading from 1.8.x to 1.9.0](#upgrading-from-18x-to-190)
- [Upgrading from 1.9.x to 1.10.0](#upgrading-from-19x-to-1100)
- [Upgrading from 1.10.x to 1.11.0](#upgrading-from-110x-to-1110)
- [Upgrading from 1.11.x to 2.0.0](#upgrading-from-111x-to-200)
- [Remediation roadmap (1.0.2 → 1.4.0) — completed, archived](#remediation-roadmap-102--140--completed-archived)

---

## Upgrading from 1.0.0 to 1.0.1

Version 1.0.1 introduces a few new features that you may want to configure after updating:

### 1. Category Icons
Version 1.0.1 adds the ability to display an emoji icon for each of your categories on the category archive pages.
- After updating, navigate to **Posts > Categories**.
- Edit your existing categories.
- You will see a new field at the bottom of the form called **Category Icon (Emoji)**.
- Paste an emoji (e.g. ⚡, 💻, 📊) into this field and save.

### 2. Dynamic Footer
The footer now automatically reads your Site Name and the year of your first published post. No manual changes to `footer.php` are required anymore to customize this text!

### 3. Background Art
The opacity of the background art has been reduced. If you were relying on it being darker, it is now much more subtle to improve reading legibility across all pages. No action is required.

---

## Upgrading from 1.0.1 to 1.0.2

Version 1.0.2 adds a dedicated Theme Options page under Appearance → Theme Options.

### 1. Social Links
Navigate to **Appearance → Theme Options** and enter your GitHub, Twitter, and LinkedIn profile URLs. Icons will appear automatically in the footer once saved.

### 2. Footer Text Override
A new field in Theme Options lets you replace the automatic "blogging since [year]" footer text with any custom string.

---

## Upgrading from 1.0.2 to 1.1.0

Version 1.1.0 is a security and stability release. Most changes are self-contained and require no action, but three items may affect your site's behaviour after updating.

### 1. Tags navigation button (action required to activate)

The Tags icon in the header navigation no longer points to a hardcoded URL. It now only renders when a WordPress page with the slug `tags` exists.

- If you want the button to appear: go to **Pages → Add New**, set the slug to `tags`, and publish.
- If you do not create this page, the button is simply hidden — no 404, no broken link.

### 2. Three new template files included

`comments.php`, `404.php`, and `search.php` are included in this release. No action is required — WordPress will use them automatically. If you have a child theme that defines any of these files, the child theme's versions will take precedence as normal.

### 3. Share and Bookmark buttons are now functional

The Share and Bookmark buttons on single post pages were previously visible but non-functional. They are now implemented:

- **Share**: uses the browser's native Share sheet where supported, falls back to copying the URL to clipboard.
- **Bookmark**: saves post IDs to browser `localStorage` under the key `rodolfo_bookmarks`. Bookmarks are per-device and per-browser — they are not stored server-side.

No configuration is required. Existing posts are unaffected.

### 4. Category icon form now has CSRF protection

A nonce field has been added to the category Add and Edit forms. This is a security fix — no visible change in behaviour, and existing category icons are unaffected.

---

## Upgrading from 1.1.0 to 1.2.0

Version 1.2.0 is an infrastructure and template completeness release. Most changes are transparent, but two items require awareness.

### 1. Tailwind CSS is now compiled — no CDN (action required if self-hosting assets)

The `cdn.tailwindcss.com` script is gone. All styles are now served from `assets/css/theme.min.css`, which is compiled from the theme's templates using the Tailwind CLI. If you are deploying the theme manually (not via the zip), ensure `assets/css/theme.min.css` is present. To recompile after any template changes:

```bash
npm install
node node_modules/tailwindcss/lib/cli.js -i assets/css/input.css -o assets/css/theme.min.css --minify
```

### 2. Three new archive templates — no action required

`archive.php`, `category.php`, and `tag.php` are now included. WordPress will route archive requests to these templates automatically. If you have a child theme overriding any of these, child theme templates take precedence as normal.

Category pages will look identical to before — the emoji icon, description, and article count header is now in `category.php` rather than in an `is_category()` branch inside `index.php`.

### 3. Lucide Icons pinned to v0.383.0

The icon library is now loaded from a specific versioned URL rather than `@latest`. This has no visible effect on the site, but ensures icons cannot be broken by an upstream Lucide release.

---

## Upgrading from 1.2.0 to 1.3.0

Version 1.3.0 is a code quality and extensibility release. All changes are internal — no visible differences to site visitors, with one exception.

### 1. Custom logo (action available, not required)

You can now set a site logo via **Appearance → Customize → Site Identity**. When a logo is uploaded, it replaces the default Gravatar in both the header and footer. If no logo is set, the Gravatar fallback continues to display exactly as before. No action is required to maintain the current appearance.

### 2. template-parts — if you have a child theme

If your child theme overrides `single.php`, be aware the article markup has moved to `template-parts/content/content-single.php`. Your child theme's `single.php` will still take precedence, but if you want to customise only the article header, you can now override `template-parts/content/content-single.php` in your child theme instead.

Similarly, the post card markup used in all archive, category, tag, search, and home loops is now in `template-parts/content/content-card.php` — override this single file to change cards across every template at once.

---

## Upgrading from 1.3.0 to 1.4.0

Version 1.4.0 is a settings infrastructure release. The Theme Options page is rebuilt — all your existing settings (footer text, social links) are preserved automatically. No data migration is needed.

### 1. Export your settings first (recommended)

Before upgrading, visit **Appearance → Theme Options** on your current install and click **Export Settings** to download a JSON backup. If anything goes wrong during the upgrade, you can restore with **Import Settings**.

### 2. New settings available — all disabled by default

The following are off by default and require you to opt in:

- **Performance → Disable Emojis** — removes the WordPress emoji script and DNS prefetch.
- **Performance → Disable Embeds** — removes the oEmbed auto-embed script.
- **Performance → Disable jQuery Migrate** — removes the jQuery backward-compatibility shim. Only enable if you are confident your plugins do not use deprecated jQuery APIs.
- **General → Maintenance mode** — set to Disabled by default. Change to Maintenance (503) or Coming Soon (200) as needed.
- **Scripts** — three textarea fields for injecting HTML into the `<head>`, `<body>` open, and `</body>` close. All empty by default.

### 3. Existing settings location

Footer text and social links are in the same place as before, now organised under the **General** and **Social** tabs respectively.

---

## Upgrading from 1.4.0 to 1.5.0

Version 1.5.0 is a hardening and compliance release. Most changes are transparent, but five are visible:

### 1. Icons no longer load from a CDN — no action required

Icons are now inline SVGs rendered by PHP (`rodolfo_icon()` in `functions.php`). The unpkg Lucide script is gone. Icons render identically but appear instantly and work with JavaScript disabled.

### 2. Inter no longer loads from Google Fonts (action optional)

The remote Google Fonts request was removed for GDPR compliance. The theme now uses your operating system's default sans-serif. To restore Inter, self-host the woff2 files in `assets/fonts/` and add `@font-face` rules to `assets/css/input.css`, then recompile Tailwind.

### 3. Mobile navigation added

Phones now get a hamburger menu with the primary menu and a search field. No configuration needed — it mirrors your desktop menu.

### 4. Header/footer avatar fallback changed

When no custom logo is set, the theme previously showed a Gravatar derived from the admin email (which leaked the email's MD5 hash in public HTML). It now shows the site's initial in a neutral disc. Set a real logo via **Appearance → Customize → Site Identity**.

### 5. Redesigned footer with legal links

The footer now has a brand block (accented wordmark + tagline), an "About the Site" column (assign a menu to the `footer` location, or category links appear), and a bottom bar with the © line, a legal row, and circular social icons.

The legal row is configurable under **Appearance → Theme Options → General → Legal links**:

- **Privacy** — uses the page set in **Settings → Privacy**; hidden if none.
- **Terms / Accessibility** — enter URLs in Theme Options; hidden when blank.
- **Sitemap** — points to WordPress core's `/wp-sitemap.xml` automatically; enter an override URL if an SEO plugin provides your sitemap (e.g. `/sitemap_index.xml`).
- **RSS** — always shown.

The new fields are included in Export/Import/Reset like every other option.

### Behaviour changes to note

- **Maintenance mode** now hides the site from logged-in subscribers too — only users who can edit themes bypass it.
- **Scripts fields** (header/body/footer injection) now require the `unfiltered_html` capability. On multisite, regular admins cannot edit them.
- **Settings import** sanitises all values; script fields in an import are ignored without `unfiltered_html`.

---

## Upgrading from 1.5.x to 1.6.0

No action required — 1.6.0 is a cosmetic and organisational release.

- **Theme Options got a new look**: same settings, same tabs, now with a modern design showing your theme version at the top. Two tabs were renamed for clarity: "Social" → "Social Links", "Scripts" → "Custom Scripts". All your saved values are untouched.
- **Documentation moved**: developer docs now live in the theme's `docs/` folder. If you bookmarked `readme.md`, `changelog.md`, `upgrading.md`, or `ssot.md` in the theme root, they are now at `docs/…`.

---

## Upgrading from 1.6.x to 1.7.0

1.7.0 adds features; nothing is removed and no existing layout changes. Points to note:

### Run setup once

Go to **Appearance → Theme Options → General → Site setup** and click **Create anything missing**. It creates Privacy, Terms, Accessibility, and Tags pages and a Footer menu — only the ones you don't already have. It never edits or deletes existing content, so it is safe to click more than once. (New installs get this automatically on activation.)

### SEO tags are now built in

The theme now outputs SEO description, social-share (Open Graph / Twitter) tags, and Google structured data. **If you run an SEO plugin** (Yoast, Rank Math, All in One SEO, SEOPress, The SEO Framework), the theme detects it and stays silent so nothing is duplicated — no action needed.

### Category colours

Each category now has an accent colour on its badge and a thin line on each card. The theme auto-assigns a tasteful colour; to choose your own, edit a category (**Posts → Categories**) and pick a **Category Colour**.

### Optional: restore Inter

As of 1.7.1 Inter ships with the theme — nothing to do. (In 1.7.0 you had to add the font file yourself.)

### Favicon

A built-in site icon now shows in browser tabs until you set your own under **Appearance → Customize → Site Identity**.

---

## Upgrading from 1.7.x to 1.8.0

No action required — everything below is automatic.

- **Table of contents**: long posts (3+ subheadings) automatically show a “On this page” box above the article. Nothing to configure; short posts are unaffected.
- **Related articles**: single posts now end with up to three related posts, chosen by shared tags and categories.
- **Serif titles**: headlines now use an elegant serif typeface. It uses fonts already on the reader's device — no downloads.
- **New accent colour**: the theme's blue is now a distinctive cobalt. Your per-category colours are unchanged.
- **For developers**: the theme now uses Tailwind v4 with a working `package.json` — run `npm install && npm run build` to recompile styles. See `docs/readme.md`. If you never touch the CSS source, nothing changes for you; the compiled stylesheet ships in the theme.

---

## Upgrading from 1.8.x to 1.9.0

No action required — everything below is automatic.

- **Instant search**: press ⌘K / Ctrl+K (or click the search box, or “/”) for a search-as-you-type panel. The normal search still works with JavaScript off.
- **Magazine homepage**: the home page leads with one large featured story beside the next two. No settings to change.
- **Richer share previews**: social images now include the category colour and author.

---

## Upgrading from 1.9.x to 1.10.0

No action required.

- **Image-less posts** now show a category-tinted placeholder with the category icon instead of an empty box.
- **Author card**: single posts end with an “About the author” block when the author has filled in their Biographical Info (Users → Profile). Empty bios hide it.
- **Tighter homepage spacing** and a rebalanced featured hero. If your homepage looked lop-sided or links seemed unresponsive on 1.9.0, both are resolved here.

---

## Upgrading from 1.10.x to 1.11.0

Mostly automatic. One optional step for older installs.

- **Built-in Tags page** at `/tags/` — no page or menu to create. If it shows “not found” immediately after updating, visit **Settings → Permalinks** once and click Save to refresh permalinks (the theme also attempts this automatically).
- **Cleaner navigation**: utility pages (Terms, Accessibility) no longer appear in the main menu; the header shows Tags plus your top-level pages. The footer’s topics column fills automatically from your most-used categories when no footer menu is assigned. Existing sites are tidied up automatically on the next admin visit.
- **Refreshed article cards** with a comma-separated topic line and an author/date footer.
- **Logo**: an uploaded logo now displays at its natural shape (no forced circle/border) in the header and footer.

---

## Upgrading from 1.11.x to 2.0.0

No action required.

> **2.0.1**: the UI layer is now scoped in a CSS cascade layer (`@layer rodolfo-ui`), so the theme's own styling always wins over the UI toolkit. Nothing to do; existing markup is unaffected.

- **Bundled UI layer**: the theme now ships a local UI framework (grid, components, and interactive widgets — modals, dropdowns, carousels) alongside its core styles. Everything loads locally; nothing is fetched from the internet.
- The UI layer uses theme-native file and script names (`assets/lib/ui.*`, handle `rodolfo-ui`). No action needed.
- **Developers**: if you deploy a build step, the UI layer lives at `assets/lib/` and is enqueued under the `rodolfo-ui` handle before the core sheet. See `docs/readme.md`.

---

## Remediation roadmap (1.0.2 → 1.4.0) — completed, archived

The 1.0.2 audit raised 18 findings (7 critical, 6 warnings, 5 advisories), remediated
across 7 phases. **All 25 items were completed by 1.4.0**; the per-phase checklist was
removed in 3.5.2 once it had been fully ticked for well over a year, since a finished
plan in a living document invites re-reading it as outstanding work.

The fixes themselves are recorded in `changelog.md` under 1.0.3 – 1.4.0, which is the
durable record. Summary of what the phases covered:

1. Site-breaking fixes — fatal errors and missing templates.
2. Security — escaping, nonces, capability checks on every write path.
3. Broken functionality — features that silently did nothing.
4. Infrastructure and asset organisation — build pipeline, minified twins.
5. Missing templates — `404`, `search`, `archive` variants.
6. Code quality and WordPress standards — prefixing, i18n, sanitizer coverage.
7. Polish — screenshot, distribution readiness.

New work is tracked in `changelog.md` per release, not here. This file documents
**upgrade paths for people moving between versions** — if you are adding a plan, it
probably belongs in the changelog or an issue tracker instead.
