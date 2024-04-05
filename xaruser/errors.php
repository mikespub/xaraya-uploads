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

function uploads_user_errors(array $args = [], $context = null)
{
    if (!xarSecurity::check('ViewUploads')) {
        return;
    }

    if (!xarVar::fetch('layout', 'str:1:100', $data['layout'], '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('maxallowed', 'str:1:100', $data['maxallowed'], '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('location', 'str:1:100', $data['location'], '', xarVar::NOT_REQUIRED)) {
        return;
    }

    return $data;
}
