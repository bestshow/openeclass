<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/
/*
 * video
 *
 * @author Dimitris Tsachalis <ditsa@ccf.auth.gr>
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 *
 * @abstract
 *
 */
/*******************************************************************
*			   VIDEO UPLOADER AND DOWNLOADER
********************************************************************

The script makes 5 things:
1. Upload video
2. Give them a name
3. Modify data about video
4. Delete link to video and simultaneously remove them
5. Show video list to students and visitors

On the long run, the idea is to allow sending realvideo . Which means only
establish a correspondence between RealServer Content Path and the user's
documents path.

*/

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Video';
$guest_allowed = true;

include '../../include/baseTheme.php';
include_once "../../include/lib/fileUploadLib.inc.php";

/**** The following is added for statistics purposes ***/
include('../../include/action.php');
$action = new action();
$action->record('MODULE_ID_VIDEO');
/**************************************/

include '../../include/lib/forcedownload.php';

$nameTools = $langVideo;
$tool_content = $head_content = "";
if (isset($_SESSION['prenom'])) { 
$nick=$_SESSION['prenom']." ".$_SESSION['nom'];
}

// ----------------------
// download video
// ----------------------

if (isset($_GET['action']) and $_GET['action'] == "download") {
	$id = $_GET['id'];
	$real_file = $webDir."/video/".$currentCourseID."/".$id;
	if (strpos($real_file, '/../') === FALSE) {
		$result = db_query ("SELECT url FROM video WHERE path = '$id'", $currentCourseID);
		$row = mysql_fetch_array($result);
		if (!empty($row['url']))
		{
			$id = $row['url'];
		}
		send_file_to_client($real_file, my_basename($id), true, true);
		exit;
	} else {
		header("Refresh: ${urlServer}modules/video/video.php");
	}
}

