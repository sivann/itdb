<script>
var oTable;

$(document).ready(function() {
	oTable = $('#itemlisttbl').dataTable( {
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

    $('input.column_filter').keyup(function () {
		oTable.fnFilter( this.value, $(this).parents('tr').attr('data-column') ); 

    } );

	var thArray=[];
	$('.colhead').each(function(i){
		var txt=$(this).text();
		if (txt)
			thArray.push(txt);
	})

	$('#colfiltertbl td.col_filt_name').each(function( index ) {
		var colidx=$(this).parents('tr').attr('data-column');
		$(this).text(thArray[colidx])
		//console.log($(this).parents('tr').attr('data-column'));
	});

    $('#togglefilter').click(function() {
		$('#colfiltertbl').toggle();
	});
} );
</script>

<h1>
<?php te("Items");?> <a title='Old Interface' style='font-size:0.5em' href="?action=listitems2">2</a>
<a title='<?php te("Add new item");?>' href='<?php echo $scriptname;?>?action=edititem&amp;id=new'><img border=0 src='images/add.png'></a>
<button style='margin-left:15px;font-weight:normal' class='filterbtn' id='togglefilter' style='font-weight:normal;font-size:1em'><?php te("Filter")?></button> 
</h1>



<table id='colfiltertbl' style='display:none'>
<tr>
<td style='vertical-align:top'>
	<table>
		<?php
		for ($i1=0;$i1<=20;$i1+=2) {
		?>
		<tr id="filter_col_<?php echo $i1?>" data-column="<?php echo $i1?>">
			<td class='col_filt_name'>Name</td>
			<td align="center"><input type="text" class="column_filter"></td>
		</tr>
		<?php
		}
		?>
	</table>
</td>

<td style='vertical-align:top'>
	<table>
		<?php
		for ($i2=1;$i2<=20;$i2+=2) {
		?>
		<tr id="filter_col_<?php echo $i2?>" data-column="<?php echo $i2?>">
			<td class='col_filt_name'>Name</td>
			<td align="center"><input type="text" class="column_filter"></td>
		</tr>
		<?php
		}
		?>
	</table>
</td>

</tr>
</table>


<table id='itemlisttbl' class="display">
<thead>
	<tr>
	<th class='colhead'><?php te("ID");?></th>
	<th class='colhead'><?php te("Label");?></th>
	<th class='colhead'><?php te("Item Type");?></th>
	<th class='colhead'><?php te("Manufacturer");?></th>
	<th class='colhead'><?php te("Model");?></th>
	<th class='colhead'><?php te("DnsName");?></th>
	<th class='colhead'><?php te("S/N");?></th>
	<th class='colhead'><?php te("PurchaseDate");?></th>
	<th class='colhead'><?php te("Warr. Rem. days");?></th>
	<th class='colhead'><?php te("User");?></th>
	<th class='colhead'><?php te("Status");?></th>
	<th class='colhead'><?php te("Location");?></th>
	<th class='colhead'><?php te("Area");?></th>
	<th class='colhead'><?php te("Rack");?></th>
	<th class='colhead'><?php te("PurchPrice");?></th>
	<th class='colhead'><?php te("MACs");?></th>
	<th class='colhead'><?php te("IPv4");?></th>
	<th class='colhead'><?php te("IPv6");?></th>
	<th class='colhead'><?php te("RemAdmIP");?></th>
	<th class='colhead'><?php te("Tags");?></th>
	<th class='colhead'><?php te("Software");?></th>
	</tr>
</thead>
<tbody>
	<tr> <td colspan="21" class="dataTables_empty"><?php te("Loading data from server");?></td> </tr>
</tbody>
</table>

