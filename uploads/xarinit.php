<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marie Altobelli (Ladyofdragons)
// Purpose of file:  Initialisation functions for uploads
// ----------------------------------------------------------------------

/**
 * initialise the module
 */
function uploads_init()
{

	if( isset( $_SERVER['PATH_TRANSLATED'] ) )
	{
		$uploads_directory = dirname(realpath($_SERVER['PATH_TRANSLATED'])) . '/var/uploads/';
	} elseif( isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
		$uploads_directory = dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/var/uploads/';
	} else {
		$uploads_directory = 'var/uploads/';
	}
    xarModSetVar('uploads', 'uploads_directory', $uploads_directory);
    xarModSetVar('uploads', 'maximum_upload_size', '100000');
    xarModSetVar('uploads', 'allowed_types', 'gif;jpg;zip;tar.gz;tgz');
    xarModSetVar('uploads', 'max_image_width', '600');
    xarModSetVar('uploads', 'max_image_height', '800');
		
    // Get datbase setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $uploadstable = $xartable['uploads'];
    $blobstable = $xartable['blobs'];

    // Xaraya offers the xarCreateTable function
    // contained in the following file to provide create table functionality.
    xarDBLoadTableMaintenanceAPI();

    // Define the table structure in this associative array
    /*CREATE TABLE `xar_uploads` (
	`xar_ulid` INT( 32 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`xar_ulmod` VARCHAR( 32 ) NOT NULL ,
	`xar_ulmodid` INT NOT NULL ,
	`xar_uluid` INT( 32 ) UNSIGNED NOT NULL ,
	`xar_ulfile` VARCHAR( 254 ) NOT NULL ,
	`xar_ulhash` VARCHAR( 254 ) NOT NULL ,
	`xar_ulapp` TINYINT( 3 ) DEFAULT '0' NOT NULL ,
	`xar_ulbid` INT( 32 ) UNSIGNED DEFAULT '0' NOT NULL ,
	`xar_ultype` CHAR( 1 ) NOT NULL, 
	INDEX ( `xar_ulmod` ) 
    );*/
    $uploadsfields = array(
        'xar_ulid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_ulmod'=>array('type'=>'varchar','size'=>32,'null'=>FALSE),
        'xar_ulmodid'=>array('type'=>'integer','size'=>'small','null'=>FALSE,'default'=>'0'),
	'xar_uluid'=>array('type'=>'integer','size'=>32,'null'=>FALSE),
	'xar_ulfile'=>array('type'=>'varchar','size'=>254,'null'=>FALSE),
	'xar_ulhash'=>array('type'=>'varchar','size'=>254,'null'=>FALSE),
	'xar_ulapp'=>array('type'=>'integer','size'=>3,'null'=>FALSE,'default'=>'0'),
	'xar_ulbid'=>array('type'=>'integer','size'=>32,'null'=>FALSE,'default'=>'0'),
	'xar_ultype'=>array('type'=>'char','size'=>1,'null'=>FALSE)
    );
		
    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $query = xarDBCreateTable($uploadstable,$uploadsfields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    //now create the blob table to contain images & files in the database.
    /*CREATE TABLE `xar_uploadblobs` (
        `xar_ulbid` INT( 32 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `xar_ulid` INT( 32 ) UNSIGNED NOT NULL ,
        `xar_ulblob` BLOB NOT NULL ,
	INDEX ( `xar_ulid` ) 
	 );*/

    $blobsfields = array(
        'xar_ulbid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_ulid'=>array('type'=>'varchar','size'=>32,'null'=>FALSE),
        'xar_ulblob'=>array('type'=>'integer','size'=>'small','null'=>FALSE,'default'=>'0')
    );
		
    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $query = xarDBCreateTable($blobstable,$blobsfields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
	
    xarRegisterMask('ViewUploads','All','uploads','Upload','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadUploads','All','uploads','Upload','All','ACCESS_READ');
    xarRegisterMask('EditUploads','All','uploads','Upload','All','ACCESS_EDIT');
    xarRegisterMask('AdminUploads','All','uploads','Upload','All','ACCESS_ADMIN');
	

    /**
     * Register hooks
     */
    // when a new module item is being specified
    if (!xarModRegisterHook('item', 'new', 'API'
	                       ,'uploads', 'admin', 'newhook')) {
         $msg = xarML('Could not register hook');
         xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
         return;
    }
	
    // when a module item is created
    if (!xarModRegisterHook('item', 'create', 'API'
	                      , 'uploads', 'admin', 'createhook')) {
         $msg = xarML('Could not register hook');
         xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
         return;
    }

	if (!xarModRegisterHook('item', 'display', 'GUI',
                            'uploads', 'user', 'formdisplay')) {
         $msg = xarML('Could not register hook');
         xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
         return;
	}


    // Set up module hooks
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
function articles_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {
        case .01:
            //Got the darn thing working and ready for initial beta release
            break;
    }
    return true;
}

/**
 * delete the uploads module
 */
function uploads_delete()
{
    xarModDelVar('uploads', 'uploads_directory');
    xarModDelVar('uploads', 'maximum_upload_size');
    xarModDelVar('uploads', 'allowed_types');

    // Get database information
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['uploads']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['blobs']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>