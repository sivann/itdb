<?php
////////////////////////////////////////////////////////////////////////////////////////////////
// PDF_Label 
//
// Class to print labels in Avery or custom formats
//
// Copyright (C) 2003 Laurent PASSEBECQ (LPA)
// Based on code by Steve Dillon: steved@mad.scientist.com
// several additions, rewritten AddLabel function by Spiros Ioannou, 2009-2010 sivann at gmail, itdb project
//
//---------------------------------------------------------------------------------------------
// VERSIONS:
// 1.0: Initial release
// 1.1: + Added unit in the constructor
//      + Now Positions start at (1,1).. then the first label at top-left of a page is (1,1)
//      + Added in the description of a label:
//           font-size : defaut char size (can be changed by calling Set_Char_Size(xx);
//           paper-size: Size of the paper for this sheet (thanx to Al Canton)
//           metric    : type of unit used in this description
//                       You can define your label properties in inches by setting metric to
//                       'in' and print in millimiters by setting unit to 'mm' in constructor
//        Added some formats:
//           5160, 5161, 5162, 5163, 5164: thanx to Al Canton: acanton@adams-blake.com
//           8600                        : thanx to Kunal Walia: kunal@u.washington.edu
//      + Added 3mm to the position of labels to avoid errors 
// 1.2: = Bug of positioning
//      = Set_Font_Size modified -> Now, just modify the size of the font
// 1.3: + Labels are now printed horizontally
//      = 'in' as document unit didn't work
////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
**/


require_once('../tcpdf/config/lang/eng.php');
require_once('../tcpdf/tcpdf.php');

//class PDF_Label extends PDF_Label {
class PDF_Label extends TCPDF{

	// Private properties
	var $_Margin_Left;			// Left margin of labels
	var $_Margin_Top;			// Top margin of labels
	var $_X_Space;				// Horizontal space between 2 labels
	var $_Y_Space;				// Vertical space between 2 labels
	var $_X_Number;				// Number of labels horizontally
	var $_Y_Number;				// Number of labels vertically
	var $_Width;				// Width of label
	var $_Height;				// Height of label
	var $_Line_Height;			// Line height
	var $_Padding;				// Padding
	var $_Metric_Doc;			// Type of metric for the document
	var $_COUNTX;				// Current x position
	var $_COUNTY;				// Current y position

