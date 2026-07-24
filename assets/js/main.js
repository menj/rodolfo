document.addEventListener('DOMContentLoaded', () => {
    // Translatable strings, localized from PHP (wp_localize_script). The
    // literals after || are last-resort fallbacks only.
    const L10N = window.rodolfoL10n || {};

    // ---- TOC: mobile collapse + scroll-spy (inc/toc.php) ----
    const toc = document.querySelector('.rodolfo-toc');
    if (toc) {
        const tocToggle = toc.querySelector('.rodolfo-toc-toggle');
        if (tocToggle) tocToggle.addEventListener('click', () => {
            // The button is display-inert on desktop (CSS never hides the list
            // there), so this only has a visible effect below md.
            const openNow = toc.classList.toggle('is-open');
            tocToggle.setAttribute('aria-expanded', openNow ? 'true' : 'false');
        });
        // Scroll-spy: highlight the entry for the section currently on screen.
        const tocLinks = new Map();
        toc.querySelectorAll('.rodolfo-toc-link').forEach((a) => {
            const id = decodeURIComponent((a.getAttribute('href') || '').slice(1));
            const target = id && document.getElementById(id);
            if (target) tocLinks.set(target, a);
        });
        if (tocLinks.size && typeof IntersectionObserver !== 'undefined') {
            let current = null;
            const spy = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        if (current) current.classList.remove('is-current');
                        current = tocLinks.get(entry.target);
                        if (current) current.classList.add('is-current');
                    }
                });
            }, { rootMargin: '0px 0px -70% 0px' });
            tocLinks.forEach((a, target) => spy.observe(target));
        }
    }

    // ---- Consent-gated embeds (inc/privacy-embeds.php) ----
    // Build the real iframe only on click; "always allow" lives in
    // localStorage (same posture as the dark-mode toggle) and auto-loads
    // that provider's gates on later visits.
    const EMBED_ALLOW_KEY = 'rodolfoEmbedAllow';
    const embedAllowed = () => {
        try { return JSON.parse(localStorage.getItem(EMBED_ALLOW_KEY)) || []; } catch (e) { return []; }
    };
    const loadEmbed = (gate) => {
        const iframe = document.createElement('iframe');
        iframe.src = gate.dataset.embedSrc;
        iframe.title = gate.dataset.embedTitle || gate.dataset.embedProvider;
        if (gate.dataset.embedAllow) iframe.setAttribute('allow', gate.dataset.embedAllow);
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'lazy');
        iframe.className = 'rodolfo-embed-frame';
        gate.replaceWith(iframe);
    };
    document.querySelectorAll('.rodolfo-embed-gate').forEach((gate) => {
        if (embedAllowed().includes(gate.dataset.embedProvider)) { loadEmbed(gate); return; }
        const btn = gate.querySelector('.rodolfo-embed-gate-btn');
        const always = gate.querySelector('.rodolfo-embed-gate-always');
        if (btn) btn.addEventListener('click', () => {
            if (always && always.checked) {
                const list = embedAllowed();
                if (!list.includes(gate.dataset.embedProvider)) {
                    list.push(gate.dataset.embedProvider);
                    try { localStorage.setItem(EMBED_ALLOW_KEY, JSON.stringify(list)); } catch (e) {}
                }
            }
            loadEmbed(gate);
        });
    });

    // ---- Turnstile lazy loader (inc/comment-guard.php) ----
    // The widget script is injected only when a reader focuses the comment
    // form, so page views without commenting never contact Cloudflare.
    const turnstileSlot = document.querySelector('#commentform .cf-turnstile');
    if (turnstileSlot) {
        let injected = false;
        document.getElementById('commentform').addEventListener('focusin', () => {
            if (injected) return;
            injected = true;
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }, { once: false });
    }
    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }

    // Mobile menu toggle — panel visibility is handled in style.css via
    // .is-open and the [aria-expanded] state on the button.
    const mobileToggle = document.getElementById('rodolfo-mobile-toggle');
    const mobileMenu = document.getElementById('rodolfo-mobile-menu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('is-open');
            mobileToggle.setAttribute('aria-expanded', String(open));
        });
    }

    // Share button — Web Share API with clipboard fallback.
    // Visual feedback is applied via CSS classes only, with no Lucide dependency.
    const shareBtn = document.getElementById('share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const { title, url } = shareBtn.dataset;
            let copied = false;

            try {
                if (navigator.share) {
                    await navigator.share({ title, url });
                } else {
                    await navigator.clipboard.writeText(url);
                    copied = true;
                }
            } catch {
                // User cancelled or both APIs unavailable — fail silently.
            }

            if (copied) {
                shareBtn.setAttribute('aria-label', L10N.linkCopied || 'Link copied!');
                shareBtn.classList.add('text-green-600', 'dark:text-green-400');
                setTimeout(() => {
                    shareBtn.setAttribute('aria-label', L10N.share || 'Share');
                    shareBtn.classList.remove('text-green-600', 'dark:text-green-400');
                }, 2000);
            }
        });
    }

    // Bookmark button — localStorage persistence per post ID.
    const bookmarkBtn = document.getElementById('bookmark-btn');
    if (bookmarkBtn) {
        const postId = bookmarkBtn.dataset.postId;
        const STORAGE_KEY = 'rodolfo_bookmarks';

        const getBookmarks = () => {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
            catch { return []; }
        };

        const applyBookmarkState = (saved) => {
            bookmarkBtn.setAttribute('aria-label', saved ? (L10N.removeBookmark || 'Remove bookmark') : (L10N.bookmark || 'Bookmark'));
            bookmarkBtn.setAttribute('aria-pressed', String(saved));
            bookmarkBtn.classList.toggle('text-blue-600', saved);
            bookmarkBtn.classList.toggle('dark:text-blue-400', saved);
        };

        // Restore state on page load.
        applyBookmarkState(getBookmarks().includes(postId));

        bookmarkBtn.addEventListener('click', () => {
            const bookmarks = getBookmarks();
            const idx = bookmarks.indexOf(postId);
            if (idx === -1) {
                bookmarks.push(postId);
            } else {
                bookmarks.splice(idx, 1);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(bookmarks));
            applyBookmarkState(idx === -1);
        });
    }

    // ⌘K / live search command palette.
    const palette = document.getElementById('rodolfo-search');
    if (palette) {
        const input = palette.querySelector('#rodolfo-search-input');
        const results = palette.querySelector('#rodolfo-search-results');
        const empty = palette.querySelector('.rodolfo-search-empty');
        const restUrl = palette.dataset.rest;
        let active = -1;
        let debounce;
        let lastFocus = null;

        const open = () => {
            lastFocus = document.activeElement;
            palette.hidden = false;
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => { palette.classList.add('is-open'); input.focus(); });
            showRecents();
        };
        const close = () => {
            palette.classList.remove('is-open');
            document.body.style.overflow = '';
            setTimeout(() => { palette.hidden = true; }, 150);
            input.value = '';
            results.innerHTML = '';
            empty.hidden = true;
            active = -1;
            // WCAG 2.4.3: return focus to where the user was before the dialog.
            if (lastFocus && typeof lastFocus.focus === 'function') { lastFocus.focus(); }
            lastFocus = null;
        };
        const isOpen = () => !palette.hidden;

        const render = (items) => {
            results.innerHTML = '';
            active = -1;
            empty.hidden = items.length > 0;
            items.forEach((it, i) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.className = 'rodolfo-search-item';
                const a = document.createElement('a');
                a.href = it.url;
                a.innerHTML = '<span class="rodolfo-search-item-title"></span>' +
                    (it.subtype ? '<span class="rodolfo-search-item-type"></span>' : '');
                a.querySelector('.rodolfo-search-item-title').textContent = it.title || '(untitled)';
                if (it.subtype) a.querySelector('.rodolfo-search-item-type').textContent = it.subtype;
                a.addEventListener('mouseenter', () => setActive(i));
                a.addEventListener('click', () => rememberSearch(input.value.trim()));
                li.appendChild(a);
                results.appendChild(li);
            });
        };

        const setActive = (i) => {
            const items = results.querySelectorAll('.rodolfo-search-item');
            items.forEach(el => el.classList.remove('is-active'));
            active = i;
            if (i >= 0 && items[i]) items[i].classList.add('is-active');
        };

        // Recent searches: the reader's own last five queries, local only.
        const RECENTS_KEY = 'rodolfoRecentSearches';
        const recents = () => {
            try { return JSON.parse(localStorage.getItem(RECENTS_KEY)) || []; } catch (e) { return []; }
        };
        const rememberSearch = (q) => {
            if (!q || q.length < 2) return;
            const list = [q].concat(recents().filter(x => x !== q)).slice(0, 5);
            try { localStorage.setItem(RECENTS_KEY, JSON.stringify(list)); } catch (e) {}
        };
        const showRecents = () => {
            const list = recents();
            results.innerHTML = '';
            active = -1;
            empty.hidden = true;
            list.forEach((q) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.className = 'rodolfo-search-item rodolfo-search-recent';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerHTML = '<span class="rodolfo-search-item-title"></span><span class="rodolfo-search-item-type"></span>';
                btn.querySelector('.rodolfo-search-item-title').textContent = q;
                btn.querySelector('.rodolfo-search-item-type').textContent = 'recent';
                btn.addEventListener('click', () => { input.value = q; input.focus(); search(q); });
                li.appendChild(btn);
                results.appendChild(li);
            });
        };

        const search = (q) => {
            if (!q || q.length < 2) { showRecents(); return; }
            // Content and categories in parallel — pages and posts come from
            // the core search endpoint; categories from its term type.
            Promise.all([
                fetch(`${restUrl}?search=${encodeURIComponent(q)}&per_page=6&_fields=title,url,subtype`).then(r => r.ok ? r.json() : []).catch(() => []),
                fetch(`${restUrl}?search=${encodeURIComponent(q)}&type=term&subtype=category&per_page=3&_fields=title,url,subtype`).then(r => r.ok ? r.json() : []).catch(() => [])
            ]).then(([content, terms]) => {
                render(content.concat(terms).slice(0, 8));
            });
        };

        input.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => search(input.value.trim()), 180);
        });

        palette.addEventListener('keydown', (e) => {
            const items = results.querySelectorAll('.rodolfo-search-item');
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(active + 1, items.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); const go = items[active].querySelector('a, button'); if (go) go.click(); }
            else if (e.key === 'Escape') { e.preventDefault(); close(); }
        });
        palette.querySelectorAll('[data-search-close]').forEach(el => el.addEventListener('click', close));

        // Global shortcuts: ⌘K / Ctrl-K anywhere; "/" when not typing in a field.
        document.addEventListener('keydown', (e) => {
            const typing = /^(input|textarea|select)$/i.test(e.target.tagName) || e.target.isContentEditable;
            if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) {
                e.preventDefault(); isOpen() ? close() : open();
            } else if (e.key === '/' && !typing && !isOpen()) {
                e.preventDefault(); open();
            }
        });
        // Clicking a header search field opens the palette instead of typing inline.
        document.querySelectorAll('.search-form input[name="s"]').forEach((el) => {
            el.addEventListener('focus', (e) => { e.preventDefault(); el.blur(); open(); });
            el.addEventListener('mousedown', (e) => { e.preventDefault(); open(); });
        });
    }
});
/* ------------------------------------------------------------------
 * Numbered navigation: press 1–9 to jump to the nth header nav link.
 * Opt-in via the rodolfo_keynav_enabled option, which renders
 * data-keynav indices onto the links; without them this does nothing.
 *
 * Guards: ignored while typing, when a modifier is held (so ⌘1 for
 * browser tabs still works), and when the search palette is open.
 * ------------------------------------------------------------------ */
document.addEventListener('keydown', (e) => {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    if (/^(input|textarea|select)$/i.test(e.target.tagName) || e.target.isContentEditable) return;
    const palette = document.getElementById('rodolfo-search-palette');
    if (palette && !palette.hidden && !palette.classList.contains('hidden')) return;
    if (!/^[1-9]$/.test(e.key)) return;

    const target = document.querySelector('[data-keynav="' + e.key + '"]');
    if (!target) return;
    e.preventDefault();

    const href = target.getAttribute('href') || '';
    if (href.startsWith('#')) {
        const el = document.querySelector(href);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.setAttribute('tabindex', '-1');
            el.focus({ preventScroll: true });
        }
    } else {
        window.location.href = href;
    }
});
