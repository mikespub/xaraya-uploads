<?php

/**
 *  Retrieve the filename for a particular file based on the file id
 *
 * @author Carl P. Corliss
 * @access public
 * @param  integer  fileId     (Optional) grab file with the specified file id
 *
 * @returns array   All of the metadata stored for the particular file
 */

function uploads_userapi_db_get_filename( $args )
{

    extract($args);

    if (!isset($fileId)) {
        $msg = xarML('Missing [#(1)] parameter for function [#(2)] in module [#(3)]', 'fileId', 'db_get_filename', 'uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (isset($fileId)) {
        if (is_array($fileId)) {
            $where = 'xar_fileEntry_id IN (' . implode(',', $fileId) . ')';
        } elseif (!empty($fileId)) {
            $where = "xar_fileEntry_id = $fileId";
        }
    }

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

        // table and column definitions
    $fileEntry_table = $xartable['file_entry'];

    $sql = "SELECT xar_filename
              FROM $fileEntry_table
             WHERE $where";

    $result = $dbconn->Execute($sql);

    if (!$result)  {
        return;
    }

    // if no record found, return an empty array
    if ($result->EOF) {
        return '';
    }

    while (!$result->EOF) {
        $row = $result->GetRowAssoc(false);
        $fileName = $row['xar_filename'];
        $result->MoveNext();
    }
    return $fileName;
}

?>