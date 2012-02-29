<h1><?php te("Data Browser");?></h1>
<script type="text/javascript">

</script>

<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}
?>


<script>
$(function () {
	$("#tree1").jstree({ 
		"themes" : {
			"theme" : "apple", //apple, classic, default
			"dots" : true,
			"icons" : true
		},
		"core" : {
		        "animation" : 0
		},


		"html_data" : {
			"ajax" : {
				"url" : "php/browse_queries.php",
				"data" : function (n) { 
					return { id : n.attr ? n.attr("id") : 0 }; 
				}
			}
		},

	
		"plugins" : [ "themes", "html_data" ]

	});
});


</script>

<div id="tree1" class="browsetree" style='text-align:left'>
</div>



</body>
</html>
