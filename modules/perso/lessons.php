<?PHP

/*===========================================================================
*   Open eClass 2.1
*   E-learning and Course Management System
* ===========================================================================
*	Copyright(c) 2003-2008  Greek Universities Network - GUnet
*	A full copyright notice can be read in "/info/copyright.txt".
*
*  	Authors:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*				Yannis Exidaridis <jexi@noc.uoa.gr>
*				Alexandros Diamantidis <adia@noc.uoa.gr>
*
*	For a full list of contributors, see "credits.txt".
*
*	This program is a free software under the terms of the GNU
*	(General Public License) as published by the Free Software
*	Foundation. See the GNU License for more details.
*	The full license can be read in "license.txt".
*
*	Contact address: 	GUnet Asynchronous Teleteaching Group,
*						Network Operations Center, University of Athens,
*						Panepistimiopolis Ilissia, 15784, Athens, Greece
*						eMail: eclassadmin@gunet.gr
============================================================================*/



/*

 * Personalised Lessons Component, eClass Personalised

 *

 * @author Evelthon Prodromou <eprodromou@upnet.gr>

 * @version $Id$

 * @package eClass Personalised

 *

 * @abstract This component populates the lessons block on the user's personalised

 * interface. It is based on the diploma thesis of Evelthon Prodromou.

 *

 */



/*

 * Function getUserLessonInfo

 *

 * Creates content for the user's lesson block on the personalised interface

 * If type is 'html' it creates the interface html populated with data and

 * If type is 'data' it returns an array with all lesson data

 *

 * @param int $uid user id

 * @param string $type (data, html)

 * @return mixed content

 */

function  getUserLessonInfo($uid, $type) {

	//	?$userID=$uid;

	global $mysqlMainDb;



	//	TODO: add the new fields for memory in the db



	$user_courses = "SELECT cours.code , cours.fake_code ,

	                                           cours.intitule , cours.titulaires ,

	                                           cours.languageCourse ,

	                                           cours_user.statut,

	                                           user.perso,

	                                           user.announce_flag,

	                                           user.doc_flag,

	                                           user.forum_flag

	                                   FROM    cours, cours_user, user

	                                  WHERE cours.code = cours_user.code_cours

	                                  AND   cours_user.user_id = '".$uid."'

	                                  AND   user.user_id = '".$uid."'

	                                  ";


	$mysql_query_result = db_query($user_courses, $mysqlMainDb);

	$repeat_val = 0;

	$lesson_titles = array();

        $lesson_fakeCode = array();



	//getting user's lesson info

	while ($mycourses = mysql_fetch_row($mysql_query_result)) {

		$lesson_titles[$repeat_val] 	= $mycourses[2]; //lesson titles

		$lesson_code[$repeat_val]	= $mycourses[0]; //lesson code used in tables

		$lesson_professor[$repeat_val]	= $mycourses[3]; //lesson professor

		$lesson_statut[$repeat_val]	= $mycourses[5];//statut (user|prof)

		$lesson_fakeCode[$repeat_val]	= $mycourses[1];//lesson fake code

		$repeat_val++;

	}



	$memory = "SELECT user.announce_flag, user.doc_flag, user.forum_flag

		FROM user WHERE user.user_id = '".$uid."'";

	$memory_result = db_query($memory, $mysqlMainDb);


	while ($my_memory_result = mysql_fetch_row($memory_result)) {

		$lesson_announce_f = eregi_replace("-", " ", $my_memory_result[0]);

		$lesson_doc_f = eregi_replace("-", " ", $my_memory_result[1]);

		$lesson_forum_f = eregi_replace("-", " ", $my_memory_result[2]);

	}

	$max_repeat_val = $repeat_val;

	$ret_val[0] = $max_repeat_val;

	$ret_val[1] = $lesson_titles;

	@$ret_val[2] = $lesson_code;

	@$ret_val[3] = $lesson_professor;

	@$ret_val[4] = $lesson_statut;

	$ret_val[5] = $lesson_announce_f;

	$ret_val[6] = $lesson_doc_f;

	$ret_val[7] = $lesson_forum_f;



	//check what sort of data should be returned

	if($type == "html") {

		return array($ret_val,htmlInterface($ret_val, $lesson_fakeCode));

		//		return htmlInterface($ret_val);

	} elseif ($type == "data") {

		return $ret_val;

	}


}



/**

 * Function htmlInterface

 *

 * @param array $data

 * @param string $lesson_fCode (Lesson's fake code)

 * @return string HTML content for the documents block

 */

function htmlInterface($data, $lesson_fCode) {

	global $statut, $is_admin, $urlServer, $langCourseCreate, $langOtherCourses;

	global $langNotEnrolledToLessons, $langCreateLesson, $langEnroll;

	global $langCourse, $langActions, $langUnregCourse, $langManagement, $uid;

	$lesson_content = "";
	if ($data[0] > 0) {

	$lesson_content .= <<<lCont
<div id="assigncontainer">


        <table width="100%" class="FormData">
        <tbody>
        <tr class="lessonslist_header">
          <td width="90%" colspan="2"><b>$langCourse</b></td>
          <td><b>$langActions</b></td>
        </tr>
lCont;

		for ($i=0; $i<$data[0]; $i++) {
 			$lesson_content .= "
        <tr style=\"background-color: transparent;\" onmouseover=\"this.style.backgroundColor='#fbfbfb'\" onmouseout=\"this.style.backgroundColor='transparent'\">
          <td align='left' width=\"1\" style=\"padding-left: 4px; padding-right: 0px;\"><img style='border:0px;' src='${urlServer}/template/classic/img/arrow_grey.gif' title='bullet'></td>
          <td align='left' style=\"padding-left: 0px; padding-top: 2px; padding-bottom: 2px; padding-right: 0px;\"><a href=\"courses/".$data[2][$i]."\">".$lesson_fCode[$i]." - ".$data[1][$i]."</a><cite class=\"content_pos\">".$data[3][$i]."</cite></td>";
			if ($data[4][$i] == '5') {
				$lesson_content .= "
          <td align='center'><a href=\"modules/unreguser/unregcours.php?cid=".$data[2][$i]."&uid=".$uid."\"><img style='border:0px;' src='${urlServer}/template/classic/img/cunregister.gif' title='$langUnregCourse'></img></a></td>
        </tr>";
			} elseif ($data[4][$i] == '1') {
				$lesson_content .= "
          <td align='center'><a href=\"modules/course_info/infocours.php?from_home=TRUE&cid=".$data[2][$i]."\"><img style='border:0px;' src='${urlServer}/template/classic/img/referencement.gif' title='$langManagement'></img></a></td>
        </tr>";
			}
		}
		$lesson_content .= "
		</tbody>
        </table>
        </div>";

	} else {
		$lesson_content .= "<p>$langNotEnrolledToLessons</p>";

		if ($statut == 1) {
			$lesson_content .= "<p>$langCreateLesson</p>";
		}

		$lesson_content .= "<p>$langEnroll</p>";

	}

	//$lesson_content .= "<a class=\"enroll_icon\" href=".$urlServer."modules/auth/courses.php>$langOtherCourses</a>";


    /*
	if ($statut == 1) {

		$lesson_content .= "

	 | <a class=\"create_lesson\" href=".$urlServer."modules/create_course/create_course.php>$langCourseCreate</a>

	";
	}
    */

	return $lesson_content;
}

?>

