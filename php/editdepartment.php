<?php /* Cory Funk 2015, cafunk@fhsu.edu */?>

<SCRIPT LANGUAGE="JavaScript"> 

  function confirm_filled($row)
  {
	  var filled = 0;
	  $row.find('input,select').each(function() {
		  if (jQuery(this).val()) filled++;
	  });
	  if (filled) return confirm('Do you really want to remove this row?');
	  return true;
  };

 $(document).ready(function() {

    //delete table row on image click
    $('.delrow').click(function(){
        var answer = confirm("Are you sure you want to delete this row ?")
        if (answer) 
	  $(this).parent().parent().remove();
    });

    $("#caddrow").click(function($e) {
	var row = $('#contactstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#contactstable tr:last');
    });
    $("#uaddrow").click(function($e) {
	var row = $('#urlstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#urlstable tr:last');
    });
  });
</SCRIPT>

<script type="text/javascript" src="../js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="../js/jquery.eComboBox.min.js"></script>

<script language="JavaScript"> //Common functions for all dropdowns

  /*----------------------------------------------
  The Common functions used for all dropdowns are:
  -----------------------------------------------

  -- function FindKeyCode(e)
  -- function FindKeyChar(e)
  -- function fnLeftToRight(getdropdown)
  -- function fnRightToLeft(getdropdown)

  --------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html */

  function fnLeftToRight(getdropdown)
  {
    getdropdown.style.direction = "ltr";
  }

  function fnRightToLeft(getdropdown)
  {
    getdropdown.style.direction = "rtl";
  }


  /*
  Since Internet Explorer and NetscapeFirefoxChrome have different
  ways of returning the key code, displaying keys
  browser-independently is a bit harder.
  However, you can create a script that displays keys
  for either browser.
  The following function will display each key
  in the status line:

  The "FindKey.." function receives the "event" object
  from the event handler and stores it in the variable "e".
  It checks whether the "e.which" property exists (for NetscapeFirefoxChrome),
  and stores it in the "keycode" variable if present.
  Otherwise, it assumes the browser is Internet Explorer
  and assigns to keycode the "e.keyCode" property.
  */

  function FindKeyCode(e)
  {
    if(e.which)
    {
    keycode=e.which;  //NetscapeFirefoxChrome
    }
    else
    {
    keycode=e.keyCode; //Internet Explorer
    }

    //alert("FindKeyCode"+ keycode);
    return keycode;
  }

  function FindKeyChar(e)
  {
    keycode = FindKeyCode(e);
    if((keycode==8)||(keycode==127))
    {
    character="backspace"
    }
    else if((keycode==46))
    {
    character="delete"
    }
    else
    {
    character=String.fromCharCode(keycode);
    }
    //alert("FindKey"+ character);
    return character;
  }

</script>

