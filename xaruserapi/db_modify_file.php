<?php

/**
 *  Modifies a file's metadata stored in the database
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer fileId    The id of the file we are modifying
 *  @param   integer userId    (optional) The id of the user whom submitted the file
 *  @param   string  filename   (optional) The name of the file (minus any path information)
 *  @param   string  fileLocation   (optional) The complete path to the file including the filename (obfuscated if so chosen)
 *  @param   integer status     (optional) The status of the file (APPROVED, SUBMITTED, READABLE, REJECTED)
 *  @param   string  mime_type  (optional) The mime content-type of the file
 *  @param   integer store_type (optional) The manner in which the file is to be stored (filesystem, database)
 *
 *  @returns integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_modify_file( $args )
{
    extract($args);

    $update_fields = array();

    if (!isset($fileId)) {
        $msg = xarML('Missing parameter [#(1)] for API function [#(2)] in module (#3)]',
                     'fileId','db_modify_file','uploads');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (isset($fileName)) {
        $update_fields[] = "xar_filename=?";
        $update_args[] = $fileName;
    }


    if (isset($fileLocation)) {
        $update_fields[] = "xar_location=?";
        $update_args[] = $fileLocation;
    }

    if (isset($userId)) {
        $update_fields[] = "xar_user_id = ?";
        $update_args[] = $userId;
    }

    if (isset($fileStatus)) {
        $update_fields[] = "xar_status = ?";
        $update_args[] = $fileStatus;
    }

    if (isset($store_type)) {
        $update_fields[] = "xar_store_type = ?";
        $update_args[] = $store_type;
    }

    if (isset($fileType)) {
        $update_fields[] = "xar_mime_type = ?";
        $update_args[] = $fileType;
    }

    if (!count($update_fields)) {
        return TRUE;
    } else {
        $update_args[] = $fileId;
    }

    //add to uploads table
    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $fileEntry_table = $xartable['file_entry'];

    $update_string   = implode(', ', $update_fields);

    $sql             = "UPDATE $fileEntry_table
                           SET $update_string
                         WHERE xar_fileEntry_id = ?";

    $result          = &$dbconn->Execute($sql, $update_args);

    if (!$result) {
        return FALSE;
    } else {
        return $dbconn->Affected_Rows();
    }

}

?>