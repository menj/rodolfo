/**
 * Click-to-Tweet block — editor script (inc/click-to-tweet.php registers it).
 *
 * Vanilla wp.blocks with createElement, deliberately buildless: the theme has
 * no JSX pipeline and a quote-with-two-fields block does not justify one.
 * Server-rendered on the front end (render_callback wraps the [bctt]
 * shortcode), so editor and reader output can never drift apart.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'rodolfo/click-to-tweet', {
		title: __( 'Click to Tweet', 'rodolfo' ),
		description: __( 'An accented pull-quote your readers can post to X in one click.', 'rodolfo' ),
		icon: 'twitter',
		category: 'text',
		attributes: {
			tweet: { type: 'string', default: '' },
			via: { type: 'string', default: '' },
			prompt: { type: 'string', default: '' }
		},
		example: {
			attributes: { tweet: __( 'A quotable line worth sharing.', 'rodolfo' ) }
		},
		edit: function ( props ) {
			var a = props.attributes;
			return el(
				element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Tweet settings', 'rodolfo' ) },
						el( components.TextControl, {
							label: __( 'Via handle (without @)', 'rodolfo' ),
							help: __( 'Leave empty to use the Theme Options handle.', 'rodolfo' ),
							value: a.via,
							onChange: function ( v ) { props.setAttributes( { via: v } ); }
						} ),
						el( components.TextControl, {
							label: __( 'Prompt label', 'rodolfo' ),
							help: __( 'The small call-to-action under the quote.', 'rodolfo' ),
							value: a.prompt,
							onChange: function ( v ) { props.setAttributes( { prompt: v } ); }
						} )
					)
				),
				el(
					'blockquote',
					blockEditor.useBlockProps( { className: 'rodolfo-bctt-editor' } ),
					el( blockEditor.RichText, {
						tagName: 'p',
						allowedFormats: [],
						placeholder: __( 'Write the quotable line…', 'rodolfo' ),
						value: a.tweet,
						onChange: function ( v ) { props.setAttributes( { tweet: v } ); }
					} )
				)
			);
		},
		// Dynamic block: the server renders through the [bctt] shortcode.
		save: function () {
			return null;
		}
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n ) );