<script language="JavaScript"> //Dropdown specific functions, which manipulate dropdown specific global variables

  /*----------------------------------------------
  Dropdown specific global variables are:
  -----------------------------------------------
  1) vEditableOptionIndex_A   --> this needs to be set by Programmer!! See explanation.
  2) vEditableOptionText_A    --> this needs to be set by Programmer!! See explanation.
  3) vUseActualTexbox_A       --> this needs to be set by Programmer!! See explanation.
  4) vPreviousSelectIndex_A
  5) vSelectIndex_A
  6) vSelectChange_A

  --------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html */

  /*----------------------------------------------
  Dropdown specific functions
  (which manipulate dropdown specific global variables)
  -----------------------------------------------
  1) function fnChangeHandler_A(getdropdown)
  2) function fnFocusHandler_A (getdropdown)
  3) function fnKeyPressHandler_A(getdropdown, e)
  4) function fnKeyUpHandler_A(getdropdown, e)
  5) function fnKeyDownHandler_A(getdropdown, e)

  --------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html */

  /*------------------------------------------------
  IMPORTANT: Global Variable required to be SET by programmer
  -------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html  */

  var vEditableOptionIndex_A = 0;

  // Give Index of Editable option in the dropdown.
  // For eg.
  // if first option is editable then vEditableOptionIndex_A = 0;
  // if second option is editable then vEditableOptionIndex_A = 1;
  // if third option is editable then vEditableOptionIndex_A = 2;
  // if last option is editable then vEditableOptionIndex_A = (length of dropdown - 1).
  // Note: the value of vEditableOptionIndex_A cannot be greater than (length of dropdown - 1)

  var vEditableOptionText_A = "Please Select or Enter A New Option";

  // Give the default text of the Editable option in the dropdown.
  // For eg.
  // if the editable option is <option ...>--?--</option>,
  // then set vEditableOptionText_A = "--?--";

 var vUseActualTexbox_A = "no";
 // = "no" ...
 //      default is 'no' because there is no need to use an actual textbox if using a PC (with physical keyboard)
 //      if using iPad/iPhone or Android device, which usually have a virtual soft keyboard, then textbox will automatically show up next to dropdown on those devices
 // = "yes" ...
 //      set this to 'yes' if and only if you want an actual textbox to show up next to dropdown at all times (even on devices with physical keyboards)

  /*------------------------------------------------
  Global Variables required for
  fnChangeHandler_A(), fnKeyPressHandler_A() and fnKeyUpHandler_A()
  for Editable Dropdowns
  -------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html  */

  var vPreviousSelectIndex_A = 0;
  // Contains the Previously Selected Index, set to 0 by default

  var vSelectIndex_A = 0;
  // Contains the Currently Selected Index, set to 0 by default

  var vSelectChange_A = 'MANUAL_CLICK';
  // Indicates whether Change in dropdown selected option
  // was due to a Manual Click
  // or instead due to System properties of dropdown.

  // vSelectChange_A = 'MANUAL_CLICK' indicates that
  // the jump to a non-editable option in the dropdown was due
  // to a Manual click (i.e.,changed on purpose by user).

  // vSelectChange_A = 'AUTO_SYSTEM' indicates that
  // the jump to a non-editable option was due to System properties of dropdown
  // (i.e.,user did not change the option in the dropdown;
  // instead an automatic jump happened due to inbuilt
  // dropdown properties of browser on typing of a character )

  /*------------------------------------------------
  Functions required for  Editable Dropdowns
  -------------------------- source: http://chakrabarty.com/pp_editable_dropdown.html  */

  function fnSanityCheck_A(getdropdown)
  {
    if(vEditableOptionIndex_A>(getdropdown.options.length-1))
    {
    alert("PROGRAMMING ERROR: The value of variable vEditableOptionIndex_... cannot be greater than (length of dropdown - 1)");
    return false;
    }
  }

  function fnKeyDownHandler_A(getdropdown, e)
  {
    fnSanityCheck_A(getdropdown);

    // Press [ <- ] and [ -> ] arrow keys on the keyboard to change alignment/flow.
    // ...go to Start : Press  [ <- ] Arrow Key
    // ...go to End : Press [ -> ] Arrow Key
    // (this is useful when the edited-text content exceeds the ListBox-fixed-width)
    // This works best on Internet Explorer, and not on NetscapeFirefoxChrome

    var vEventKeyCode = FindKeyCode(e);

    // Press left/right arrow keys
    if(vEventKeyCode == 37)
    {
      fnLeftToRight(getdropdown);
    }
    if(vEventKeyCode == 39)
    {
      fnRightToLeft(getdropdown);
    }

    // Delete key pressed
    if(vEventKeyCode == 46)
    {
      if(getdropdown.options.length != 0)
      // if dropdown is not empty
      {
        if (getdropdown.options.selectedIndex == vEditableOptionIndex_A)
        // if option is the Editable field
        {
          getdropdown.options[getdropdown.options.selectedIndex].text = '';
          getdropdown.options[getdropdown.options.selectedIndex].value = '';
        }
      }
    }

    // backspace key pressed
    if(vEventKeyCode == 8 || vEventKeyCode == 127)
    {
      if(getdropdown.options.length != 0)
      // if dropdown is not empty
      {
        if (getdropdown.options.selectedIndex == vEditableOptionIndex_A)
        // if option is the Editable field
        {
           // make Editable option Null if it is being edited for the first time
           if ((getdropdown[vEditableOptionIndex_A].text == vEditableOptionText_A)||(getdropdown[vEditableOptionIndex_A].value == vEditableOptionText_A))
           {
             getdropdown.options[getdropdown.options.selectedIndex].text = '';
             getdropdown.options[getdropdown.options.selectedIndex].value = '';
           }
           else
           {
             getdropdown.options[getdropdown.options.selectedIndex].text = getdropdown.options[getdropdown.options.selectedIndex].text.slice(0,-1);
             getdropdown.options[getdropdown.options.selectedIndex].value = getdropdown.options[getdropdown.options.selectedIndex].value.slice(0,-1);
           }
        }
      }
      if(e.which) //NetscapeFirefoxChrome
      {
        e.which = '';
      }
      else //Internet Explorer
      {
        e.keyCode = '';
      }
      if (e.cancelBubble)	  //Internet Explorer
      {
        e.cancelBubble = true;
        e.returnValue = false;
      }
      if (e.stopPropagation)	 //NetscapeFirefoxChrome
      {
          e.stopPropagation();
      }
      if (e.preventDefault)	 //NetscapeFirefoxChrome
      {
      	e.preventDefault();
      }
    }
  }

  function fnFocusHandler_A(getdropdown)
  {
    //use textbox for devices such as android and ipad that don't have a physical keyboard (textbox allows use of virtual soft keyboard)
    if ( (navigator.userAgent.toLowerCase().search(/android|ipad|iphone|ipod/) > -1) || (vUseActualTexbox_A == 'yes') )
    {
      if (getdropdown[(vEditableOptionIndex_A)].selected == true)
      {
        document.getElementById('textboxoption_division').style.visibility='';
        document.getElementById('textboxoption_division').style.display='';
      }
      else
      {
        document.getElementById('textboxoption_division').style.visibility='hidden';
        document.getElementById('textboxoption_division').style.display='none';
      }
    }
  }

  function fnChangeHandler_A(getdropdown)
  {
    fnSanityCheck_A(getdropdown);

    vPreviousSelectIndex_A = vSelectIndex_A;
    // Contains the Previously Selected Index

    vSelectIndex_A = getdropdown.options.selectedIndex;
    // Contains the Currently Selected Index

    if ((vPreviousSelectIndex_A == (vEditableOptionIndex_A)) && (vSelectIndex_A != (vEditableOptionIndex_A))&&(vSelectChange_A != 'MANUAL_CLICK'))
    // To Set value of Index variables - source: http://chakrabarty.com/pp_editable_dropdown.html
    {
      getdropdown[(vEditableOptionIndex_A)].selected=true;
      vPreviousSelectIndex_A = vSelectIndex_A;
      vSelectIndex_A = getdropdown.options.selectedIndex;
      vSelectChange_A = 'MANUAL_CLICK';
      // Indicates that the Change in dropdown selected
      // option was due to a Manual Click
    }

    //use textbox for devices such as android and ipad that don't have a physical keyboard (textbox allows use of virtual soft keyboard)
    if ( (navigator.userAgent.toLowerCase().search(/android|ipad|iphone|ipod/) > -1) || (vUseActualTexbox_A == 'yes') )
    {
      fnFocusHandler_A(getdropdown);
    }
  }

  function fnKeyPressHandler_A(getdropdown, e)
  {
    fnSanityCheck_A(getdropdown);

    keycode = FindKeyCode(e);
    keychar = FindKeyChar(e);

    // Check for allowable Characters
    // The various characters allowable for entry into Editable option..
    // may be customized by minor modifications in the code (if condition below)
    // (you need to know the keycode/ASCII value of the  character to be allowed/disallowed.
    // - source: http://chakrabarty.com/pp_editable_dropdown.html

    if ((keycode>47 && keycode<59)||(keycode>62 && keycode<127) ||(keycode==32))
    {
      var vAllowableCharacter = "yes";
    }
    else
    {
      var vAllowableCharacter = "no";
    }

    //alert(window); alert(window.event);

    if(getdropdown.options.length != 0)
    // if dropdown is not empty
      if (getdropdown.options.selectedIndex == (vEditableOptionIndex_A))
      // if selected option the Editable option of the dropdown
      {

        // Empty space (ascii 32)  is not captured by NetscapeFirefoxChrome when .text is used
        // NetscapeFirefoxChrome removes extra spaces at end of string when .text is used
        // NetscapeFirefoxChrome allows one empty space at end of string when .value is used
        // Hence, use .value insead of .text
        var vEditString = getdropdown[vEditableOptionIndex_A].value;

        // make Editable option Null if it is being edited for the first time
        if(vAllowableCharacter == "yes")
        {
          if ((getdropdown[vEditableOptionIndex_A].text == vEditableOptionText_A)||(getdropdown[vEditableOptionIndex_A].value == vEditableOptionText_A))
            vEditString = "";
        }

        if (vAllowableCharacter == "yes")
        // To handle addition of a character - source: http://chakrabarty.com/pp_editable_dropdown.html
        {
          vEditString+=String.fromCharCode(keycode);
          // Concatenate Enter character to Editable string

          // The following portion handles the "automatic Jump" bug
          // The "automatic Jump" bug (Description):
          //   If a alphabet is entered (while editing)
          //   ...which is contained as a first character in one of the read-only options
          //   ..the focus automatically "jumps" to the read-only option
          //   (-- this is a common property of normal dropdowns
          //    ..but..is undesirable while editing).

          var i=0;
          var vEnteredChar = String.fromCharCode(keycode);
          var vUpperCaseEnteredChar = vEnteredChar;
          var vLowerCaseEnteredChar = vEnteredChar;


          if(((keycode)>=97)&&((keycode)<=122))
          // if vEnteredChar lowercase
            vUpperCaseEnteredChar = String.fromCharCode(keycode - 32);
            // This is UpperCase


          if(((keycode)>=65)&&((keycode)<=90))
          // if vEnteredChar is UpperCase
            vLowerCaseEnteredChar = String.fromCharCode(keycode + 32);
            // This is lowercase

          if(e.which) //For NetscapeFirefoxChrome
          {
            // Compare the typed character (into the editable option)
            // with the first character of all the other
            // options (non-editable).

            // To note if the jump to the non-editable option was due
            // to a Manual click (i.e.,changed on purpose by user)
            // or instead due to System properties of dropdown
            // (i.e.,user did not change the option in the dropdown;
            // instead an automatic jump happened due to inbuilt
            // dropdown properties of browser on typing of a character )

            for (i=0;i<=(getdropdown.options.length-1);i++)
            {
              if(i!=vEditableOptionIndex_A)
              {
                var vEnteredDigitNumber = getdropdown[vEditableOptionIndex_A].text.length;
                var vFirstReadOnlyChar = getdropdown[i].text.substring(0,1);
                var vEquivalentReadOnlyChar = getdropdown[i].text.substring(vEnteredDigitNumber,vEnteredDigitNumber+1);
                if (vEnteredDigitNumber >= getdropdown[i].text.length)
                {
                    vEquivalentReadOnlyChar = vFirstReadOnlyChar;
                }
                if( (vEquivalentReadOnlyChar == vUpperCaseEnteredChar)||(vEquivalentReadOnlyChar == vLowerCaseEnteredChar)
                  ||(vFirstReadOnlyChar == vUpperCaseEnteredChar)||(vFirstReadOnlyChar == vLowerCaseEnteredChar) )
                {
                  vSelectChange_A = 'AUTO_SYSTEM';
                  // Indicates that the Change in dropdown selected
                  // option was due to System properties of dropdown
                  break;
                }
                else
                {
                  vSelectChange_A = 'MANUAL_CLICK';
                  // Indicates that the Change in dropdown selected
                  // option was due to a Manual Click
                }
              }
            }
          }
        }

        // Set the new edited string into the Editable option
        getdropdown.options[vEditableOptionIndex_A].text = vEditString;
        getdropdown.options[vEditableOptionIndex_A].value = vEditString;

        return false;
      }
    return true;
  }

  function fnKeyUpHandler_A(getdropdown, e)
  {
    fnSanityCheck_A(getdropdown);

    if(e.which) // NetscapeFirefoxChrome
    {
      if(vSelectChange_A == 'AUTO_SYSTEM')
      {
        // if editable dropdown option jumped while editing
        // (due to typing of a character which is the first character of some other option)
        // then go back to the editable option.
        getdropdown[(vEditableOptionIndex_A)].selected=true;
        vSelectChange_A = 'MANUAL_CLICK';
      }

      var vEventKeyCode = FindKeyCode(e);
      // if [ <- ] or [ -> ] arrow keys are pressed, select the editable option
      if((vEventKeyCode == 37)||(vEventKeyCode == 39))
      {
        getdropdown[vEditableOptionIndex_A].selected=true;
      }
    }
  }
