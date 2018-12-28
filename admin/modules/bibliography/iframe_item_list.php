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


/* Biblio Item List */

// key to authenticate
define('INDEX_AUTH', '1');
// key to get full database access
define('DB_ACCESS', 'fa');

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');

// privileges checking
$can_write = utility::havePrivilege('bibliography', 'w');
if (!$can_write) {
  die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

// page title
$page_title = 'Item List';
// get id from url
$biblioID = 0;
if (isset($_GET['biblioID']) AND !empty($_GET['biblioID'])) {
  $biblioID = (integer)$_GET['biblioID'];
}

// start the output buffer
ob_start();
?>
<script type="text/javascript">
function confirmProcess(int_biblio_id, int_item_id)
{
  var confirmBox = confirm('<?php echo addslashes(__('Are you sure to remove selected item?'));?>' + "\n" + '<?php echo addslashes(__('Once deleted, it can\'t be restored!'));?>');
  if (confirmBox) {
    // set hidden element value
    document.hiddenActionForm.bid.value = int_biblio_id;
    document.hiddenActionForm.remove.value = int_item_id;
    // submit form
    document.hiddenActionForm.submit();
  }
}
</script>
<?php
/* main content */
if (isset($_POST['remove'])) {
  $id = (integer)$_POST['remove'];
  $bid = (integer)$_POST['bid'];
  $sql_op = new simbio_dbop($dbs);
  // check if the item still on loan
  $loan_q = $dbs->query('SELECT DISTINCT l.item_code, b.title FROM loan AS l
    LEFT JOIN item AS i ON l.item_code=i.item_code
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE i.item_id='.$id.' AND l.is_lent=1 AND l.is_return=0');
  $loan_d = $loan_q->fetch_row();
  // send an alert if the member cant be deleted
  if ($loan_q->num_rows > 0) {
      utility::jsToastr('Items', __('Item data can not be deleted because still on hold by members'), 'error');
    echo '<script type="text/javascript">';
    echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
    echo '</script>';
  } else {
    if ($sql_op->delete('item', 'item_id='.$id)) {
        utility::jsToastr('Item', __('Item succesfully removed!'), 'success');
      echo '<script type="text/javascript">';
      echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
      echo '</script>';
    } else {
        utility::jsToastr('Item', __('Item FAILED to removed!'), 'error');
      echo '<script type="text/javascript">';
      echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
      echo '</script>';
    }
  }
}

// if biblio ID is set
if ($biblioID) {
  $table = new simbio_table();
  $table->table_attr = 'align="center" class="detailTable" style="width: 100%;" cellpadding="2" cellspacing="0"';

  // database list
  $item_q = $dbs->query('SELECT i.item_id, i.item_code, b.title, i.site, loc.location_name, ct.coll_type_name, st.item_status_name FROM item AS i
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
    LEFT JOIN mst_location AS loc ON i.location_id=loc.location_id
    LEFT JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
    LEFT JOIN mst_item_status AS st ON i.item_status_id=st.item_status_id
    WHERE i.biblio_id='.$biblioID);
  if($item_q->num_rows > 0){
  $row = 1;
  while ($item_d = $item_q->fetch_assoc()) {
    // alternate the row color
    $row_class = ($row%2 == 0)?'alterCell':'alterCell2';

    // links
    $edit_link = '<a class="s-btn btn btn-default notAJAX openPopUp" href="'.MWB.'bibliography/pop_item.php?inPopUp=true&action=detail&biblioID='.$biblioID.'&itemID='.$item_d['item_id'].'" width="650" height="400" title="'.__('Items/Copies').'" style="text-decoration: underline;">' . __('Edit') . '</a>';
    $remove_link = '<a href="#" class="s-btn btn btn-danger notAJAX" onclick="javascript: confirmProcess('.$biblioID.', '.$item_d['item_id'].')">' . __('Delete') . '</a>';
    $title = $item_d['item_code'];

    $table->appendTableRow(array($edit_link, $remove_link, $title, $item_d['location_name'], $item_d['site'], $item_d['coll_type_name'], $item_d['item_status_name']));
    $table->setCellAttr($row, null, 'class="'.$row_class.'" style="width: auto;"');
    $table->setCellAttr($row, 0, 'class="'.$row_class.'" style="width: 7%;"');
    $table->setCellAttr($row, 1, 'class="'.$row_class.'" style="width: 10%;"');
    $table->setCellAttr($row, 2, 'class="'.$row_class.'" style="width: 40%;"');

    $row++;
  }
  }
  echo $table->printTable();
  // hidden form
  echo '<form name="hiddenActionForm" method="post" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="bid" value="0" /><input type="hidden" name="remove" value="0" /></form>';
}
/* main content end */
$content = ob_get_clean();
// include the page template
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