	// List of label formats
	var $_Avery_Labels = array(
		'5160' => array('paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>1.762,	'marginTop'=>10.7,		'NX'=>3,	'NY'=>10,	'SpaceX'=>3.175,	'SpaceY'=>0,	'width'=>66.675,	'height'=>25.4,		'font-size'=>8),
		'5161' => array('paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>0.967,	'marginTop'=>10.7,		'NX'=>2,	'NY'=>10,	'SpaceX'=>3.967,	'SpaceY'=>0,	'width'=>101.6,		'height'=>25.4,		'font-size'=>8),
		'5162' => array('paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>0.97,		'marginTop'=>20.224,	'NX'=>2,	'NY'=>7,	'SpaceX'=>4.762,	'SpaceY'=>0,	'width'=>100.807,	'height'=>35.72,	'font-size'=>8),
		'5163' => array('paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>1.762,	'marginTop'=>10.7, 		'NX'=>2,	'NY'=>5,	'SpaceX'=>3.175,	'SpaceY'=>0,	'width'=>101.6,		'height'=>50.8,		'font-size'=>8),
		'5164' => array('paper-size'=>'letter',	'metric'=>'in',	'marginLeft'=>0.148,	'marginTop'=>0.5, 		'NX'=>2,	'NY'=>3,	'SpaceX'=>0.2031,	'SpaceY'=>0,	'width'=>4.0,		'height'=>3.33,		'font-size'=>12),
		'8600' => array('paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>7.1, 		'marginTop'=>19, 		'NX'=>3, 	'NY'=>10, 	'SpaceX'=>9.5, 		'SpaceY'=>3.1, 	'width'=>66.6, 		'height'=>25.4,		'font-size'=>8),
		'L7163'=> array('paper-size'=>'A4',		'metric'=>'mm',	'marginLeft'=>5,		'marginTop'=>15, 		'NX'=>2,	'NY'=>7,	'SpaceX'=>25,		'SpaceY'=>0,	'width'=>99.1,		'height'=>38.1,		'font-size'=>9)
	);

	// Constructor
	function PDF_Label($format, $unit='mm', $posX=1, $posY=1) {
		if (is_array($format)) {
			// Custom format
			$Tformat = $format;
		} else {
			// Built-in format
			if (!isset($this->_Avery_Labels[$format]))
				$this->Error('Unknown label format: '.$format);
			$Tformat = $this->_Avery_Labels[$format];
		}

		//parent::TCPDF('P', $unit, $Tformat['paper-size']);

		//($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false)
		//parent::TCPDF('P', $unit, $format, true, 'UTF-8', false);
		 parent::__construct ('P', $unit, $format, true, 'UTF-8', false);

		$this->_Metric_Doc = $unit;
		$this->_Set_Format($Tformat);
		//$this->AddFont('BookAntiqua','','bkant.php');
		//$this->AddFont('dejavusans','','tahoma.php');
		//$this->AddFont('dejavusans','B','tahomabd.php');

		$this->SetFont('freesans');
		//$this->SetFont('dejavusans','B');

		$this->SetMargins(0,0); 
		$this->SetAutoPageBreak(false); 
		$this->_COUNTX = $posX-2;
		$this->_COUNTY = $posY-1;
	}

	function _Set_Format($format) {
		$this->_Margin_Left	= $this->_Convert_Metric($format['marginLeft'], $format['metric']);
		$this->_Margin_Top	= $this->_Convert_Metric($format['marginTop'], $format['metric']);
		$this->_X_Space 	= $this->_Convert_Metric($format['SpaceX'], $format['metric']);
		$this->_Y_Space 	= $this->_Convert_Metric($format['SpaceY'], $format['metric']);
		$this->_X_Number 	= $format['NX'];
		$this->_Y_Number 	= $format['NY'];
		$this->_Width 		= $this->_Convert_Metric($format['width'], $format['metric']);
		$this->_Height	 	= $this->_Convert_Metric($format['height'], $format['metric']);
		$this->Set_Font_Size($format['font-size']);
		$this->_Padding		= $this->_Convert_Metric(3, 'mm');
	}

	// convert units (in to mm, mm to in)
	// $src must be 'in' or 'mm'
	function _Convert_Metric($value, $src) {
		$dest = $this->_Metric_Doc;
		if ($src != $dest) {
			$a['in'] = 39.37008;
			$a['mm'] = 1000;
			return $value * $a[$dest] / $a[$src];
		} else {
			return $value;
		}
	}

	// Give the line height for a given font size
	function _Get_Height_Chars($pt) {
		$a = array(6=>2, 7=>2.5, 8=>3, 9=>4, 10=>5, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
		if (!isset($a[$pt]))
			$this->Error('Invalid font size: ('.$pt.")");
		return $this->_Convert_Metric($a[$pt], 'mm');
	}

	// Sets the character size
	// This changes the line height too
	function Set_Font_Size($pt) {
		$this->_Line_Height = $this->_Get_Height_Chars($pt);
		$this->SetFontSize($pt);
	}

	// Print a label
	// sivann
	function Add_Label($text_head, $text, $padding=3, $bordercolor=230, $img="",$imwidth=0,$imheight=0,
                           $headerfontsize=6,$fontsize=6,$idfontsize=7,$barcode="",$bar_w="0.4",$bar_h="20",$barcodesize=20,$raligntext=0) {
		$this->_COUNTX++;
		if ($this->_COUNTX == $this->_X_Number) {
			// Row full, we start a new one
			$this->_COUNTX=0;
			$this->_COUNTY++;
			if ($this->_COUNTY == $this->_Y_Number) {
				// End of page reached, we start a new one
				$this->_COUNTY=0;
				$this->AddPage();
			}
		}

		//$_PosX = $this->_Margin_Left + $this->_COUNTX*($this->_Width+$this->_X_Space) + $this->_Padding;
		//$_PosY = $this->_Margin_Top + $this->_COUNTY*($this->_Height+$this->_Y_Space) + $this->_Padding;

		$_PosX = $this->_Margin_Left + $this->_COUNTX*($this->_Width+$this->_X_Space) ;
		$_PosY = $this->_Margin_Top + $this->_COUNTY*($this->_Height+$this->_Y_Space) ;

		//$this->SetXY($_PosX, $_PosY);

		//sivann:
                if (strlen($img)) {
		  $this->Image($img, $_PosX+$padding, $_PosY+$padding, $imwidth,0);
		  $imwidth+=1; //dont glue it with text
		  $imheight+=1;
		}

		//draw text
		//header
		$this->SetTextColor(0,70,100); 
		$this->Set_Font_Size($headerfontsize);
		$this->SetXY($_PosX+$imwidth+$padding, $_PosY+$padding);
		//$this->MultiCell($this->_Width-$imwidth-(2*$padding), $this->_Line_Height, $text_head);
		$this->MultiCell($this->_Width-$imwidth-(2*$padding), $this->_Line_Height, $text_head,0,'L');


		if (strstr($text,"ID:")) {
		  $txtid=$text;
		  $txtid=str_replace("\n","",$txtid);
		  $txtid=preg_replace('/.*ID:([0-9]+).*/','ID:\1 ',$txtid);
		  $text=preg_replace('/ID:[0-9]+\n/','',$text);
		  $this->SetFont('freesans','B');
		  $this->SetTextColor(0,0,0); 
		  $this->Set_Font_Size($idfontsize);

		  if (!$raligntext) {
			  if (($this->y - $_PosY)>=$imheight) { //if header text had more height than the image
				$this->SetX($_PosX); //position to the left border, we are now under the logo hopefully
			  }
			  else {
				$this->SetXY($_PosX, $_PosY+$imheight);
			  }
			  $this->MultiCell($this->_Width-(2*$padding), $this->_Line_Height, "$txtid",0,'L');
			}
		}

		if (strlen($barcode)) {
		  $Y=parent::GetY();
		  $qz=$bar_w*10; //quiet zone
		  //$X=$_PosX+$padding+$qz; //force quiet zone on the left
		  $X=$_PosX; //quite zone on the left
		  $bstyle = array(
		      'position' => '',
		      'align' => 'L',
		      'stretch' => false,
		      'fitwidth' => false,
		      'cellfitalign' => '',
		      'border' => false,
		      'hpadding' => (10*$bar_w),
		      'vpadding' => 'auto',
		      'fgcolor' => array(0,0,0),
		      'bgcolor' => false, //array(255,255,255),
		      'text' => true,
		      'font' => 'helvetica',
		      'fontsize' => 9,
		      'stretchtext' => 1
		  );

		  //code,type,x,y, width,height, xres, style, align
		  //$this->write1DBarcode($barcode, 'C39E+', $X, $Y, $this->_Width, $bar_h, $bar_w, $bstyle, 'N');
		  //$this->write1DBarcode($barcode, 'C128', $X, $Y, $this->_Width, $bar_h, $bar_w, $bstyle, 'N');

		  // QRCODE,M : QR-CODE Medium error correction
		  $this->write2DBarcode($barcode, 'QRCODE,M', $X, $Y, $barcodesize,$barcodesize, $bstyle, 'N');
//		  $this->write2DBarcode('www.lala.org', 'QRCODE,M', $X, $Y, $barcodesize,$barcodesize, $bstyle, 'N');

		}


		//rest of the text

		if ($raligntext) {
			//$this->SetXY($_PosX+$barcodesize-2, $Y+$qz-3);
			$this->SetFont('freesans','B');
			$this->SetXY($_PosX+$barcodesize, $Y+$qz);
			$this->MultiCell($this->_Width-(2*$padding), $this->_Line_Height, $txtid,0,'L');
			$this->SetX($_PosX+$barcodesize); //position to the left border, we are now under the logo image hopefully
		}
		else {
			$this->SetX($_PosX); //position to the left border, we are now under the logo image hopefully
		}

		$this->SetFont('freesans');
		$this->SetTextColor(0,0,0); 
		$this->Set_Font_Size($fontsize);

		$this->MultiCell($this->_Width-(2*$padding), $this->_Line_Height, $text,0,'L');

		if ($bordercolor) 
		  $this->SetDrawColor($bordercolor,$bordercolor,$bordercolor);

		//horz borders (sivann):
		$this->Line($_PosX,$_PosY,$_PosX+$this->_Width,$_PosY);
		$this->Line($_PosX,$_PosY+$this->_Height, $_PosX+$this->_Width, $_PosY+$this->_Height);

		//sivann: vertical borders:
		$this->Line($_PosX,$_PosY,$_PosX,$_PosY+$this->_Height);
		$this->Line($_PosX+$this->_Width,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height);

	}

}
?>
