	$(document).ready(function() {

		$(".tags").tag({
			autoCompleteData: { source: "php/taglist_ajax.php"},
			tagTemplate: "<li>",
			editTagsSelector: ".edit-tags", //trigger edit mode
			doneEditingElement: "<a href='#'>done editing</a>",
			removeElement: "<a class='delete-tag' title='Delete this tag?' href='#'>&nbsp;</a>",
			//addElement: "<input type='submit' value='Add' class='addtag'>",
			saveTagCallback: function( tag ) { 
			  tag.valueElement.wrap("<a href='#'>"); 
			  $(".tags li").removeClass( "last" );
			  $(tag).addClass( "last" );
			  //var tags_string;
			  //$(".tags").each(function() { tags_string += this.newValue ? this.newValue + "," : this.oldValue + ","; });
			  //log( "SAVE: " + tag.newValue); // + ", tags: " + tags_string );
			  $("#result").html('<img src="images/ajaxload.gif">').load(ajaxtagscript, {addtag: tag.newValue});

			  return true; 
			},
			removeTagCallback: function( tag ) { 
			  //log( "REMOVE: " + tag.oldValue); // + ", tags: " + tags_string );
			  $("#result").html('<img src="images/ajaxload.gif">').load(ajaxtagscript, {removetag: tag.oldValue});
			  return true; 
			}
		});

	});
