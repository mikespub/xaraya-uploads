<?php

/**
 * Load an image object for further manipulation
 *
 * @param   integer $fileId        The (uploads) file id of the image to load, or
 * @param   string  $fileLocation  The file location of the image to load
 * @param   string  $thumbsdir     (optional) The directory where derivative images are stored
 * @returns object
 * @return an Image_GD (or other) object
 */
function & images_userapi_load_image( $args ) 
{
    extract($args);

    if (empty($fileId) && empty($fileLocation)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      '', 'load_image', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    } elseif (!empty($fileId) && !is_numeric($fileId)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      'fileId', 'load_image', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      'fileLocation', 'load_image', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    }

    // if both arguments are specified, give priority to fileId
    if (!empty($fileId)) {    
        $fileInfo = end(xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $fileId)));
        if (empty($fileInfo)) {
            return NULL;
        } else {
            $location = $fileInfo['fileLocation'];
        }
    } else {
        $location = $fileLocation;
    }

    if (empty($thumbsdir)) {
        $thumbsdir = xarModGetVar('images', 'path.derivative-store');
    }

    include_once('modules/images/xarclass/image_properties.php');
     
    switch(xarModGetVar('images', 'type.graphics-library')) {
        case _IMAGES_LIBRARY_IMAGEMAGICK:
            include_once('modules/images/xarclass/image_ImageMagick.php');
            return new Image_ImageMagick($location, $thumbsdir);
            break;
        case _IMAGES_LIBRARY_NETPBM:
            include_once('modules/images/xarclass/image_NetPBM.php');
            return new Image_NetPBM($location, $thumbsdir);
            break;
        default:
        case _IMAGES_LIBRARY_GD:
            include_once('modules/images/xarclass/image_gd.php');
            return new Image_GD($location, $thumbsdir);
            break;
    }
}

?>
