Bundled fonts
=============

Inter (OFL, see OFL.txt) ships with the theme:

  Inter-Variable.ttf          upright, weights 100–900 (variable)
  Inter-Italic-Variable.ttf   italic, weights 100–900 (variable)
  Inter-Bold.ttf              static bold — used by the generated social card

inc/fonts.php detects Inter-Variable.ttf, emits the @font-face declarations
with font-display:swap, and preloads the upright file. No remote request is
made (Google Fonts was removed in 1.5.0 for GDPR compliance). If these files
are removed the theme silently falls back to the system font stack.

Note: TTF variable fonts are shipped rather than woff2 — no build step, and
WordPress.org permits them. To shrink the download you can convert them to
woff2 later and update the src/format() in inc/fonts.php.
