<?php

/**
 *  Adds a file (fileEntry) entry to the database. This entry just contains metadata
 *  about the file and not the actual DATA (contents) of the file.
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer userId         The id of the user whom submitted the file
 *  @param   string  fileName       The name of the file (minus any path information)
 *  @param   string  fileLocation   The complete path to the file including the filename (obfuscated if so chosen)
 *  @param   string  fileType       The mime content-type of the file
 *  @param   integer fileStatus     The status of the file (APPROVED, SUBMITTED, READABLE, REJECTED)
 *  @param   integer store_type     The manner in which the file is to be stored (filesystem, database)
 *
 *  @returns integer The id of the fileEntry that was added, or FALSE on error
 */

function uploads_userapi_db_add_file( $args )
{

    extract($args);

    if (!isset($fileName)) {
        $msg = xarML('Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                     'filename','db_add_file','uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (!isset($fileLocation)) {
        $msg = xarML('Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                     'fileLocation','db_add_file','uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (!isset($userId)) {
        $userId = xarSessionGetVar('uid');
    }

    if (!isset($fileStatus)) {
        $autoApprove = xarModGetVar('uploads', 'file.auto-approve');

        if ($autoApprove == _UPLOADS_APPROVE_EVERYONE ||
           ($autoApprove == _UPLOADS_APPROVE_ADMIN && xarSecurityCheck('AdminUploads', 0))) {
                $fileStatus = _UPLOADS_STATUS_APPROVED;
        } else {
            $fileStatus = _UPLOADS_STATUS_SUBMITTED;
        }
    }

    if (!isset($fileSize)) {
        $fileSize = 0;
    } else {
        // FIXME: only normalize the filesize before it's passed to a template
        //        otherwise, keep it as an integer <rabbitt>
        if (is_array($fileSize)) {
            if (stristr($fileSize['long'], ',')) {
                $fileSize = str_replace(',', '', $fileSize['long']);
            } else {
                $fileSize = $fileSize['long'];
            }
        }
    }

    if (!isset($store_type)) {
        $store_type = _UPLOADS_STORE_FILESYSTEM;
    }

    if (!isset($fileType)) {
        $fileType = xarModAPIFunc('mime','user','analyze_file', array('fileName' => $fileLocation, 'altFileName'=>$fileName));
        if (empty($fileType)) {
            $fileType = 'application/octet-stream';
        }
    }

    //add to uploads table
    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();


    // table and column definitions
    $fileEntry_table = $xartable['file_entry'];
    $file_id    = $dbconn->GenID($fileEntry_table);

    // insert value into table
    $sql = "INSERT INTO $fileEntry_table
                      (
                        xar_fileEntry_id,
                        xar_user_id,
                        xar_filename,
                        xar_location,
                        xar_status,
                        xar_filesize,
                        xar_store_type,
                        xar_mime_type
                      )
               VALUES
                      (
                        $file_id,
                        $userId,'" .
                        xarVarPrepForStore($fileName) . "', '" .
                        xarVarPrepForStore($fileLocation) . "',
                        $fileStatus,
                        $fileSize,
                        $store_type, '" .
                        xarVarPrepForStore($fileType) . "'
                      )";

    $result = &$dbconn->Execute($sql);

    if (!$result) {
        return FALSE;
    } else {
        return $dbconn->PO_Insert_ID($xartable['file_entry'], 'xar_fileEntry_id');
    }
}

?>
