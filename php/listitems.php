<script>
$(document).ready(function() {
	var oTable = $('#itemlisttbl').dataTable( {
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 18,
		"aLengthMenu": [[10,18, 25, 50, 100, -1], [10,18, 25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                //"sDom": '<"H"CTlpf>rt<"F"ip>',
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
/*

			"aButtons": [ {
			  "sExtends": "ajax",
			  "sButtonText": "Download CSV",
			  "fnClick": function () {
			    var iframe = document.createElement('iframe');
			    iframe.style.height = "0px";
			    iframe.style.width = "0px";
			    iframe.src = "/php/datatables_listitems_ajax_csv.php";
			    document.body.appendChild( iframe );
			  }
			  //"sAjaxUrl": "php/datatables_listitems_ajax_csv.php",
			} ]
*/
                },
		"aoColumnDefs": [ 
			{ "sWidth": "70px", "aTargets": [ 0 ] },
			{ "asSorting": [ "desc","asc" ], "aTargets": [ 0 ] },
			{ "sType": "title-numeric", "aTargets": [ 7 ] }
		],
		//"oColVis": { "buttonText": "+/-", },
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": "php/datatables_listitems_ajax.php",
		//"sScrollY": "550px", "bScrollCollapse": true,
		"sScrollX": "100%",
		"sScrollXInner": "180%",
		"bScrollCollapse": true,
	} );

	jQuery.fn.dataTableExt.oSort['title-numeric-asc']  = function(a,b) {
		var x = a.match(/title="*(-?[0-9]+)/)[1];
		var y = b.match(/title="*(-?[0-9]+)/)[1];
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ? -1 : ((x > y) ?  1 : 0));
	};

	jQuery.fn.dataTableExt.oSort['title-numeric-desc'] = function(a,b) {
		var x = a.match(/title="*(-?[0-9]+)/)[1];
		var y = b.match(/title="*(-?[0-9]+)/)[1];
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ?  1 : ((x > y) ? -1 : 0));
	};

/*
       	new FixedColumns( oTable, {
 		"iLeftColumns": 1,
		"iLeftWidth": 70
 	} );
*/

} );
</script>

<h1><?php te("Items");?> <a title='<?php te("Add new item");?>' href='<?php echo $scriptname;?>?action=edititem&amp;id=new'><img border=0 src='images/add.png'></a></h1>

<table id='itemlisttbl' class="display">
<thead>
<tr>
<th><?php te("ID");?></th>
<th><?php te("Label");?></th>
<th><?php te("Item Type");?></th>
<th><?php te("Manufacturer");?></th>
<th><?php te("Model");?></th>
<th><?php te("DnsName");?></th>
<th><?php te("S/N");?></th>
<th><?php te("PurchaseDate");?></th>
<th><?php te("Warr. Rem. days");?></th>
<th><?php te("User");?></th>
<th><?php te("Status");?></th>
<th><?php te("Location");?></th>
<th><?php te("Area");?></th>
<th><?php te("Rack");?></th>
<th><?php te("PurchPrice");?></th>
<th><?php te("MACs");?></th>
<th><?php te("IPv4");?></th>
<th><?php te("IPv6");?></th>
<th><?php te("RemAdmIP");?></th>
<th><?php te("Tags");?></th>
<th><?php te("Software");?></th>
</tr>
</thead>
<tbody>
  <tr> <td colspan="21" class="dataTables_empty"><?php te("Loading data from server");?></td> </tr>
</tbody>
</table>

