<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Biblio Topic Adding Pop Windows */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_write = utility::havePrivilege('bibliography', 'w');
if (!$can_write) {
  die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

// page title
$page_title = 'Subject List';
// check for biblioID in url
$biblioID = 0;
if (isset($_GET['biblioID']) AND $_GET['biblioID']) {
    $biblioID = (integer)$_GET['biblioID'];
}

// utility function to check subject/topic
function checkSubject($str_subject, $str_subject_type = 't')
{
    global $dbs;
    $_q = $dbs->query('SELECT topic_id FROM mst_topic WHERE topic=\''.$str_subject.'\' AND topic_type=\''.$str_subject_type.'\'');
    if ($_q->num_rows > 0) {
        $_d = $_q->fetch_row();
        // return the subject/topic ID
        return $_d[0];
    }
    return false;
}

// start the output buffer
ob_start();
/* main content */
// biblio topic save proccess
if (isset($_POST['save']) AND (isset($_POST['topicID']) OR trim($_POST['search_str']))) {
    $subject = trim($dbs->escape_string(strip_tags($_POST['search_str'])));
    // create new sql op object
    $sql_op = new simbio_dbop($dbs);
    // check if biblioID POST var exists
    if (isset($_POST['biblioID']) AND !empty($_POST['biblioID'])) {
        $data['biblio_id'] = (integer)$_POST['biblioID'];
        // check if the topic select list is empty or not
        if (!empty($_POST['topicID'])) {
            $data['topic_id'] = $_POST['topicID'];
        } else if ($subject AND empty($_POST['topicID'])) {
            // check subject
            $subject_id = checkSubject($subject, $_POST['type']);
            if ($subject_id !== false) {
                $data['topic_id'] = $subject_id;
            } else {
                // adding new topic
                $topic_data['topic'] = $subject;
                $topic_data['topic_type'] = $_POST['type'];
                $topic_data['classification'] = '';
                $topic_data['input_date'] = date('Y-m-d');
                $topic_data['last_update'] = date('Y-m-d');
                // insert new topic to topic master table
                $sql_op->insert('mst_topic', $topic_data);
                // put last inserted ID
                $data['topic_id'] = $sql_op->insert_id;
            }
        }
        $data['level'] = intval($_POST['level']);

        if ($sql_op->insert('biblio_topic', $data)) {
            utility::jsToastr('Subject', __('Topic succesfully updated!'), 'success');
            echo '<script type="text/javascript">';
            echo 'parent.setIframeContent(\'topicIframe\', \''.MWB.'bibliography/iframe_topic.php?biblioID='.$data['biblio_id'].'\');';
            echo '</script>';
        } else {
            utility::jsToastr('Subject', __('Subject FAILED to Add. Please Contact System Administrator')."\n".$sql_op->error, 'error');
        }
    } else {
        if (!empty($_POST['topicID'])) {
            // add to current session
            $_SESSION['biblioTopic'][$_POST['topicID']] = array($_POST['topicID'], intval($_POST['level']));
        } else if ($subject AND empty($_POST['topicID'])) {
            // check subject
            $subject_id = checkSubject($subject);
            if ($subject_id !== false) {
                $last_id = $subject_id;
            } else {
                // adding new topic
                $topic_data['topic'] = $subject;
                $topic_data['topic_type'] = $_POST['type'];
                $topic_data['classification'] = '';
                $topic_data['input_date'] = date('Y-m-d');
                $topic_data['last_update'] = date('Y-m-d');
                // insert new topic to topic master table
                $sql_op->insert('mst_topic', $topic_data);
                $last_id = $sql_op->insert_id;
            }
            $_SESSION['biblioTopic'][$last_id] = array($last_id, intval($_POST['level']));
        }

        utility::jsToastr('Subject', __('Subject added!'), 'success');
        echo '<script type="text/javascript">';
        echo 'parent.setIframeContent(\'topicIframe\', \''.MWB.'bibliography/iframe_topic.php\');';
        echo '</script>';
    }
}

?>

<div class="popUpForm">
<form name="mainForm" action="pop_topic.php?biblioID=<?php echo $biblioID; ?>" method="post">
<strong><?php echo __('Add Subject'); ?></strong>
<hr />
<div class="form-inline">
    <?php
    $ajax_exp = "ajaxFillSelect('../../AJAX_vocabolary_control.php', 'mst_topic', 'topic_id:topic:topic_type', 'topicID', $('#search_str').val())";
    ?>    
    <input type="text" name="search_str" class="form-control col" id="search_str" placeholder="<?php echo __('Keyword'); ?>" oninput="<?php echo $ajax_exp; ?>" />
    <select name="type" class="form-control col"><?php
    foreach ($sysconf['subject_type'] as $type_id => $type) {
        echo '<option value="'.$type_id.'">'.$type.'</option>';
    }
    ?></select>
    <select name="level" class="form-control col">
    <?php
    echo '<option value="1">' . __('Primary') . '</option>';
    echo '<option value="2">' . __('Additional') . '</option>';
    ?>
    </select>
</div>
<div class="popUpSubForm">
    <ul id="topicID" class="form-control">
        <li><?php echo __('Type to search for existing topics or to add a new one'); ?></li>
    </ul>
    <?php if ($biblioID) { echo '<input type="hidden" name="biblioID" value="'.$biblioID.'" />'; } ?>
    <input type="submit" name="save" value="<?php echo __('Insert To Bibliography'); ?>" class="s-btn btn btn-primary popUpSubmit" />
</div>
</form>
<script type="text/javascript">

    $('#topicID').mouseover(function() {

        $('.voc').mouseover(function() {
            $(this).css({'cursor': 'pointer', 'background': '#b3e5fc'});
        })
        .mouseleave(function() {
            $(this).css('background', 'none');
        })
        .click(function() {
            var vocVal = $(this).text();
            $('#search_str').val(vocVal);
            ajaxFillSelect('../../AJAX_vocabolary_control.php', 'mst_topic', 'topic_id:topic:topic_type', 'topicID', vocVal);
        });

    });

</script>
</div>

<?php
/* main content end */
$content = ob_get_clean();
// include the page template
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
