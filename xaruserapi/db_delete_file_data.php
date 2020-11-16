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
 *  Remove a file's data contents from the database. This just removes any data (contents)
 *  that we might have in store for this file. The actual metadata (FILE ENTRY) for the file
 *  itself is removed via db_delete_file() .
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer fileId    The id of the file who's contents we are removing
 *
 *  @return integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_delete_file_data($args)
{
    extract($args);

    if (!isset($fileId)) {
        $msg = xarML(
            'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
            'fileId',
            'db_delete_file_data',
            'uploads'
        );
        throw new Exception($msg);
    }

    //add to uploads table
    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // table and column definitions
    $fileData_table   = $xartable['file_data'];

    // insert value into table
    $sql = "DELETE
              FROM $fileData_table
             WHERE xar_fileEntry_id = $fileId";


    $result = &$dbconn->Execute($sql);

    if (!$result) {
        return false;
    } else {
        return $dbconn->Affected_Rows();
    }
}
