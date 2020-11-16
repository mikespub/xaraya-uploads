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

xarModAPILoad('uploads', 'user');
/**
 * The view function for the site admin
 *
 * @param int mimetype
 * @param int subtype
 * @param int status
 * @param bool inverse
 * @param int fileId
 * @param string fileDo
 * @param int action
 * @param int startnum
 * @param int numitems
 * @param string sort
 * @param string catid
 * @return array
 */
function uploads_admin_view()
{
    //security check
    if (!xarSecurityCheck('AdminUploads')) {
        return;
    }

    /**
     *  Validate variables passed back
     */

    if (!xarVarFetch('mimetype', 'int:0:', $mimetype, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('subtype', 'int:0:', $subtype, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('status', 'int:0:', $status, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('inverse', 'checkbox', $inverse, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('fileId', 'list:int:1', $fileId, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('fileDo', 'str:5:', $fileDo, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('action', 'int:0:', $action, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('startnum', 'int:0:', $startnum, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('numitems', 'int:0:', $numitems, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('sort', 'enum:id:name:size:user:status', $sort, null, XARVAR_DONT_SET)) {
        return;
    }
    if (!xarVarFetch('catid', 'str:1:', $catid, null, XARVAR_DONT_SET)) {
        return;
    }

    /**
     *  Determine the filter settings to use for this view
     */
    if (!isset($mimetype) || !isset($subtype) || !isset($status) || !isset($inverse)) {
        // if the filter settings are empty, then
        // grab the users last view filter
        $options  = unserialize(xarModUserVars::get('uploads', 'view.filter'));
        $data     = $options['data'];
        $filter   = $options['filter'];
        unset($options);
    } else {
        // otherwise, grab whatever filter options where passed in
        // and process them to create a filter
        $filters['mimetype'] = $mimetype;
        $filters['subtype']  = $subtype;
        $filters['status']   = $status;
        $filters['inverse']  = $inverse;
        $filters['catid']    = $catid;

        $options  =  xarModAPIFunc('uploads', 'user', 'process_filters', $filters);
        $data     = $options['data'];
        $filter   = $options['filter'];
        unset($options);
    }
    // Override catid when we're not simply changing the sort order
    if (!isset($sort)) {
        $filter['catid'] = $catid;
        $data['catid']   = $catid;
    }

    /**
     * Perform all actions
     */

    if (isset($action)) {
        if ($action > 0) {
            if (isset($fileDo)) {
                // If we got a signal to change status but no list of files to change,
                // then do nothing
                if (isset($fileId) && !empty($fileId)) {
                    $args['fileId']     = $fileId;
                } else {
                    $action = 0;
                }
            } else {
                $args['fileType']   = $filter['fileType'];
                $args['inverse']    = (isset($inverse) ? $inverse : false);
                $args['curStatus']  = $filter['fileStatus'];
            }
        }

        switch ($action) {
            case _UPLOADS_STATUS_APPROVED:
                    xarModAPIFunc('uploads', 'user', 'db_change_status', $args + array('newStatus'    => _UPLOADS_STATUS_APPROVED));
                    break;
            case _UPLOADS_STATUS_SUBMITTED:
                    xarModAPIFunc('uploads', 'user', 'db_change_status', $args + array('newStatus'    => _UPLOADS_STATUS_SUBMITTED));
                    break;
            case _UPLOADS_STATUS_REJECTED:
                xarModAPIFunc('uploads', 'user', 'db_change_status', $args + array('newStatus'   => _UPLOADS_STATUS_REJECTED));
                if (xarModVars::get('uploads', 'file.auto-purge')) {
                    if (xarModVars::get('uploads', 'file.delete-confirmation')) {
                        return xarModFunc('uploads', 'admin', 'purge_rejected', array('confirmation' => false, 'authid' => xarSecGenAuthKey('uploads')));
                    } else {
                        return xarModFunc('uploads', 'admin', 'purge_rejected', array('confirmation' => true, 'authid' => xarSecGenAuthKey('uploads')));
                    }
                }
                break;
            case 0: /* Change View or anything not defined */
            default:
                break;
        }
    }

    /**
     * Grab a list of files based on the defined filter
     */

    if (!isset($numitems)) {
        $numitems = xarModVars::get('uploads', 'view.itemsperpage');
        $skipnum = 1;
    }

    if (!isset($filter) || count($filter) <= 0) {
        $filter = array();
        $filter['startnum'] = $startnum;
        $filter['numitems'] = $numitems;
        $filter['sort']     = $sort;
        $items = xarModAPIFunc('uploads', 'user', 'db_getall_files', $filter);
    } else {
        $filter['startnum'] = $startnum;
        $filter['numitems'] = $numitems;
        $filter['sort']     = $sort;
        $items = xarModAPIFunc('uploads', 'user', 'db_get_file', $filter);
    }
    $countitems = xarModAPIfunc('uploads', 'user', 'db_count', $filter);

    if (!empty($items)) {
        $data['numassoc'] = xarModAPIFunc(
            'uploads',
            'user',
            'db_count_associations',
            array('fileId' => array_keys($items))
        );
    }

    if (xarSecurityCheck('EditUploads', 0)) {
        $data['diskUsage']['stored_size_filtered'] = xarModAPIFunc('uploads', 'user', 'db_diskusage', $filter);
        $data['diskUsage']['stored_size_total']    = xarModAPIFunc('uploads', 'user', 'db_diskusage');

        $data['uploadsdir'] = xarMod::apiFunc('uploads', 'user', 'db_get_dir', array('directory' => 'uploads_directory'));
        $data['diskUsage']['device_free']  = @disk_free_space($data['uploadsdir']);
        $data['diskUsage']['device_total'] = @disk_total_space($data['uploadsdir']);
        $data['diskUsage']['device_used']  = $data['diskUsage']['device_total'] - $data['diskUsage']['device_free'];

        foreach ($data['diskUsage'] as $key => $value) {
            $data['diskUsage'][$key] = xarModAPIFunc('uploads', 'user', 'normalize_filesize', $value);
        }

        $data['diskUsage']['numfiles_filtered']   = $countitems;
        $data['diskUsage']['numfiles_total']      = xarModAPIFunc('uploads', 'user', 'db_count');
    }
    // now we check to see if the user has enough access to view
    // each particular file - if not, we just silently remove it
    // from the view
    foreach ($items as $key => $fileInfo) {
        unset($instance);
        $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
        $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
        $instance[2] = xarSession::getVar('uid');
        $instance[3] = $fileInfo['fileId'];

        if (is_array($instance)) {
            $instance = implode(':', $instance);
        }
        if (!xarSecurityCheck('EditUploads', 0, 'File', $instance)) {
            unset($items[$key]);
        }
    }


    /**
     *  Check for exceptions
     */
    if (!isset($items) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    } // throw back

    $data['items'] = $items;
    $data['authid'] = xarSecGenAuthKey();

    // Add pager
    if (!empty($numitems) && $countitems > $numitems) {
        sys::import('modules.base.class.pager');
        $data['pager'] = xarTplPager::getPager(
            $startnum,
            $countitems,
            xarModURL(
                                            'uploads',
                                            'admin',
                                            'view',
                                            array('startnum' => '%%',
                                                        'numitems' => (empty($skipnum) ? $numitems : null),
                                                        'sort'     => $sort)
                                        ),
            $numitems
        );
    } else {
        $data['pager'] = '';
    }

    return $data;
}