</script>
<!--<script type="text/javascript">
			var selected = "";
			$(document).ready(function(){
				$("#division").eComboBox({
					'allowNewElements' : true,
					'editableElements' : false
				});
			});
</script>
--><?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

//delete department
if (isset($_GET['delid'])) { //if we came from a post (save) the update department 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from departments where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listdepartments'</script>";
  echo "<a href='$scriptname?action=listdepartments'>Go here</a></body></html>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update department 
  $id=$_POST['id'];

  if ($_POST['id']=="new")  {//if we came from a post (save) then add department 
    $sql="INSERT INTO departments (division, name, abbr) VALUES ('$division', '$name', '$abbr')";
		  
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added Department <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE departments SET ".
       " division='$division',name='$name',abbr='$abbr' WHERE id=$id";
    db_exec($dbh,$sql);
	
  echo "<script>document.location='$fscriptname?action=editdepartment&id=$id'</script>";
  echo "<a href='$fscriptname?action=editdepartment&id=$id'></a>"; 
  exit;
  }


}//save pressed

///////////////////////////////// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM departments WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if ($id !="new")
$division=$r['division'];$name=$r['name'];$abbr=$r['abbr'];

echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add Department")."</h1>\n";
else
  echo "\n<h1>".t("Edit Department $id")."</h1>\n";
  
