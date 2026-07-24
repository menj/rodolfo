<?php
/**
 * In-dashboard documentation viewer.
 *
 * Renders the theme's own Markdown files (docs/*.md) as HTML inside
 * Theme Options → Documentation. The Markdown files remain the single source
 * of truth: nothing is duplicated, nothing is hardcoded, and editing a .md
 * file changes what the tab shows on the very next page load.
 *
 * Markdown parsing uses the best available engine, in this order:
 *   1. A `Parsedown` class already loaded by a plugin or another theme.
 *   2. The copy bundled at `vendor/parsedown/Parsedown.php` (MIT — see the
 *      LICENSE.txt beside it).
 *   3. A small built-in renderer covering the subset these docs use.
 *
 * Preferring an already-loaded copy matters: Parsedown declares a global,
 * unguarded `class Parsedown`, so loading a second copy is a fatal error. The
 * class_exists() check in rodolfo_docs_parser() is what makes bundling safe.
 * Tier 3 keeps the tab working if the vendor directory is stripped from a
 * distribution — and, more importantly, if Parsedown itself fails to load.
 * Parsedown 1.8.0 is unmaintained (2019; CI stops at PHP 8.1), so a future PHP
 * release breaking it is the realistic failure mode; the catch in
 * rodolfo_docs_parser() falls through here rather than fatalling wp-admin.
 * DO NOT delete this as dead code: it does not execute in a correctly packaged
 * release, which is exactly the point. See docs/ssot.md for the full rationale.
 *
 * Security posture: the sources are theme-owned files, not user input, but the
 * output is defended in depth regardless. Parsedown runs with safe mode and
 * markup escaping enabled; the built-in renderer escapes every text node BEFORE
 * emitting tags. Either way the final string passes through
 * rodolfo_docs_kses() with an explicit allowlist, so raw HTML in a Markdown
 * source is inert — it is escaped as literal text.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The documentation set, in tab order. Keyed by slug; value is the label and
 * the file, resolved relative to the theme directory.
 *
 * Filterable so a child theme can add or remove documents without touching
 * this file.
 */
function rodolfo_docs_files() {
    return apply_filters( 'rodolfo_docs_files', array(
        'readme'    => array(
            'label' => __( 'Overview', 'rodolfo' ),
            'file'  => 'docs/readme.md',
        ),
        'upgrading' => array(
            'label' => __( 'Upgrading', 'rodolfo' ),
            'file'  => 'docs/upgrading.md',
        ),
        'changelog' => array(
            'label' => __( 'Changelog', 'rodolfo' ),
            'file'  => 'docs/changelog.md',
        ),
        'ssot'      => array(
            'label' => __( 'Reference', 'rodolfo' ),
            'file'  => 'docs/ssot.md',
        ),
    ) );
}

/**
 * Resolve a doc slug to an absolute path, refusing anything that escapes the
 * theme directory. Returns '' when the slug is unknown or the file is absent.
 */
function rodolfo_docs_path( $slug ) {
    $docs = rodolfo_docs_files();
    if ( ! isset( $docs[ $slug ] ) ) {
        return '';
    }
    $base = wp_normalize_path( trailingslashit( get_template_directory() ) );
    $path = wp_normalize_path( $base . ltrim( $docs[ $slug ]['file'], '/' ) );

    // Containment check: the resolved path must sit under the theme directory.
    if ( 0 !== strpos( $path, $base ) ) {
        return '';
    }
    return file_exists( $path ) ? $path : '';
}

/**
 * Resolve a Markdown parser, preferring the best available option.
 *
 * Order:
 *   1. A Parsedown already loaded by a plugin or another theme — we never
 *      redeclare the class, so we can't cause a fatal collision.
 *   2. The copy bundled at vendor/parsedown/Parsedown.php.
 *   3. null — the caller falls back to the built-in renderer below.
 *
 * Parsedown lives in the global namespace with no include guard of its own,
 * which is a well-known WordPress vendoring hazard: if two copies are loaded,
 * the second `class Parsedown` declaration is a fatal error. The class_exists()
 * check below is what prevents that, so do not remove it.
 *
 * Safe mode + markup escaping are both enabled: raw HTML in a source file is
 * escaped rather than emitted, and dangerous URL schemes are neutralised. The
 * result still passes through wp_kses() afterwards regardless.
 */
function rodolfo_docs_parser() {
    static $parser = false; // false = not yet resolved, null = unavailable.

    if ( false !== $parser ) {
        return $parser;
    }

    if ( ! class_exists( 'Parsedown' ) ) {
        $bundled = get_template_directory() . '/vendor/parsedown/Parsedown.php';
        if ( is_readable( $bundled ) ) {
            require_once $bundled;
        }
    }

    if ( ! class_exists( 'Parsedown' ) ) {
        $parser = null;
        return $parser;
    }

    try {
        $instance = new Parsedown();
        // Available since 1.6/1.7. Guard so an older bundled copy can't fatal.
        if ( method_exists( $instance, 'setSafeMode' ) ) {
            $instance->setSafeMode( true );
        }
        if ( method_exists( $instance, 'setMarkupEscaped' ) ) {
            $instance->setMarkupEscaped( true );
        }
        if ( method_exists( $instance, 'setBreaksEnabled' ) ) {
            $instance->setBreaksEnabled( false );
        }
        $parser = $instance;
    } catch ( Exception $e ) {
        $parser = null;
    } catch ( Error $e ) {
        $parser = null;
    }

    return $parser;
}

