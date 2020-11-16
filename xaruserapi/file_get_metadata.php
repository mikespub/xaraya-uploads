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
 *  Retrieves metadata on a file from the filesystem
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   string   fileLocation  The location of the file on in the filesystem
 *  @param   boolean  normalize     Whether or not to
 *  @param   boolean  analyze       Whether or not to
 *  @return array                  array containing the inodeType, fileSize, fileType, fileLocation, fileName
 *
 */

function uploads_userapi_file_get_metadata($args)
{
    extract($args);

    if (!isset($normalize)) {
        $normalize = false;
    }

    if (!isset($analyze)) {
        $analyze = true;
    }

    if (isset($fileLocation) && !empty($fileLocation) && file_exists($fileLocation)) {
        $file =& $fileLocation;
        if (is_dir($file)) {
            $type = _INODE_TYPE_DIRECTORY;
            $size = 'N/A';
            $mime = 'filesystem/directory';
        } elseif (is_file($file)) {
            $type = _INODE_TYPE_FILE;
            $size = filesize($file);
            if ($analyze) {
                $mime = xarModAPIFunc('mime', 'user', 'analyze_file', array('fileName' => $file));
            } else {
                $mime = 'application/octet';
            }
        } else {
            $type = _INODE_TYPE_UNKNOWN;
            $size = 0;
            $mime = 'application/octet';
        }

        $name = basename($file);

        if ($normalize) {
            $size = xarModAPIFunc('uploads', 'user', 'normalize_filesize', $size);
        }

        // CHECKME: use 'imports' name like in db_get_file() ?
        $relative_path = str_replace(xarModVars::get('uploads', 'imports_directory'), '/trusted', $file);

        $fileInfo = array('inodeType'    => $type,
                          'fileName'     => $name,
                          'fileLocation' => $file,
                          'relativePath' => $relative_path,
                          'fileType'     => $mime,
                          'fileSize'     => $size);

        return $fileInfo;
    } else {
        // TODO: exception
        return false;
    }
}
