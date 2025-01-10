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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_dump function
 */
class FileDumpMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Dump a files contents into the database.
     *  @author  Carl P. corliss
     * @access public
     * @param   string  fileSrc   The location of the file whose contents we want to dump into the database
     * @param   integer fileId    The file entry id of the file's meta data in the database
     * returns  integer           The total bytes stored or boolean FALSE on error
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($unlink)) {
            $unlink = true;
        }
        if (!isset($fileSrc)) {
            $msg = xarML(
                'Missing parameter [#(1)] for API function [#(2)] in module [#(3)].',
                'fileSrc',
                'file_dump',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileId)) {
            $msg = xarML(
                'Missing parameter [#(1)] for API function [#(2)] in module [#(3)].',
                'fileId',
                'file_dump',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!file_exists($fileSrc)) {
            $msg = xarML('Unable to locate file [#(1)]. Are you sure it\'s there??', $fileSrc);
            throw new Exception($msg);
        }

        if (!is_readable($fileSrc) || !is_writable($fileSrc)) {
            $msg = xarML('Cannot read and/or write to file [#(1)]. File will be read from and deleted afterwards. Please ensure that this application has sufficient access to do so.', $fileSrc);
            throw new Exception($msg);
        }

        $fileInfo = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $fileId]);
        $fileInfo = end($fileInfo);

        if (!count($fileInfo) || empty($fileInfo)) {
            $msg = xarML(
                'FileId [#(1)] does not exist. File [#(2)] does not have a corresponding metadata entry in the databsae.',
                $fileId,
                $fileSrc
            );
            throw new Exception($msg);
        } else {
            $dataBlocks = xarMod::apiFunc('uploads', 'user', 'db_count_data', ['fileId' => $fileId]);

            if ($dataBlocks > 0) {
                // we don't support non-truncated overwrites nor appends
                // so truncate the file and then save it
                if (!xarMod::apiFunc('uploads', 'user', 'db_delete_file_data', ['fileId' => $fileId])) {
                    $msg = xarML('Unable to truncate file [#(1)] in database.', $fileInfo['fileName']);
                    throw new Exception($msg);
                }
            }

            // Now we copy the contents of the file into the database
            if (($srcId = fopen($fileSrc, 'rb')) !== false) {
                do {
                    // Read 16K in at a time
                    $data = fread($srcId, (64 * 1024));
                    if (0 == strlen($data)) {
                        fclose($srcId);
                        break;
                    }
                    if (!xarMod::apiFunc('uploads', 'user', 'db_add_file_data', ['fileId' => $fileId, 'fileData' => $data])) {
                        // there was an error, so close the input file and delete any blocks
                        // we may have written, unlink the file (if specified), and return an exception
                        fclose($srcId);
                        if ($unlink) {
                            @unlink($fileSrc); // fail silently
                        }
                        xarMod::apiFunc('uploads', 'user', 'db_delete_file_data', ['fileId' => $fileId]);
                        $msg = xarML('Unable to save file contents to database.');
                        throw new Exception($msg);
                    }
                } while (true);
            } else {
                $msg = xarML('Cannot read and/or write to file [#(1)]. File will be read from and deleted afterwards. Please ensure that this application has sufficient access to do so.', $fileSrc);
                throw new Exception($msg);
            }
        }

        if ($unlink) {
            @unlink($fileSrc);
        }
        return true;
    }
}
