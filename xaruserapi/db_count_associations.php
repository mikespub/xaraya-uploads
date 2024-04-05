<?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 *  Retrieve the total count associations for a particular file/module/itemtype/item combination
 *
 * @author  Carl P. Corliss
 * @access  public
 * @param   mixed   fileId    The id of the file, or an array of fileId's
 * @param   integer modid     The id of module this file is associated with
 * @param   integer itemtype  The item type within the defined module
 * @param   integer itemid    The id of the item types item

 * @return mixed             The total number of associations for particular file/module/itemtype/item combination
 *                            or an array of fileId's and their number of associations
 */

function uploads_userapi_db_count_associations(array $args = [], $context = null)
{
    extract($args);

    $whereList = [];
    $bindvars = [];

    if (isset($fileId)) {
        if (is_array($fileId)) {
            $whereList[] = ' (xar_fileEntry_id IN (' . implode(',', $fileId) . ') ) ';
            $isgrouped = 1;
        } else {
            $whereList[] = ' (xar_fileEntry_id = ?) ';
            $bindvars[] = (int) $fileId;
        }
    }

    if (isset($modid)) {
        $whereList[] = ' (xar_modid = ?) ';
        $bindvars[] = (int) $modid;

        if (isset($itemtype)) {
            $whereList[] = ' (xar_itemtype = ?) ';
            $bindvars[] = (int) $itemtype;

            if (isset($itemid)) {
                $whereList[] = ' (xar_objectid = ?) ';
                $bindvars[] = (int) $itemid;
            }
        }
    }

    if (count($whereList)) {
        $where = 'WHERE ' . implode(' AND ', $whereList);
    } else {
        $where = '';
    }

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // table and column definitions
    $file_assoc_table = $xartable['file_associations'];

    if (empty($isgrouped)) {
        $sql = "SELECT COUNT(xar_fileEntry_id) AS total
                FROM $file_assoc_table
                $where";

        $result = $dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        }

        // if no record found, return zero
        if ($result->EOF) {
            return 0;
        }

        $row = $result->GetRowAssoc(false);

        return $row['total'];
    } else {
        $sql = "SELECT xar_fileEntry_id, COUNT(*) AS total
                FROM $file_assoc_table
                $where
                GROUP BY xar_fileEntry_id";

        $result = $dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        }

        $count = [];
        while (!$result->EOF) {
            [$file, $total] = $result->fields;
            $count[$file] = $total;

            $result->MoveNext();
        }

        return $count;
    }
}