if($is_adminOfCourse) {
	$head_content = '
<script>
function confirmation (name)
{
    if (confirm("'.$langConfirmDelete.'"+ name + " ?"))
        {return true;}
    else
        {return false;}
}
</script>
';

$head_content .= <<<hContent
<script type="text/javascript">
function checkrequired(which, entry) {
	var pass=true;
	if (document.images) {
		for (i=0;i<which.length;i++) {
			var tempobj=which.elements[i];
			if (tempobj.name == entry) {
				if (tempobj.type=="text"&&tempobj.value=='') {
					pass=false;
					break;
		  		}
	  		}
		}
	}
	if (!pass) {
		alert("$langEmptyVideoTitle");
		return false;
	} else {
		return true;
	}
}

</script>
hContent;
	
$d = mysql_fetch_array(db_query("SELECT video_quota FROM cours WHERE code='$currentCourseID'",$mysqlMainDb));
$diskQuotaVideo = $d['video_quota'];
$updir = "$webDir/video/$currentCourseID"; //path to upload directory
$diskUsed = dir_total_space($updir);

if (isset($_GET['showQuota']) and $_GET['showQuota'] == TRUE) {
	$nameTools = $langQuotaBar;
	$navigation[]= array ("url"=>"$_SERVER[PHP_SELF]", "name"=> $langVideo);
	$tool_content .= showquota($diskQuotaVideo, $diskUsed);
	draw($tool_content, 2);
	exit;
}	

if (isset($_POST['edit_submit'])) { // edit
	if(isset($_POST['id'])) {
		$id = intval($_POST['id']);
		if (isset($_POST['table'])) {
			$table = $_POST['table'];
		}
		if ($table == 'video') {
			$sql = "UPDATE $table SET titre='".mysql_real_escape_string($_POST['titre'])."',
				description='".mysql_real_escape_string($_POST['description'])."',
				creator='".mysql_real_escape_string($_POST['creator'])."',
				publisher='".mysql_real_escape_string($_POST['publisher'])."'
			WHERE id='".mysql_real_escape_string($id)."'";	
		} elseif ($table == 'videolinks') {
			$sql = "UPDATE $table SET url='".mysql_real_escape_string($_POST['url'])."',
				titre='".mysql_real_escape_string($_POST['titre'])."',
				description='".mysql_real_escape_string($_POST['description'])."',
				creator='".mysql_real_escape_string($_POST['creator'])."',
				publisher='".mysql_real_escape_string($_POST['publisher'])."'
			WHERE id='".mysql_real_escape_string($id)."'";
		}
		$result = db_query($sql, $currentCourseID);
		$tool_content .= "<p class=\"success\">$langTitleMod</p><br />";
		$id = "";
	}
}	
if (isset($_POST['add_submit'])) {  // add
		if(isset($_POST['URL'])) { // add videolinks
			$url = $_POST['URL'];
			if ($_POST['titre'] == "") {
				$titre = $url;
			} else {
				$titre = $_POST['titre'];
			}
			$sql = "INSERT INTO videolinks (url,titre,description,creator,publisher,date)
				VALUES ('$url','".mysql_real_escape_string($titre)."',
				'".mysql_real_escape_string($_POST['description'])."',
				'".mysql_real_escape_string($_POST['creator'])."',
				'".mysql_real_escape_string($_POST['publisher'])."',
				'".mysql_real_escape_string($_POST['date'])."')";
			$result = db_query($sql, $currentCourseID);
			$tool_content .= "<p class=\"success\">$langLinkAdded</p><br />";
		} else {  // add video
			if (isset($_FILES['userFile']) && is_uploaded_file($_FILES['userFile']['tmp_name'])) {
				if ($diskUsed + @$_FILES['userFile']['size'] > $diskQuotaVideo) {
					$tool_content .= "<p class=\"caution\">$langNoSpace<br />
						<a href=\"$_SERVER[PHP_SELF]\">$langBack</a></p><br />";
						draw($tool_content, 2, '', $head_content);
						exit;
				} else {
					$file_name = $_FILES['userFile']['name'];
					$tmpfile = $_FILES['userFile']['tmp_name'];
					// convert php file in phps to protect the platform against malicious codes
					$file_name = preg_replace("/\.php$/", ".phps", $file_name);
					// check for dangerous file extensions
					if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' .'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' .'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $file_name)) {
						$tool_content .= "<p class=\"caution\">$langUnwantedFiletype:  $file_name<br />";
						$tool_content .= "<a href=\"$_SERVER[PHP_SELF]\">$langBack</a></p><br />";
						draw($tool_content, 2, '', $head_content);
						exit;
					}
					$file_name = str_replace(" ", "%20", $file_name);
					$file_name = str_replace("%20", "", $file_name);
					$file_name = str_replace("\'", "", $file_name);
					$safe_filename = date("YmdGis").randomkeys("8").".".get_file_extension($file_name);
					$iscopy = copy("$tmpfile", "$updir/$safe_filename");
					if(!$iscopy) {
						$tool_content .= "<p class=\"success\">$langFileNot<br />
						<a href=\"$_SERVER[PHP_SELF]\">$langBack</a></p><br />";
						draw($tool_content, 2, '', $head_content);
						exit;
					}
					$path = "/".$safe_filename;
					$url="$file_name";
					$sql = "INSERT INTO video (path, url, titre, description, creator, publisher, date)
						VALUES ('$path', '$url',
						'".mysql_real_escape_string($_POST['titre'])."',
						'".mysql_real_escape_string($_POST['description'])."',
						'".mysql_real_escape_string($_POST['creator'])."',
						'".mysql_real_escape_string($_POST['publisher'])."',
						'".mysql_real_escape_string($_POST['date'])."')";
				}
				$result = db_query($sql, $currentCourseID);
				$tool_content .= "<p class=\"success\">$langFAdd</p><br />";
			}
		}
	}	// end of add
	if (isset($_GET['delete'])) { // delete
		$id = intval($_GET['id']);
		$table = $_GET['table'];
		$sql_select="SELECT * FROM $table WHERE id='".mysql_real_escape_string($id)."'";
		$result = db_query($sql_select,$currentCourseID);
		$myrow = mysql_fetch_array($result);
		if($table == "video") {
			unlink("$webDir/video/$currentCourseID/".$myrow['path']);
		}
		$sql = "DELETE FROM $table WHERE id='".mysql_real_escape_string($id)."'";
		$result = db_query($sql,$currentCourseID);
		$tool_content .= "<p class=\"success\">$langDelF</p><br />";
		$id="";
	} elseif (isset($_GET['form_input']) && $_GET['form_input'] == "file") { // display video form
		$nameTools = $langAddV;
		$navigation[] = array ("url"=>"video.php", "name"=> $langVideo);
		$tool_content .= "
              <form method=\"POST\" action=\"$_SERVER[PHP_SELF]\" enctype=\"multipart/form-data\" onsubmit=\"return checkrequired(this, 'titre');\">
              <fieldset>
              <legend>$langAddV</legend>
		<table width=\"100%\" class=\"tbl\">
		<tr>
		  <th valign='top'>$langWorkFile :</th>
		  <td>
		    <input type=\"hidden\" name=\"id\" value=\"\">
		    <input type=\"file\" name=\"userFile\" size=\"38\">
                    <br />
                    $langPathUploadFile
		  </td>
		<tr>
		  <th>$langVideoTitle:</th>
		  <td><input type=\"text\" name=\"titre\" value=\"\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langDescr&nbsp;:</th>
		  <td><textarea wrap=\"physical\" rows=\"3\" name=\"description\" cols=\"52\"></textarea></td>
		</tr>
		<tr>
		  <th>$langcreator&nbsp;:</th>
		  <td><input type=\"text\" name=\"creator\" value=\"$nick\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langpublisher &nbsp;:</th>
		  <td><input type=\"text\" name=\"publisher\" value=\"$nick\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langdate &nbsp;:</th>
		  <td><input type=\"text\" name=\"date\" value=\"".date("Y-m-d G:i:s")."\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>&nbsp;</th>
		  <td><input type=\"submit\" name=\"add_submit\" value=\"$dropbox_lang[uploadFile]\"></td>
		</tr>
                <tr>
                  <th>&nbsp;</th>
                  <td><div align='right'>$langMaxFileSize <b>". ini_get('upload_max_filesize') . "</b></div></td>
                </tr>
		</table>
              </fieldset>
	      </form>";
	} elseif (isset($_GET['form_input']) && $_GET['form_input'] == "url") { // display video links form
		$nameTools = $langAddVideoLink;
		$navigation[] = array ("url"=>"video.php", "name"=> $langVideo);
		$tool_content .= "
		<form method=\"POST\" action=\"$_SERVER[PHP_SELF]\" onsubmit=\"return checkrequired(this, 'titre');\">
                <fieldset>
                <legend>$langAddVideoLink</legend>
		<table width=\"100%\" class=\"tbl\">
		<tr>
		  <th valign='top'>$langGiveURL<input type=\"hidden\" name=\"id\" value=\"\"></th>
		  <td><input type=\"text\" name=\"URL\" size=\"55\">
                      <br />
                      $langURL
                  </td>
		<tr>
		  <th>$langVideoTitle :</th>
		  <td><input type=\"text\" name=\"titre\" value=\"\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langDescr :</th>
		  <td><textarea wrap=\"physical\" rows=\"3\" name=\"description\" cols=\"52\"></textarea></td>
		</tr>
		<tr>
		  <th>$langcreator :</th>
		  <td><input type=\"text\" name=\"creator\" value=\"$nick\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langpublisher :</th>
		  <td><input type=\"text\" name=\"publisher\" value=\"$nick\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>$langdate :</th>
		  <td><input type=\"text\" name=\"date\" value=\"".date("Y-m-d G:i")."\" size=\"55\"></td>
		</tr>
		<tr>
		  <th>&nbsp;</th>
		  <td><input type=\"submit\" name=\"add_submit\" value=\"$langAdd\"></td>
		</tr>
		</table>
                </fieldset>
		</form>
		<br/>";
	}

