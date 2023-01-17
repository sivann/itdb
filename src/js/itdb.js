
$("document").ready(function() {


  //prevent enter on inputs to submit form
  $("input[type=text]").keypress(function(e)
  {
      // if the key pressed is the enter key
      if (e.keyCode == 13)
      {
	return false;
      }
  });


    //used in "item types" for example. Association tables are handled by quicksearch.js
    $("table.brdr tr:even").addClass("even");
    //$('table.brdr >tbody>tr:nth-child(even)').addClass('even'); 

    $( "button" ).button();

});
