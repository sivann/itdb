/*!
 * Tag, the jQuery plugin to edit tag
 * by Georges Duverger
 * http://simplicityengineer.com/
 */

/* !
 * Structure of a tag:
 *
 * tag
 * tag.addElement
 * tag.valueElement
 * tag.noDelete
 * tag.inputField
 * tag.oldValue
 * tag.newValue
 * tag.removeLink
 */

(function( $ ){
	
	var KEY = { up: 38, down: 40, del: 46, tab: 9, enter: 13, escape: 27, comma: 188, page_up: 33, page_down: 34, back_space: 8, comma: 44 },
		
		// DON'T CHANGE THE DEFAULTS, OVERRIDE THEM WHEN YOU CALL TAG!
		DEFAULTS = {
			
			// The template used to create a new tag
			tagTemplate: "<li>",
			
			// The selector of "Edit tags"
			editTagsSelector: ".edit-tags",
			
			// Allows to delete tags
			noDelete: false,
			noDeleteClass: "no-delete",
			
			// Able/disable to add new tags
			noAdd: false,
			
			doneEditingElement: "<a href='#' class='done-editing'>Done editing</a>",
			removeElement: "<a href='#' class='remove-tag' title='Remove'>Remove</a>",
			addElement: "<a href='#' class='add-tag' title='Add'>Add</a>",
			
			addModeClass: "add-mode",
			editModeClass: "edit-mode",
			
			inputWidth: 100,
			inputPadding: 30,
			inputClass: "tag-input",
			inputMaxLength: 50,
			
			effectsDuration: "fast", // fast, slow, false
			
			separator: KEY.comma,
			autoCompleteData: false,
			
			removeTagCallback: function( tag ) { return true; },
			saveTagCallback: function( tag, selector, tags, settings ) { return true; }
		};
	
	$.fn.extend({
		tag: function( options ) {
			var settings = $.extend( {}, DEFAULTS, options ),
				selector = this,
				tags = new Array();
			
			// Binds "Edit tags"
			$(settings.editTagsSelector).click(function( event ) {
				if ( event.preventDefault ) { event.preventDefault(); } else { event.returnValue = false; }
				$(this).hide();
				editTags( selector, tags, settings );
			})
			
			return this;
		},
		
		addAsTag: function( tags, options ) {
			var tag, settings = $.extend( {}, DEFAULTS, options );
			return this.each(function() {
				tag = this;
				
				// Add the tag to the tags array
				tags.push( tag );
				
				// Change the deletable properties if overrided in the HTML
				tag.noDelete = $(tag).hasClass( settings.noDeleteClass ) ? true : settings.noDelete;
				
				tag.oldValue = $(tag).text();
				// TODO wrap instead?
				tag.valueElement = $(tag).children("a").length != 0 ? $(tag).children( "a:first" ) : $("<span>").text( tag.oldValue ).appendTo( $(tag).empty() );
				
				// Adds "Remove"
				if ( !tag.noDelete ) {
					tag.removeLink = $(settings.removeElement).appendTo( tag ).click(function( event ) {
						if ( event.preventDefault ) { event.preventDefault(); } else { event.returnValue = false; }
						$(tag).removeTag( tags, settings );
					});
					$(tag).addClass( settings.editModeClass ); // EDIT MODE
				}
			});
		},
		
		editTag: function( selector, tags, options ) {
			var settings = $.extend( {}, DEFAULTS, options ), tag;
			return this.each(function() {
				tag = this;
				
				// Makes sure it's a tag
				if( !this.valueElement ) { $(this).addAsTag( tags, settings ); }
				
				// Adds "Save"
				if ( !this.addElement ) {
					this.addElement = $(settings.addElement).appendTo( this ).hide().click(function( event ) {
						if ( event.preventDefault ) { event.preventDefault(); } else { event.returnValue = false; }
						$(tag).saveTag( selector, tags, settings );
						createNewTag( selector, tags, settings );
					});
				}
				
				var width = $(this.valueElement).text() ? $( this.valueElement ).width() + settings.inputPadding : settings.inputWidth;
				$(this.valueElement).hide();
				$(this).addClass( settings.addModeClass );
				
				if ( $(this.inputField).length != 0 ) {
					// The input element already exists
					$(this.inputField).width( width ).show().focus();
				} else {
					// Creates the input element
					var tag = this;
					tag.oldValue = $(tag.valueElement).text();
					tag.inputField = $("<input type='text'>").addClass( settings.inputClass ).val( tag.oldValue ).width( width ).attr( "maxlength", settings.inputMaxLength );
					
					// Auto-complete
					if( settings.autoCompleteData ) {
						$(tag.inputField).autocomplete( settings.autoCompleteData );
					}
					
					$(tag.inputField).keypress(function( e ) {
						var code = (e.keyCode ? e.keyCode : e.which);
						if ( code == KEY.enter || code == settings.separator ) { // Enter or Comma
							$(tag).saveTag( selector, tags, settings );
							createNewTag( selector, tags, settings );
							return false;
						}
						// Auto-resizes
						width = $(tag.valueElement).text( $(tag.inputField).val() ).width() + settings.inputPadding;
						if ( width > settings.inputWidth || width > $(tag.inputField).width() ) { $(tag.inputField).width( width ) };
					});
					$(tag).prepend( tag.inputField );
					tag.inputField.focus();
				}
				$(this.removeLink).hide();
				$(this).removeClass( settings.editModeClass ); // EDIT MODE
				$(this.addElement).show();
			});
		},
		
		removeTag: function( tags, options ) {
			var settings = $.extend( {}, DEFAULTS, options );
			return this.each(function() {
				if ( !this.oldValue ) {
					removeFromArray( tags, tags.indexOf( this ) );
					if ( settings.effectsDuration ) { $(this).hide( settings.effectsDuration, function() { $(this).remove(); } ); }
					else { $(this).remove(); }
				} else if ( settings.removeTagCallback( this ) ) {
					removeFromArray( tags, tags.indexOf( this ) );
					if ( settings.effectsDuration ) { $(this).hide( settings.effectsDuration, function() { $(this).remove(); } ); }
					else { $(this).remove(); }
				}
			});
		},
		
		saveTag: function( selector, tags, options ) {
			var settings = $.extend( {}, DEFAULTS, options );
			return this.each(function() {
				// this.newValue = $(this.inputField).val();
				
				// HILTON STARTED CHANGES HERE
				var noDuplicates = true;
				for (var i = 0; i < tags.length; i++) {
					if (tags[i].oldValue.toLowerCase() == $(this.inputField).val().toLowerCase()) {
						noDuplicates = false;
						break;
					}
				}
				if (noDuplicates) {
					this.newValue = $(this.inputField).val();
				}
				// END OF CHANGES BY HILTON
				
				if ( !this.newValue ) {
					$(this).removeTag( tags, settings );
				} else if ( this.oldValue == this.newValue ) {
					$(this.valueElement).show();
					$(this.inputField).hide();
					$(this.addElement).hide();
					$(this.removeLink).show();
					$(this).addClass( settings.editModeClass ); // EDIT MODE
				} else if ( settings.saveTagCallback( this, selector, tags, settings ) ) {
					$(this.valueElement).text( this.newValue ).show();
					$(this.inputField).hide();
					$(this.addElement).hide();
					$(this.removeLink).show();
					$(this).addClass( settings.editModeClass ); // EDIT MODE
					this.oldValue = this.newValue;
				} else {
					$(this).removeTag( tags, settings );
				}
				$(this).removeClass( settings.addModeClass );
			});
		}
	});
	
	editTags = function( selector, tags, options ) {
		var settings = $.extend( {}, DEFAULTS, options ),
			doneEditingElement;
			
		// Inserts, binds, and shows "Done editing"
		if ( !doneEditingElement ) {
			doneEditingElement = $(settings.doneEditingElement).insertAfter( $(settings.editTagsSelector) ).click(function( event ) {
				if ( event.preventDefault ) { event.preventDefault(); } else { event.returnValue = false; }
				$(this).hide();
				doneEditing( selector, tags, settings );
			});
		} else {
			doneEditingElement.show();
		}
		
		// Switches to edit mode
		// TODO don't rely on selector but tags instead
		$(selector).children().each(function() {
			if(this.valueElement) {
				$(this.removeLink).show();
				$(this).addClass( settings.editModeClass ); // EDIT MODE
			} else {
				$(this).addAsTag( tags, settings );
			}
		});
		
		if ( !settings.noAdd ) {
			// Creates a new tag
			createNewTag( selector, tags, settings );
		}
	}
	
	doneEditing = function( selector, tags, options ) {
		var settings = $.extend( {}, DEFAULTS, options );
		
		// Switches to view mode
		$(selector).children().each(function() {
			if ( this.inputField ) { $(this).saveTag( selector, tags, settings ); }
			$(this.removeLink).hide();
			$(this).removeClass( settings.editModeClass ); // EDIT MODE
		});
		
		// Restore the original "Edit tags" link
		$(settings.editTagsSelector).show();
	}
	
	createNewTag = function( selector, tags, options ) {
		var tag, settings = $.extend( {}, DEFAULTS, options );
		
		// Create a tag element
		tag = $(settings.tagTemplate).addAsTag( tags, settings );
		
		// Call the add user callback
		$(selector).append(tag);
		
		// Edit the tag
		// Done after the callback so the focus will work
		$(tag).editTag( selector, tags, settings );
		
		return tag;
	}
	
	// Array Remove - By John Resig (MIT Licensed)
	removeFromArray = function(array, from, to) {
		var rest = array.slice((to || from) + 1 || array.length);
		array.length = from < 0 ? array.length + from : from;
		return array.push.apply(array, rest);
	};
	
})( jQuery );

// To fix the fact that indexOf is 'undefined' in IE
// http://soledadpenades.com/2007/05/17/arrayindexof-in-internet-explorer/
if(!Array.indexOf){
	Array.prototype.indexOf = function(obj){
		for(var i=0; i<this.length; i++){
			if(this[i]==obj){
				return i;
			}
		}
		return -1;
	}
}
