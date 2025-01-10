<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\MethodClass;
use xarSession;
use xarModVars;
use xarSecurity;
use xarMod;
use xarDB;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_add_file function
 */
class DbAddFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Adds a file (fileEntry) entry to the database. This entry just contains metadata
     *  about the file and not the actual DATA (contents) of the file.
     *  @author  Carl P. Corliss
     * @access public
     * @param   integer userId         The id of the user whom submitted the file
     * @param   string  fileName       The name of the file (minus any path information)
     * @param   string  fileLocation   The complete path to the file including the filename (obfuscated if so chosen)
     * @param   string  fileType       The mime content-type of the file
     * @param   integer fileStatus     The status of the file (APPROVED, SUBMITTED, READABLE, REJECTED)
     * @param   integer store_type     The manner in which the file is to be stored (filesystem, database)
     * @param   array   extrainfo      Extra information to be stored for this file (e.g. modified, width, height, ...)
     *
     * @return integer The id of the fileEntry that was added, or FALSE on error
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileName)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'filename',
                'db_add_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileLocation)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileLocation',
                'db_add_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($userId)) {
            $userId = xarSession::getVar('role_id');
        }

        if (!isset($fileStatus)) {
            $autoApprove = xarModVars::get('uploads', 'file.auto-approve');

            if ($autoApprove == _UPLOADS_APPROVE_EVERYONE ||
               ($autoApprove == _UPLOADS_APPROVE_ADMIN && xarSecurity::check('AdminUploads', 0))) {
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
            $fileType = xarMod::apiFunc('mime', 'user', 'analyze_file', ['fileName' => $fileLocation, 'altFileName' => $fileName]);
            if (empty($fileType)) {
                $fileType = 'application/octet-stream';
            }
        }

        if (empty($extrainfo)) {
            $extrainfo = '';
        } elseif (is_array($extrainfo)) {
            $extrainfo = serialize($extrainfo);
        }

        //add to uploads table
        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();


        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];
        $file_id    = $dbconn->genID($fileEntry_table);

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
                            xar_mime_type,
                            xar_extrainfo
                          )
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $bindvars = [ $file_id,
            (int) $userId,
            (string) $fileName,
            (string) $fileLocation,
            (int) $fileStatus,
            (int) $fileSize,
            (int) $store_type,
            (string) $fileType,
            (string) $extrainfo, ];

        $result = &$dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        }

        $fileId = $dbconn->PO_Insert_ID($xartable['file_entry'], 'xar_fileEntry_id');

        // Pass the arguments to the hook modules too
        $args['module'] = 'uploads';
        $args['itemtype'] = 1; // Files
        xarModHooks::call('item', 'create', $fileId, $args);

        return $fileId;
    }
}