/**
 * Read and render one document. Result is cached in a transient keyed by the
 * file's modification time, so an edited .md file invalidates its own cache
 * automatically — the tab always reflects the current file.
 */
function rodolfo_docs_render( $slug ) {
    $path = rodolfo_docs_path( $slug );
    if ( '' === $path ) {
        return '<p>' . esc_html__( 'This document is not available in the installed theme.', 'rodolfo' ) . '</p>';
    }

    $parser  = rodolfo_docs_parser();
    $engine  = $parser ? 'parsedown' : 'builtin';
    $key     = 'rodolfo_docs_' . md5( $slug . '|' . filemtime( $path ) . '|' . RODOLFO_VERSION . '|' . $engine );
    $cached  = get_transient( $key );
    if ( is_string( $cached ) && '' !== $cached ) {
        return $cached;
    }

    $markdown = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme file, not remote.
    if ( false === $markdown ) {
        return '<p>' . esc_html__( 'This document could not be read.', 'rodolfo' ) . '</p>';
    }

    if ( $parser ) {
        $html = $parser->text( $markdown );

        // Parsedown emits h1 for '#'; shift headings down one level so the
        // panel's own heading stays the page's h2, matching the fallback.
        $html = preg_replace( '/<(\/?)h([1-5])\b/', '<$1h$2X', $html );
        $html = preg_replace_callback( '/<(\/?)h([1-5])X/', function ( $m ) {
            return '<' . $m[1] . 'h' . min( 6, (int) $m[2] + 1 );
        }, $html );

        // Parsedown 1.8 has no task-list support, so GitHub-style checkboxes
        // would otherwise render as literal "[x]" / "[ ]" text. Convert them
        // here rather than pre-processing the Markdown, so Parsedown stays the
        // single authority on how the list itself is parsed.
        $html = preg_replace(
            '/<li>\s*\[x\]\s*/i',
            '<li class="rodolfo-docs-task"><span class="rodolfo-docs-check is-done" aria-hidden="true">&#10003;</span> ',
            $html
        );
        $html = preg_replace(
            '/<li>\s*\[ \]\s*/',
            '<li class="rodolfo-docs-task"><span class="rodolfo-docs-check" aria-hidden="true">&#9633;</span> ',
            $html
        );

        $html = rodolfo_docs_kses( $html );
    } else {
        $html = rodolfo_docs_markdown_to_html( $markdown );
    }

    set_transient( $key, $html, DAY_IN_SECONDS );

    return $html;
}

/**
 * Minimal fallback renderer.
 *
 * Parsedown handles every document in normal operation; this exists only for
 * the case where the bundled copy has been stripped from a distribution and no
 * other Parsedown is loaded. It is deliberately not a second full Markdown
 * implementation — maintaining one in parallel with Parsedown would mean two
 * sets of edge cases to keep in step. It covers just enough for the docs to
 * remain readable: headings, lists, fenced/inline code, blockquotes, rules,
 * links, bold and italic. Tables and task lists degrade to plain lines rather
 * than being reimplemented.
 *
 * Every text node is escaped BEFORE any tag is emitted, so HTML in a source
 * file becomes literal text; the result then passes through
 * rodolfo_docs_kses() like Parsedown's output does.
 */
function rodolfo_docs_fallback_inline( $text ) {
    $text = esc_html( $text );

    // Code spans first — their contents must not be processed further.
    $codes = array();
    $text  = preg_replace_callback( '/`([^`]+)`/', function ( $m ) use ( &$codes ) {
        $codes[] = '<code>' . $m[1] . '</code>';
        return "\x00C" . ( count( $codes ) - 1 ) . "\x00";
    }, $text );

    $text = preg_replace_callback( '/\[([^\]]+)\]\(([^)\s]+)\)/', function ( $m ) {
        $url = html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' );
        if ( 0 === strpos( $url, '#' ) ) {
            return $m[1];
        }
        $url = esc_url( $url, array( 'http', 'https', 'mailto' ) );
        return '' === $url ? $m[1] : '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
    }, $text );

    $text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );
    $text = preg_replace( '/(?<![\w*])\*([^*\n]+)\*(?![\w*])/', '<em>$1</em>', $text );

    foreach ( $codes as $i => $code ) {
        $text = str_replace( "\x00C{$i}\x00", $code, $text );
    }

    return $text;
}

/**
 * Block-level fallback. See rodolfo_docs_fallback_inline() for scope.
 */
