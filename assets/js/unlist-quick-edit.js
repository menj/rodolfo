/**
 * Unlist quick-edit prefill (inc/unlist.php).
 *
 * WordPress clones the quick-edit row with every checkbox unchecked; the
 * post's actual state lives in the list-table column's data attribute. Wrap
 * core's inline editor so opening quick edit mirrors reality — without this,
 * saving quick edit would silently re-list every unlisted post it touched.
 */
( function () {
	'use strict';
	if ( typeof window.inlineEditPost === 'undefined' ) {
		return;
	}
	var coreEdit = window.inlineEditPost.edit;
	window.inlineEditPost.edit = function ( post ) {
		coreEdit.apply( this, arguments );
		var id = typeof post === 'object' ? parseInt( this.getId( post ), 10 ) : parseInt( post, 10 );
		if ( ! id ) {
			return;
		}
		var row = document.getElementById( 'post-' + id );
		var editRow = document.getElementById( 'edit-' + id );
		if ( ! row || ! editRow ) {
			return;
		}
		var state = row.querySelector( '.rodolfo-unlist-state' );
		var box = editRow.querySelector( 'input[name="rodolfo_unlisted"]' );
		if ( state && box ) {
			box.checked = state.getAttribute( 'data-unlisted' ) === '1';
		}
	};
}() );
