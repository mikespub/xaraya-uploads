<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Uploads Module
 * @link http://xaraya.com/index.php/release/666.html
 * @author Uploads Module Development Team
 */
/**
 *  Delete a file from the filesystem
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   string fileName    The complete path to the file being deleted
 *
 *  @return TRUE on success, FALSE on error
 */

function uploads_userapi_file_delete( $args )
{

    extract ($args);

    if (!isset($fileName)) {
        $msg = xarML('Missing parameter [#(1)] for function [(#(2)] in module [#(3)]',
                     'fileName','file_move','uploads');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (!file_exists($fileName)) {
        // if the file doesn't exist, then we don't need
        // to worry about deleting it - so return true :)
        return TRUE;
    }

    if (!unlink($fileName)) {
        $msg = xarML('Unable to remove file: [#(1)].', $fileName);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FILE_NO_DELETE', new SystemException($msg));
        return FALSE;
    }

    return TRUE;
}

?>