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
 *  Retrieve a list of (item - file) associations for a particular module/itemtype combination
 *
 * @author Carl P. Corliss
 * @access public
 * @param   integer modid     The id of module this file is associated with
 * @param   integer itemtype  The item type within the defined module
 * @param   integer itemid    The id of the item types item
 * @param   integer fileId    The id of the file we are going to associate with an item
 *
 * @return array   A list of associations, including the itemid -> fileId
 */

function uploads_userapi_db_list_associations($args)
{
    extract($args);

    if (empty($modid)) {
        return [];
    }

    $whereList = [];
    $bindvars = [];

    if (isset($fileId)) {
        $whereList[] = ' (xar_fileEntry_id = ?) ';
        $bindvars[] = (int) $fileId;
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

    $sql = "SELECT
                    xar_modid,
                    xar_itemtype,
                    xar_objectid,
                    xar_fileEntry_id
            FROM $file_assoc_table
            $where
            ORDER BY xar_objectid ASC";

    if (!empty($numitems)) {
        if (empty($startnum)) {
            $startnum = 1;
        }
        $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1, $bindvars);
    } else {
        $result = $dbconn->Execute($sql, $bindvars);
    }

    if (!$result) {
        return [];
    }

    // if no record found, return an empty array
    if ($result->EOF) {
        return [];
    }

    $list = [];
    while (!$result->EOF) {
        [$modid, $itemtype, $itemid, $fileId] = $result->fields;
        // simple item - file array
        if (!isset($list[$itemid])) {
            $list[$itemid] = [];
        }
        $list[$itemid][] = (int) $fileId;
        $result->MoveNext();
    }
    return $list;
}
