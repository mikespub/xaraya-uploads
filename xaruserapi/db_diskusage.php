<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Uploads Module
 * @link http://xaraya.com/index.php/release/666.html
 * @author Uploads Module Development Team
 */
/**
 *  Retrieve the total size of disk usage for selected files based on the filters passed in
 *
 * @author Carl P. Corliss
 * @author Micheal Cortez
 * @access public
 * @param  integer  fileId      (Optional) grab file with the specified file id(s)
 * @param  string   fileName    (Optional) grab file(s) with the specified file name
 * @param  integer  fileStatus  (Optional) grab files with a specified status  (SUBMITTED, APPROVED, REJECTED)
 * @param  integer  userId      (Optional) grab files uploaded by a particular user
 * @param  integer  store_type  (Optional) grab files with the specified store type (FILESYSTEM, DATABASE)
 * @param  integer  fileType    (Optional) grab files with the specified mime type
 * @param  string   catid       (Optional) grab file(s) in the specified categories
 *
 * @return integer             The total amount of diskspace used by the current set of selected files
 */

function uploads_userapi_db_diskusage( $args )
{

    extract($args);

    $where = array();

    if (!isset($inverse)) {
        $inverse = FALSE;
    }

    if (isset($fileId)) {
        if (is_array($fileId)) {
            $where[] = 'xar_fileEntry_id IN (' . implode(',', $fileIds) . ')';
        } elseif (!empty($fileId)) {
            $where[] = "xar_fileEntry_id = $fileId";
        }
    }

    if (isset($fileName) && !empty($fileName)) {
        $where[] = "(xar_filename LIKE '$fileName')";
    }

    if (isset($fileStatus) && !empty($fileStatus) && is_numeric($fileStatus)) {
        $where[] = "(xar_status = $fileStatus)";
    }

    if (isset($userId) && !empty($userId) && is_numeric($userId)) {
        $where[] = "(xar_user_id = $userId)";
    }

    if (isset($store_type) && !empty($store_type) && is_numeric($store_type)) {
        $where[] = "(xar_store_type = $store_type)";
    }

    if (isseT($fileType) && !empty($fileType)) {
        $where[] = "(xar_mime_type LIKE '$fileType')";
    }

    if (count($where) > 1) {
        if ($inverse) {
            $where = 'WHERE NOT (' . implode(' OR ', $where) .')';
        } else {
            $where = 'WHERE ' . implode(' AND ', $where);
        }
    } elseif (count($where) == 1) {
        if ($inverse) {
            $where = 'WHERE NOT (' . implode('', $where) . ')';
        } else {
            $where = 'WHERE ' . implode('', $where);
        }
    } else {
        $where = '';
    }

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // table and column definitions
    $fileEntry_table = $xartable['file_entry'];

    $sql = "SELECT SUM(xar_filesize) AS disk_usage
              FROM $fileEntry_table ";

    if (!empty($catid) && xarModIsAvailable('categories') && xarModIsHooked('categories','uploads',1)) {
        // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
        $categoriesdef = xarModAPIFunc('categories','user','leftjoin',
                                      array('modid' => xarMod::getRegID('uploads'),
                                            'itemtype' => 1,
                                            'catid' => $catid));
        if (empty($categoriesdef)) return;

        // Add LEFT JOIN ... ON ... from categories_linkage
        $sql .= ' LEFT JOIN ' . $categoriesdef['table'];
        $sql .= ' ON ' . $categoriesdef['field'] . ' = ' . 'xar_fileEntry_id';
        if (!empty($categoriesdef['more'])) {
            // More LEFT JOIN ... ON ... from categories (when selecting by category)
            $sql .= $categoriesdef['more'];
        }
        if (!empty($categoriesdef['where'])) {
            if (!empty($where) && strpos($where,'WHERE') !== FALSE) {
                $where .= ' AND ' . $categoriesdef['where'];
            } else {
                $where .= ' WHERE ' . $categoriesdef['where'];
            }
        }
    }

    $sql .= " $where";

    $result = $dbconn->Execute($sql);

    if (!$result)  {
        return FALSE;
    }

    // if no record found, return an empty array
    if ($result->EOF) {
        return (integer) 0;
    }

    $row = $result->GetRowAssoc(false);

    return $row['disk_usage'];
}

?>