$ph = "Please Select Or Enter A New Option";  //Placeholder for division
?>

<table border="0" cellpadding="5" cellspacing="5" class="tbl1">

<!-- Department Properties Title -->
    <tr> 
      <td class='tdtop'>
        <table border='0' class="tbl2">
          
<!-- Department Properties Title -->
      <tr>
		<td class='tdt'><?php te("Division");?>:</td>
		<td>
		<select name="division" id="division" style="width:35em" onKeyDown="fnKeyDownHandler_A(this, event);" onKeyUp="fnKeyUpHandler_A(this, event); return false;" onKeyPress = "return fnKeyPressHandler_A(this, event);"  
        onChange="fnChangeHandler_A(this);" onFocus="fnFocusHandler_A(this);">
			<option value=''>
			<?php if ($division!="")
					echo $division;
                  else
					echo $ph;
			?>
                </option> <!-- This is the Editable Option -->
                <?php
                $sql="SELECT DISTINCT departments.division FROM departments";
                $sth=$dbh->query($sql);
                $departments=$sth->fetchAll(PDO::FETCH_ASSOC);
                foreach ($departments as $d) {
                    $dbid=$d['id'];
                    $itype=$d['division'];
                    $s="";
                    if (isset($_GET['division']) && $_GET['division']=="$itype") $s=" SELECTED ";
                    echo "<option $s value='".$itype."' title='$itype'>$itype</option>\n";
                }
                echo "</select>
				<!--use textbox for devices such as android and ipad that don't have a physical keyboard (textbox allows use of virtual soft keyboard)-->
				</td>
				<input type='text' id='textboxoption_division' style='visibility:hidden;display:none;width:35em' value='<?php echo $textboxoption_division ?>' onfocus='this.value = 
				document.getElementById('division').options[vEditableOptionIndex_A].text' onKeyUp='document.getElementById('division').options[vEditableOptionIndex_A].text=this.value;
				document.getElementById('division').options[vEditableOptionIndex_A].value=this.value;' onblur='document.getElementById('division').options[vEditableOptionIndex_A].text=this.value;
				document.getElementById('division').options[vEditableOptionIndex_A].value=this.value;
				document.getElementById('division').focus();'></input>

	  </tr>
      <tr>
          <td class='tdt'>"?><?php te("Department Name");?>:</td>
          <td><input style="width:33em" id='name' name='name' value='<?php echo $name?>'></input></td>
      </tr>
      <tr>
          <td class='tdt'><?php te("Department Abbr.");?>:</td>
          <td><input style="width:33em" id='name' name='abbr' value='<?php echo $abbr?>'></input></td>
      </tr>
<!-- end, department Properties Title -->
</table>
<table border="0" class="tbl2">
          <tr>
            <td><button type="submit"><img src="images/save.png" alt="Save" /> <?php te("Save");?></button></td>
				<input type=hidden name='action' value='$action'>
				<input type=hidden name='id' value='$id'>

		<?php
            echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>"."<img title='Delete' src='images/delete.png' border=0>".t("Delete")."
		</button></td>\n</tr>\n";
		echo "\n</table>\n";
		echo "\n<input type=hidden name='action' value='$action'>";
		echo "\n<input type=hidden name='id' value='$id'>";
		?>
       	  </tr>
        </table></td>
      </tr>
    </table>
    </form>
</body>
</html>