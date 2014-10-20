<?php 
//error_reporting(E_ALL); 
/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

require("../init.php");
require_once('PDF_Label.php');


set_time_limit(15);


//create the list of previously selected ids to get info from db
$ids="";
for ($i=0;$i<count($selitems);$i++)  {
  $ids.="'".$selitems[$i]."'";
  if ($i<count($selitems)-1) $ids.=", ";
}




//$sql="SELECT items.id,model,sn,sn3,itemtypeid,dnsname,ipv4,ipv6,label, agents.title as agtitle FROM items,agents ".
//     " WHERE agents.id=items.manufacturerid AND items.id in ($ids) order by itemtypeid, agtitle, model,sn,sn2,sn3";
$sql="SELECT items.id,model,sn,sn3,itemtypeid,dnsname,ipv4,ipv6,label, agents.title as agtitle FROM items,agents ".
     " WHERE agents.id=items.manufacturerid AND items.id in ($ids) order by items.id";
$sth=db_execute($dbh,$sql);
$idx=0;



$pdf=new PDF_Label(array(
  'paper-size'=>"$labelpapersize", 
  'metric'=>'mm',
  'marginLeft'=>$lmargin,
  'marginTop'=>$tmargin,
  'NX'=>$cols,
  'NY'=>$rows,
  'SpaceX'=>($hpitch-$lwidth),
  'SpaceY'=>($vpitch-$lheight),
  'width'=>$lwidth,
  'height'=>$lheight,
  'font-size'=>$fontsize));

$pdf->AddPage();
$pdf->SetAuthor('ITDB Asset Management');
$pdf->SetTitle('Items');

$pdf->setFontSubsetting(true);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

/* skip specified labels, to avoid printing on missing label positions on reused papers*/
for ($skipno=0;$skipno<$labelskip;$skipno++) {
  $pdf->Add_Label("","",0,255,"",0,0,6,6);
}

$pages=0;

for ($row=1;$row<=$rows;$row++) {
  if ($pages>30) break;
  for ($col=1;$col<=$cols;$col++) {

    $r=$sth->fetch(PDO::FETCH_ASSOC);
    if (!$r) break;

    $idesc=$itypes[$r['itemtypeid']]['typedesc'];
    $id=sprintf("%04d",$r['id']);
    $dnsname=$r['dnsname'];
    $ipv4=$r['ipv4'];
    $ipv4=mb_substr($ipv4,0,15);
    $ipv6=$r['ipv6'];
    $agtitle=$r['agtitle'];
    $model=$r['model'];
    $label=$r['label'];

    $desc="$agtitle/$model";
    $desc=mb_substr($desc,0,37);
    $desc=trim($desc);
    $sn=strlen($r['sn'])>0?$r['sn']:$r['sn3'];

    $labeltext="";
    $labeltext.=sprintf("ID:$id\n");
    if (strlen($label)) $labeltext.=sprintf("LBL:$label\n");

    if (strlen($sn)) $labeltext.=sprintf("SN:$sn\n");

    if (strlen($desc)) { $labeltext.=$desc."\n"; }

    if (strlen($ipv4)) { $labeltext.="IPv4:$ipv4\n"; }
    if (strlen($ipv6)) { $labeltext.="IPv6:$ipv6\n"; }

    if (strlen($dnsname)) { 
       $labeltext.="HName:$dnsname\n";
    }
    $labeltext=rtrim($labeltext);

    if (!$wantheadertext)
      $headertext="";

    if (!$wantheaderimage) 
      $image="";

    if ($wantbarcode) {
      $barcode=$qrtext.$id;
      //$barcode = mb_strtoupper($barcode, 'UTF-8');
    }
    else {
      $barcode="";
	}

    $headertext=str_replace('_NL_',"\n",$headertext);

    $nbw=0.30; //code39 narrow bar width (mm). 15+1 narrow bars/character (+1=spacing)
    $barcodewidth=((strlen($barcode)+3)*(15+2)+20)*$nbw;
    //code39:bh:(mm) barcode height must allow for 15 degrees of scanning ideally
    //code39:includes barcode text font width (about 3mm)
    $bh=max(0.15*$barcodewidth+3,16); //at least 15% of length

	if ($wantnotext) {
		$headertext='';
		$labeltext='';
	}
    

    $pdf->Add_Label($headertext,$labeltext,$padding,$border,
                    $image,$imagewidth,$imageheight,
                    $headerfontsize,$fontsize,$idfontsize,
		    $barcode, $nbw,$bh,$barcodesize,$wantraligntext );


    // for code39 barcodes: check if barcode fits inside label, allowing space for its quiet zone
    //2 narrow spacings if nbw>0.29, + 1 char checksum +2 chars start/stop (*) +10 bars quiet zone on each side
/*
    if ($barcodewidth>=$lwidth) {
      $pdf->Ln();
      $pdf->Cell(0,20,"Required barcode width ($barcodewidth mm) too wide for label width ($lwidth mm)",1,1,'C');
      break;

    }
*/

  }

  if (($row==$rows)) {
    $row=0;
    $col=0;
    $pages++;
    //$pdf->AddPage('P','A4');
  }


}

$dstr=date('Y-m-d_his');
$pdf->Output("labels-$dstr.pdf",'D');

?>



</body>
</html>
