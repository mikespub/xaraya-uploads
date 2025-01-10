<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\MethodClass;
use xarMod;
use xarSecurity;
use xarModVars;
use xarSec;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin modifyconfig function
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration for the Uploads module
     */
    public function __invoke(array $args = [])
    {
        xarMod::apiLoad('uploads', 'user');

        // Security check
        if (!xarSecurity::check('AdminUploads')) {
            return;
        }

        // Generate a one-time authorisation code for this operation

        // get the current module variables for display
        // *********************************************
        // Global
        $data['file']['maxsize']                = number_format(xarModVars::get('uploads', 'file.maxsize'));
        $data['file']['delete-confirmation']    = xarModVars::get('uploads', 'file.delete-confirmation');
        $data['file']['auto-purge']             = xarModVars::get('uploads', 'file.auto-purge');
        $data['file']['auto-approve']           = xarModVars::get('uploads', 'file.auto-approve');
        $data['file']['obfuscate-on-import']    = xarModVars::get('uploads', 'file.obfuscate-on-import');
        $data['file']['obfuscate-on-upload']    = xarModVars::get('uploads', 'file.obfuscate-on-upload');
        $data['file']['cache-expire']           = xarModVars::get('uploads', 'file.cache-expire');
        if (!isset($data['file']['cache-expire'])) {
            xarModVars::set('uploads', 'file.cache-expire', 0);
        }
        $data['file']['allow-duplicate-upload'] = xarModVars::get('uploads', 'file.allow-duplicate-upload');
        if (!isset($data['file']['allow-duplicate-upload'])) {
            xarModVars::set('uploads', 'file.allow-duplicate-upload', 0);
            $data['file']['allow-duplicate-upload'] = 0;
        }
        $data['ddprop']['trusted']              = xarModVars::get('uploads', 'dd.fileupload.trusted');
        $data['ddprop']['external']             = xarModVars::get('uploads', 'dd.fileupload.external');
        $data['ddprop']['stored']               = xarModVars::get('uploads', 'dd.fileupload.stored');
        $data['ddprop']['upload']               = xarModVars::get('uploads', 'dd.fileupload.upload');
        $data['authid']                         = xarSec::genAuthKey();

        $data['approveList']['noone']      = _UPLOADS_APPROVE_NOONE;
        $data['approveList']['admin']      = _UPLOADS_APPROVE_ADMIN;
        $data['approveList']['everyone']   = _UPLOADS_APPROVE_EVERYONE;

        if ($data['file']['auto-approve'] != _UPLOADS_APPROVE_NOONE &&
            $data['file']['auto-approve'] != _UPLOADS_APPROVE_ADMIN &&
            $data['file']['auto-approve'] != _UPLOADS_APPROVE_EVERYONE) {
            $data['file']['auto-approve'] = _UPLOADS_APPROVE_NOONE;
        }

        $hooks = xarModHooks::call(
            'module',
            'modifyconfig',
            'uploads',
            ['module'   => 'uploads',
                'itemtype' => 1, ]
        ); // Files

        if (empty($hooks)) {
            $data['hooks'] = [];
        } else {
            $data['hooks'] = $hooks;
        }

        // Check the validaty of directories
        $location = xarMod::apiFunc('uploads', 'user', 'db_get_dir', ['directory' => 'uploads_directory']);
        $data['uploads_directory_message'] = "";
        if (!file_exists($location) || !is_dir($location)) {
            $data['uploads_directory_message'] = xarML('Not a valid directory');
        } elseif (!is_writable($location)) {
            $data['uploads_directory_message'] = xarML('Not a writable directory');
        }

        $location = xarMod::apiFunc('uploads', 'user', 'db_get_dir', ['directory' => 'imports_directory']);
        $data['imports_directory_message'] = "";
        if (!file_exists($location) || !is_dir($location)) {
            $data['imports_directory_message'] = xarML('Not a valid directory');
        } elseif (!is_writable($location)) {
            $data['imports_directory_message'] = xarML('Not a writable directory');
        }

        // Define the module settings
        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'uploads']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons');
        $data['module_settings']->getItem();

        // Return the template variables defined in this function
        return $data;
    }
}