function rodolfo_docs_markdown_to_html( $markdown ) {
    $lines = preg_split( '/\R/', (string) $markdown );
    $out   = array();
    $i     = 0;
    $count = count( $lines );

    while ( $i < $count ) {
        $line = $lines[ $i ];

        // Fenced code block.
        if ( preg_match( '/^```/', $line ) ) {
            $buf = array();
            $i++;
            while ( $i < $count && ! preg_match( '/^```/', $lines[ $i ] ) ) {
                $buf[] = $lines[ $i ];
                $i++;
            }
            $i++;
            $out[] = '<pre><code>' . esc_html( implode( "\n", $buf ) ) . '</code></pre>';
            continue;
        }

        // Horizontal rule.
        if ( preg_match( '/^\s*(-{3,}|\*{3,})\s*$/', $line ) ) {
            $out[] = '<hr />';
            $i++;
            continue;
        }

        // ATX heading, shifted down one level to match the Parsedown path.
        if ( preg_match( '/^(#{1,6})\s+(.*)$/', $line, $m ) ) {
            $level = min( 6, strlen( $m[1] ) + 1 );
            $out[] = '<h' . $level . '>' . rodolfo_docs_fallback_inline( trim( $m[2] ) ) . '</h' . $level . '>';
            $i++;
            continue;
        }

        // Blockquote.
        if ( preg_match( '/^>\s?(.*)$/', $line, $m ) ) {
            $buf = array( $m[1] );
            $i++;
            while ( $i < $count && preg_match( '/^>\s?(.*)$/', $lines[ $i ], $m2 ) ) {
                $buf[] = $m2[1];
                $i++;
            }
            $out[] = '<blockquote><p>' . rodolfo_docs_fallback_inline( trim( implode( ' ', $buf ) ) ) . '</p></blockquote>';
            continue;
        }

        // Lists. Task-list markers are stripped rather than rendered as boxes.
        if ( preg_match( '/^\s*([-*]|\d+\.)\s+/', $line ) ) {
            $ordered = (bool) preg_match( '/^\s*\d+\./', $line );
            $items   = array();
            while ( $i < $count && preg_match( '/^\s*(?:[-*]|\d+\.)\s+(.*)$/', $lines[ $i ], $m ) ) {
                $item    = preg_replace( '/^\[[ xX]\]\s*/', '', $m[1] );
                $items[] = '<li>' . rodolfo_docs_fallback_inline( $item ) . '</li>';
                $i++;
            }
            $tag   = $ordered ? 'ol' : 'ul';
            $out[] = '<' . $tag . '>' . implode( '', $items ) . '</' . $tag . '>';
            continue;
        }

        // Blank line.
        if ( '' === trim( $line ) ) {
            $i++;
            continue;
        }

        // Paragraph.
        $buf = array();
        while ( $i < $count && '' !== trim( $lines[ $i ] )
            && ! preg_match( '/^(#{1,6}\s|```|>|\s*(?:[-*]|\d+\.)\s)/', $lines[ $i ] )
            && ! preg_match( '/^\s*(-{3,}|\*{3,})\s*$/', $lines[ $i ] ) ) {
            $buf[] = $lines[ $i ];
            $i++;
        }
        if ( $buf ) {
            $out[] = '<p>' . rodolfo_docs_fallback_inline( trim( implode( ' ', $buf ) ) ) . '</p>';
        }
    }

    return rodolfo_docs_kses( implode( "\n", $out ) );
}

/**
 * The single output allowlist, applied to whichever engine rendered the HTML.
 *
 * Deliberately narrow: no script/style/iframe/form, no event handlers, no
 * inline styles. Parsedown runs in safe mode and the built-in renderer escapes
 * text nodes before emitting tags, so this is the third and final layer.
 */
function rodolfo_docs_kses( $html ) {
    return wp_kses( $html, array(
        'h1'         => array( 'id' => array() ),
        'h2'         => array( 'id' => array() ),
        'h3'         => array( 'id' => array() ),
        'h4'         => array( 'id' => array() ),
        'h5'         => array( 'id' => array() ),
        'h6'         => array( 'id' => array() ),
        'p'          => array(), 'br' => array(), 'hr' => array(),
        'strong'     => array(), 'b' => array(),
        'em'         => array(), 'i' => array(),
        'del'        => array(), 's' => array(), 'strike' => array(),
        'code'       => array( 'class' => array() ),
        'pre'        => array( 'class' => array() ),
        'ul'         => array(), 'ol' => array( 'start' => array() ),
        'li'         => array( 'class' => array() ),
        'span'       => array( 'class' => array(), 'aria-hidden' => array() ),
        'blockquote' => array( 'cite' => array() ),
        'table'      => array( 'class' => array() ),
        'thead'      => array(), 'tbody' => array(), 'tr' => array(),
        'th'         => array( 'style' => array(), 'align' => array(), 'colspan' => array() ),
        'td'         => array( 'style' => array(), 'align' => array(), 'colspan' => array() ),
        'a'          => array( 'href' => array(), 'title' => array(), 'target' => array(), 'rel' => array() ),
        'img'        => array( 'src' => array(), 'alt' => array(), 'title' => array(), 'width' => array(), 'height' => array() ),
    ) );
}
