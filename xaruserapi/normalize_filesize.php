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

function uploads_userapi_normalize_filesize($args)
{
    if (is_array($args)) {
        extract($args);
    } elseif (is_numeric($args)) {
        $fileSize = $args;
    } else {
        return array('long' => 0, 'short' => 0);
    }

    $size = $fileSize;

    $range = array('', 'KB', 'MB', 'GB', 'TB', 'PB');

    for ($i = 0; $size >= 1024 && $i < count($range); $i++) {
        $size /= 1024;
    }

    $short = round($size, 2).' '.$range[$i];

    return array('long' => number_format($fileSize), 'short' => $short);
}