// ------------------- if no submit -----------------------
if (isset($_GET['id']) and isset($_GET['table_edit']))  {
	$id = intval($_GET['id']);
	$table_edit = $_GET['table_edit'];
	if($id != "") {
		$sql = "SELECT * FROM $table_edit WHERE id='".mysql_real_escape_string($id)."' ORDER BY titre";
		$result = db_query($sql,$currentCourseID);
		$myrow = mysql_fetch_array($result);
		$id = $myrow[0];
		if ($table_edit == 'videolinks') {
			$url= $myrow[1];
			$titre = $myrow[2];
			$description = $myrow[3];
			$creator = $myrow[4];
			$publisher = $myrow[5];
		} elseif ($table_edit == 'video') {
			$url= $myrow[2];
			$titre = $myrow[3];
			$description = $myrow[4];
			$creator = $myrow[5];
			$publisher = $myrow[6];
		}
		$nameTools = $langModify;
		$navigation[] = array ("url"=>"video.php", "name"=> $langVideo);
		$tool_content .= "
           <form method='POST' action='$_SERVER[PHP_SELF]' onsubmit=\"return checkrequired(this, 'titre');\">
           <fieldset>
           <legend>$langModify</legend>

           <table width=\"100%\" class=\"tbl\">";
		if ($table_edit == 'videolinks') {
			$tool_content .= "
           <tr>
             <th>$langURL:</th>
             <td><input type='text' name='url' value='$url' size='55'></td>
           </tr>";
		}
		elseif ($table_edit == 'video') {
			$tool_content .= "<input type='hidden' name='url' value='$url'>";
		}
		@$tool_content .= "
           <tr>
             <th>$langVideoTitle:</th>
             <td><input type=\"text\" name=\"titre\" value=\"".$titre."\" size=\"55\"></td>
	   </tr>
           <tr>
             <th>$langDescr&nbsp;:</th>
             <td><textarea wrap=\"physical\" rows=\"3\" name=\"description\" cols=\"52\">".$description."</textarea></td>
          </tr>
          <tr>
            <th>$langcreator&nbsp;:</th>
            <td><input type=\"text\" name=\"creator\" value=\"".$creator."\" size=\"55\"></td>
          </tr>
          <tr>
            <th>$langpublisher &nbsp;:</th>
            <td><input type=\"text\" name=\"publisher\" value=\"".$publisher."\" size=\"55\"></td>
          </tr>
          <tr>
            <th>&nbsp;</th>
            <td><input type=\"submit\" name=\"edit_submit\" value=\"$langModify\">
		<input type=\"hidden\" name=\"id\" value=\"".$id."\">
		<input type=\"hidden\" name=\"table\" value=\"".$table_edit."\">
            </td>
          </tr>
          </table>
          </fieldset>
          </form>
          <br/>";
	}
}	// if id
	if (!isset($_GET['form_input'])) {
		
          $tool_content .= "
        <div id='operations_container'>
	  <ul id='opslist'>
	    <li><a href='$_SERVER[PHP_SELF]?form_input=file'>$langAddV</a></li>
	    <li><a href='$_SERVER[PHP_SELF]?form_input=url'>$langAddVideoLink</a></li>
	    <li><a href='$_SERVER[PHP_SELF]?showQuota=TRUE'>$langQuotaBar</a></li>
	  </ul>
	</div>";
	}

	$count_video = mysql_fetch_array(db_query("SELECT count(*) FROM video ORDER BY titre",$currentCourseID));
	$count_video_links = mysql_fetch_array(db_query("SELECT count(*) FROM videolinks
				ORDER BY titre",$currentCourseID));

	if ($count_video[0]<>0 || $count_video_links[0]<>0) {
	// print the list if there is no editing
		$results['video'] = db_query("SELECT *  FROM video ORDER BY titre",$currentCourseID);
		$results['videolinks'] = db_query("SELECT * FROM videolinks ORDER BY titre",$currentCourseID);
		$i=0;
		$count_video_presented_for_admin=1;
		$tool_content.= "
        <table width=\"100%\" class=\"tbl_alt\">
        <tr>
          <th>&nbsp;</th>
          <th><div align=\"left\">$langDirectory $langVideo</div></th>
          <th width=\"150\"><div align=\"left\">$langcreator</div></th>
          <th width=\"150\"><div align=\"left\">$langpublisher</div></th>
          <th width=\"70\">$langdate</th>
          <th width=\"70\">$langActions</th>
        </tr>";
		foreach($results as $table => $result)
		while ($myrow = mysql_fetch_array($result)) {
			switch($table){
				case "video":
					if(isset($vodServer)) {
						$videoURL=$vodServer."$currentCourseID/".$myrow[1];
					} else {
						$videoURL = "'$_SERVER[PHP_SELF]?action=download&id=$myrow[1]'";
					}
					$link_to_add = "\n          <td><a href= $videoURL>$myrow[3]</a><br>\n$myrow[4]</td>\n      <td>$myrow[5]</td>\n      <td>$myrow[6]</td>\n<td align='center'>".nice_format(date("Y-m-d", strtotime($myrow[7])))."</td>";
					break;
				case "videolinks":
					$videoURL= "'$myrow[1]' target=_blank";
					$link_to_add = "\n          <td><a href= $videoURL>$myrow[2]</a><br>$myrow[3]</td>\n      <td>$myrow[4]</td>\n      <td>$myrow[5]</td>\n      <td align='center'>".nice_format(date("Y-m-d", strtotime($myrow[6])))."</td>";
					break;
				default:
					exit;
			}
			if ($i%2) {
				$rowClass = "class='odd'";
			} else {
				$rowClass = "class='even'";
			}
				$tool_content .= "\n        <tr $rowClass>";
				$tool_content .= "\n          <td width=\"1\" valign='top'><img style='border:0px; padding-top:3px;' src='${urlServer}/template/classic/img/arrow.png' title='bullet'></td>";
				$tool_content .= $link_to_add;
				$tool_content .= "\n          <td align='center'><a href='$_SERVER[PHP_SELF]?id=$myrow[0]&table_edit=$table'><img src='../../template/classic/img/edit.png' border='0' title='$langModify'></img></a>&nbsp;&nbsp;<a href='$_SERVER[PHP_SELF]?id=$myrow[0]&delete=yes&table=$table' onClick='return confirmation(\"".addslashes($myrow[2])."\");'><img src='../../template/classic/img/delete.png' border='0' title='$langDelete'></img></a></td>";
                                $tool_content .= "\n        </tr>";
			$i++;
			$count_video_presented_for_admin++;
		} // while
		$tool_content.="\n        </table>";
	}
	else
	{
		$tool_content .= "\n        <p class='alert1'>$langNoVideo</p>";
	}
}   // if uid=prof_id

// student view
else {
	$results['video'] = db_query("SELECT *  FROM video ORDER BY titre",$currentCourseID);
	$results['videolinks'] = db_query("SELECT * FROM videolinks ORDER BY titre",$currentCourseID);
	$count_video = mysql_fetch_array(db_query("SELECT count(*) FROM video ORDER BY titre",$currentCourseID));
	$count_video_links = mysql_fetch_array(db_query("SELECT count(*) FROM videolinks
			ORDER BY titre",$currentCourseID));
	if ($count_video[0]<>0 || $count_video_links[0]<>0) {
		$tool_content .= "
		<table width=\"100%\" class=\"tbl_alt\">
		<tr>
                 
                  <th colspan='2'><div align=\"left\">$langDirectory $langVideo</div></th>
		</tr>";
		$i=0;
		$count_video_presented=1;
		foreach($results as $table => $result) {
			while ($myrow = mysql_fetch_array($result)) {
				switch($table){
					case "video":
						if(isset($vodServer)) {
							$videoURL=$vodServer."$currentCourseID/".$myrow[1];
						} else {
							$videoURL = "'$_SERVER[PHP_SELF]?action=download&id=$myrow[1]'";
						}
						$link_to_add = "\n                  <td><a href=$videoURL>$myrow[3]</a><br /><small>$myrow[4]</small></td>";
						break;
					case "videolinks":
						$videoURL= "'$myrow[1]' target=_blank";
						$link_to_add = "\n                  <td><a href=$videoURL>$myrow[2]</a><br />$myrow[3]</td>";
						break;
					default:
						exit;
				}
				if ($i%2) {
					$rowClass = "class='odd'";
				} else {
					$rowClass = "class='even'";
				}
				$tool_content .= "\n                <tr $rowClass>";
				$tool_content .= "\n                  <td width=\"1\" valign='top'><img style='border:0px; padding-top:3px;' src='${urlServer}/template/classic/img/arrow.png' title='bullet'></td>";
				$tool_content .= $link_to_add;
				$tool_content .= "\n                </tr>";
				$i++;
				$count_video_presented++;
			}
		}
		$tool_content .= "\n                </table>\n";
	} else {
		$tool_content .= "                  <p class='alert1'>$langNoVideo</p>";
	}
}
add_units_navigation(TRUE);
if (isset($head_content))
	draw($tool_content, 2, '', $head_content);
else
	draw($tool_content, 2);
?>
