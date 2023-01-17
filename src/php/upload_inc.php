<?php

function upload($file_id, $folder="", $ftype,$title,$date,$id,$assoctable="",$colname="",$uploader="") {
global $dbh,$uploadErrors;
    if ($_FILES[$file_id]["error"] > 0) {
      $result=$uploadErrors[$_FILES[$file_id]["error"]];
      return array('',$result);
    }

    if(!$_FILES[$file_id]['name']) return array('','No file specified');
    if(!$_FILES[$file_id]['size']) return array('','File is zero length');
    if(!is_numeric($ftype)) return array('',"No type specified ($ftype)");
    if(!strlen($title)) return array('','No title specified');
    if(!strlen($date)) return array('','No date specified');
    if(!strlen($assoctable)) return array('','No table specified');
    if(!strlen($colname)) return array('','No colname specified');
    if(!strlen($folder)) return array('','No folder specified');
    $ftypestr=ftype2str($ftype,$dbh);
    

    $path_parts = pathinfo($_FILES[$file_id]["name"]);
    $fileext=$path_parts['extension'];
    $unique=substr(uniqid(),-4,4);

    $filefn="$ftypestr-".validfn($title)."-$unique.$fileext";
    $filefn=strtolower($filefn);

    $uploadfile = $folder.$filefn;

    $result = '';

    //Move the file from the stored location to the new location
    if (!move_uploaded_file($_FILES[$file_id]['tmp_name'], $uploadfile)) {
        $result = "Cannot upload the file '".$_FILES[$file_id]['name']."'"; 
        if(!file_exists($folder)) {
            $result .= " : Folder doesn't exist.";
        } elseif(!is_writable($folder)) {
            $result .= " : Folder not writable.";
        } elseif(!is_writable($uploadfile)) {
            $result .= " : File not writable.";
        }
        $filefn = '';

	return array($filefn,$result);
    } 

    //else file was written
    //chmod($uploadfile,0777);//Make it universally writable.

    //add file to files table
    $datesec=ymd2sec($date);
    $sql="INSERT into files (type,title,date,fname,uploader,uploaddate) VALUES ($ftype,'$title','$datesec','$filefn','$uploader','".time()."')";
    db_exec($dbh,$sql);
    $lastid=$dbh->lastInsertId();


    //make association
    $sql="INSERT into $assoctable ($colname,fileid) VALUES ($id,$lastid)";
    db_exec($dbh,$sql);

    return array($filefn,$result);
}

