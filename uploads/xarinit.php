<?php

// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marie Altobelli (Ladyofdragons)
// Current Maintainer: Michael Cortez (mcortez)
// Purpose of file:  Initialisation functions for uploads
// ----------------------------------------------------------------------

/**
 * initialise the module
 */
function uploads_init()
{
    if(xarServerGetVar('PATH_TRANSLATED')) {
        $base_directory = dirname(realpath(xarServerGetVar('PATH_TRANSLATED')));
    } elseif(xarServerGetVar('SCRIPT_FILENAME')) {
        $base_directory = dirname(realpath(xarServerGetVar('SCRIPT_FILENAME')));
    } else {
        $base_directory = './';
    }
    xarModSetVar('uploads', 'path.uploads-directory',   $base_directory . 'var/uploads');
    xarModSetVar('uploads', 'path.imports-directory',   $base_directory . 'var/imports');
    xarModSetVar('uploads', 'file.maxsize',             '10000000');
    xarModSetVar('uploads', 'file.censored-mimetypes',   serialize(array()));
    xarModSetVar('uploads', 'file.delete-confirmation',  TRUE);
    xarModSetVar('uploads', 'file.obfuscate-on-import',  FALSE);
    xarModSetVar('uploads', 'file.obfuscate-on-upload',  TRUE);
        
    // Get datbase setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    
    $fileEntry_table = $xartable['file_entry'];
    $fileData_table = $xartable['file_data'];

    xarDBLoadTableMaintenanceAPI();
    $fileEntry_fields = array(
        'xar_fileEntry_id' => array('type'=>'integer', 'size'=>'big',      'null'=>FALSE,  'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_itemtype_id'  => array('type'=>'integer', 'size'=>'big',     'null'=>FALSE),
        'xar_user_id'      => array('type'=>'integer', 'size'=>'big',     'null'=>FALSE),
        'xar_filename'     => array('type'=>'varchar', 'size'=>254,    'null'=>FALSE),
        'xar_location'     => array('type'=>'varchar', 'size'=>254,    'null'=>FALSE),
        'xar_status'       => array('type'=>'integer', 'size'=>'tiny',      'null'=>FALSE,  'default'=>'0'),
        'xar_filesize'     => array('type'=>'integer', 'size'=>64,     'null'=>FALSE),
        'xar_store_type'   => array('type'=>'varchar', 'size'=>1,      'null'=>FALSE),
        'xar_mime_type'    => array('type'=>'varchar', 'size' =>128,  'null'=>FALSE,  'default' => 'application/octet-stream')
    );
        
        
    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $query   =  xarDBCreateTable($fileEntry_table, $fileEntry_fields);
    $result  =& $dbconn->Execute($query);
    if (!$result) return;

    $fileData_fields = array(
        'xar_fileData_id'  => array('type'=>'integer','size'=>'big','null'=>FALSE,'increment'=>TRUE, 'primary_key'=>TRUE),
        'xar_fileEntry_id' => array('type'=>'integer','size'=>'big','null'=>FALSE),
        'xar_fileData'     => array('type'=>'blob','size'=>'medium','null'=>FALSE)
    );
        
    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $query  =  xarDBCreateTable($fileData_table, $fileData_fields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    
    xarRegisterMask('ViewUploads','All','uploads','Upload','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadUploads','All','uploads','Upload','All','ACCESS_READ');
    xarRegisterMask('EditUploads','All','uploads','Upload','All','ACCESS_EDIT');
    xarRegisterMask('AdminUploads','All','uploads','Upload','All','ACCESS_ADMIN');
    

    /**
     * Register hooks
     */
    if (!xarModRegisterHook('item', 'transform', 'API',
                           'uploads', 'user', 'transformhook')) {
         $msg = xarML('Could not register hook');
         xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
         return;
    }
    
    return true;
}

/**
 * upgrade the uploads module from an old version
 */
/**
 * upgrade the articles module from an old version
 */
function uploads_upgrade($oldversion)
{
    echo "<br />OLDVERSION: $oldversion";
    
    // Upgrade dependent on old version number
    switch($oldversion) {
        case .01:
        case .02:
            // change newhook from API to GUI
            list($dbconn) = xarDBGetConn();

            $hookstable = xarDBGetSiteTablePrefix() . '_hooks';
            $query = "UPDATE $hookstable
                      SET xar_tarea='GUI'
                      WHERE xar_tmodule='uploads' AND xar_tfunc='newhook'";

            $result =& $dbconn->Execute($query);
            if (!$result) return;
        case .03:
            // Remove unused hooks
            xarModUnregisterHook('item', 'new', 'GUI','uploads', 'admin', 'newhook');
            xarModUnregisterHook('item', 'create', 'API', 'uploads', 'admin', 'createhook');
            xarModUnregisterHook('item', 'display', 'GUI', 'uploads', 'user', 'formdisplay');
            
            
            // Had problems with unregister not working in beta testing... So forcefully removing these
            list($dbconn) = xarDBGetConn();
        
            $hookstable = xarDBGetSiteTablePrefix() . '_hooks';
            $query = "DELETE FROM $hookstable
                            WHERE xar_tmodule='uploads' 
                              AND (xar_tfunc='formdisplay' 
                               OR xar_tfunc='createhook' 
                               OR xar_tfunc='newhook')";
        
            $result =& $dbconn->Execute($query);
            if (!$result) return;
            
            break;
        case .04:
        case .05:
            //Add mimetype column to DB
//            ALTER TABLE `xar_uploads` ADD `ulmime` VARCHAR( 128 ) DEFAULT 'application/octet-stream' NOT NULL ;

            // Get database information
            list($dbconn) = xarDBGetConn();
            $xartable = xarDBGetTables();
            $linkagetable = $xartable['uploads'];

            xarDBLoadTableMaintenanceAPI();

            // add the xar_itemtype column
            $query = xarDBAlterTable($linkagetable,
                                     array('command' => 'add',
                                           'field' => 'xar_ulmime',
                                           'type' => 'varchar',
                                           'size' => 128,
                                           'null' => false,
                                           'default' => 'application/octet-stream'));
            $result = &$dbconn->Execute($query);
            if (!$result) return;
        case .10: 
        case .75:
        
            xarModAPILoad('uploads','user');
            xarDBLoadTableMaintenanceAPI();
            
            list($dbconn)        = xarDBGetConn();
            $xartables           = xarDBGetTables();
            
            $uploads_table       = xarDBGetSiteTablePrefix() . "_uploads";
            $uploads_blobs_table = xarDBGetSiteTablePrefix() . "_uploadblobs";
 
            $file_entry_table    = $xartables['file_entry'];
            $file_data_table     = $xartables['file_data'];
            
            
            // Grab all the file entries from the db
            $query = "SELECT xar_ulid,
                             xar_uluid,
                             xar_ulfile,
                             xar_ulhash,
                             xar_ulapp,
                             xar_ultype,
                             xar_ulmime
                        FROM $uploads_table";
            
            $result  =& $dbconn->Execute($query);
            if (!$result)  
                return;
            
            $fileEntries = array();
            
            while (!$result->EOF) {
                $row = $result->GetRowAssoc(false);
                $entry['xar_fileEntry_id']  = $row['xar_ulid'];
                $entry['xar_user_id']       = $row['xar_uluid'];
                $entry['xar_filename']      = $row['xar_ulfile'];
                $entry['xar_location']      = $row['xar_ulhash'];
                $entry['xar_status']        = ($row['xar_ulapp']) ? _UPLOADS_STATUS_APPROVED : _UPLOADS_STATUS_SUBMITTED;
                $entry['xar_filesize']      = @filesize($row['xar_ulhash']) ? filesize($row['xar_ulhash']) : 0;
                
                switch(strtolower($row['xar_ultype'])) {
                    case 'd':   
                                $entry['xar_store_type'] = _UPLOADS_STORE_DATABASE;
                                break;
                    default:
                    case 'f': 
                                $entry['xar_store_type'] = _UPLOADS_STORE_FILESYSTEM;
                                break;  
                }
                $entry['xar_mime_type']     = xarModAPIFunc('mime','user','analyze_file', array('fileName' => $row['xar_ulhash']));
                $fileEntries[] = $entry;
                $result->MoveNext();
            }
            
            // Create the new tables
            $fileEntry_fields = array(
                'xar_fileEntry_id' => array('type'=>'integer', 'size'=>'big',      'null'=>FALSE,  'increment'=>TRUE,'primary_key'=>TRUE),
                'xar_user_id'      => array('type'=>'integer', 'size'=>'big',     'null'=>FALSE),
                'xar_filename'     => array('type'=>'varchar', 'size'=>254,    'null'=>FALSE),
                'xar_location'     => array('type'=>'varchar', 'size'=>254,    'null'=>FALSE),
                'xar_status'       => array('type'=>'integer', 'size'=>'tiny',      'null'=>FALSE,  'default'=>'0'),
                'xar_filesize'     => array('type'=>'integer', 'size'=>64,     'null'=>FALSE),
                'xar_store_type'   => array('type'=>'varchar', 'size'=>1,      'null'=>FALSE),
                'xar_mime_type'    => array('type'=>'varchar', 'size' =>128,  'null'=>FALSE,  'default' => 'application/octet-stream')
            );


            // Create the Table - the function will return the SQL is successful or
            // raise an exception if it fails, in this case $sql is empty
            $query   =  xarDBCreateTable($file_entry_table, $fileEntry_fields);
            $result  =& $dbconn->Execute($query);
            if (!$result) { 
                // if there was an error, make sure to remove the table 
                // so the user can try the upgrade again
                xarDBDropTable($file_entry_table);
                return;
            }
            
            // Add files to new database
            foreach ($fileEntries as $fileEntry) {
                $query = "INSERT INTO $file_entry_table
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
                                      $fileEntry[xar_fileEntry_id],
                                      $fileEntry[xar_user_id],
                                     '$fileEntry[xar_filename]',
                                     '$fileEntry[xar_location]',
                                      $fileEntry[xar_status],
                                      $fileEntry[xar_filesize],
                                      $fileEntry[xar_store_type],
                                     '$fileEntry[xar_mime_type]'
                                    )";
                $result =& $dbconn->Execute($query);
                if (!$result) {
                    $query = xarDBDropTable($file_entry_table);
                    $result =& $dbconn->Execute($query);
                    return;
                }
            }
            
            $fileData_fields = array(
                'xar_fileData_id'  => array('type'=>'integer','size'=>'big','null'=>FALSE,'increment'=>TRUE, 'primary_key'=>TRUE),
                'xar_fileEntry_id' => array('type'=>'integer','size'=>'big','null'=>FALSE),
                'xar_fileData'     => array('type'=>'blob','size'=>'medium','null'=>FALSE)
            );

            // Create the Table - the function will return the SQL is successful or
            // raise an exception if it fails, in this case $sql is empty
            $query  =  xarDBCreateTable($file_data_table, $fileData_fields);
            $result =& $dbconn->Execute($query);
            if (!$result) {
                // if there was an error, make sure to remove the tables 
                // so the user can try the upgrade again
                $query[] = xarDBDropTable($file_entry_table);
                $query[] = xarDBDropTable($file_data_table);
                foreach ($query as $run) {
                    $result =& $dbconn->Execute($run);
                }
                return;
            }

            /*
             * need to change these to the new priv structure
             *
             
                xarRegisterMask('ViewUploads','All','uploads','Upload','All','ACCESS_OVERVIEW');
                xarRegisterMask('ReadUploads','All','uploads','Upload','All','ACCESS_READ');
                xarRegisterMask('EditUploads','All','uploads','Upload','All','ACCESS_EDIT');
                xarRegisterMask('AdminUploads','All','uploads','Upload','All','ACCESS_ADMIN');
             
             *
             * And these need to be switched as well:
             *

                xarModSetVar('uploads', 'uploads_directory', $uploads_directory);
                xarModSetVar('uploads', 'maximum_upload_size', '100000');
                xarModSetVar('uploads', 'allowed_types', 'gif;jpg;zip;tar.gz;tgz');
                xarModSetVar('uploads', 'confirm_delete', '1');
                xarModSetVar('uploads', 'import_directory',  '');
                xarModSetVar('uploads', 'obfuscate_imports', '0');

                
             * Note: the ones below need to be moved over to the image module...
             
                xarModSetVar('uploads', 'max_image_width', '600');
                xarModSetVar('uploads', 'max_image_height', '800');
                xarModSetVar('uploads', 'thumbnail_setting', '0');
                xarModSetVar('uploads', 'thumbnail_path', '');
                xarModSetVar('uploads', 'netpbm_path', '');

             *
             */
            
            if(xarServerGetVar('PATH_TRANSLATED')) {
                $base_directory = dirname(realpath(xarServerGetVar('PATH_TRANSLATED')));
            } elseif(xarServerGetVar('SCRIPT_FILENAME')) {
                $base_directory = dirname(realpath(xarServerGetVar('SCRIPT_FILENAME')));
            } else {
                $base_directory = './';
            }
            
            // Grab the old values
            $path_uploads_directory   = xarModGetVar('uploads','uploads_directory');
            if (empty($path_uploads_directory)) {
                $path_uploads_directory = $base_directory . '/var/imports';
            }
            
            $path_imports_directory   = xarModGetVar('uploads','import_directory');
            if (empty($import_directory)) {
               $path_imports_directory = $base_directory . '/var/imports';
            }
            
            $file_maxsize             = xarModGetVar('uploads','maximum_upload_size');
            $file_censored_mimetypes  = serialize(array('application','video','audio', 'other', 'message'));
            $file_delete_confirmation = xarModGetVar('uploads','confirm_delete') ? 1 : 0;
            $file_obfuscate_on_import = (int) xarModGetVar('uploads','obfuscate_imports') ? 1 : 0;
            $file_obfuscate_on_upload = TRUE;
            
            // Now remove the old module vars
            xarModDelVar('uploads','uploads_directory');
            xarModDelVar('uploads','maximum_upload_size');
            xarModDelVar('uploads','allowed_types');
            xarModDelVar('uploads','confirm_delete');
            xarModDelVar('uploads','max_image_width');
            xarModDelVar('uploads','max_image_height');
            xarModDelVar('uploads','thumbnail_setting');
            xarModDelVar('uploads','thumbnail_path');
            xarModDelVar('uploads','netpbm_path');
            xarModDelVar('uploads','import_directory');
            xarModDelVar('uploads','obfuscate_imports');
            
            // Now set up the new ones :)
            xarModSetVar('uploads','path.uploads-directory', $path_uploads_directory);
            xarModSetVar('uploads','path.imports-directory', $path_imports_directory);
            xarModSetVar('uploads','file.maxsize', $file_maxsize);
            xarModSetVar('uploads','file.censored-mimetypes', $file_censored_mimetypes);
            xarModSetVar('uploads','file.obfuscate-on-import', $file_obfuscate_on_import);
            xarModSetVar('uploads','file.obfuscate-on-upload', $file_obfuscate_on_upload);

            /** 
             * Last, but not least, we drop the old tables:
             * We wait to do this until the very end so that, in the event there 
             * was a problem, we can retry at some point in time
             */
            $query = xarDBDropTable($uploads_table);
            $result =& $dbconn->Execute($query);
            if (!$result) 
                return;
            
            $query = xarDBDropTable($uploads_blobs_table);
            $result =& $dbconn->Execute($query);
            if (!$result) 
                return;
                
            return true;
            
    }
    return true;
}

/**
 * delete the uploads module
 */
function uploads_delete()
{
    xarModDelVar('uploads','path.uploads-directory');
    xarModDelVar('uploads','path.imports-directory');
    xarModDelVar('uploads','file.maxsize');
    xarModDelVar('uploads','file.censored-mimetypes');
    xarModDelVar('uploads','file.obfuscate-on-import');
    xarModDelVar('uploads','file.obfuscate-on-upload');

    // Get database information
    list($dbconn)   = xarDBGetConn();
    $xartables       = xarDBGetTables();
    
    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartables['file_entry']);
    if (empty($query)) 
        return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) 
        return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['file_data']);
    if (empty($query)) 
        return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) 
        return;

    return true;
}

?>
