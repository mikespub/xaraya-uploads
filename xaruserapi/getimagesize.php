<?php

/**
 * Get the size of an image (from file or database)
 *
 * @param   string  $fileLocation  The file location of the image, or
 * @param   integer $fileId        The (uploads) file id of the image
 * @param   integer $fileType      The (uploads) mime type for the image
 * @param   integer $storeType     The (uploads) store type for the image
 * @returns array
 * @return an array containing the width, height and gd_info if available
 */
function images_userapi_getimagesize( $args ) 
{
    extract($args);

    if (empty($fileId) && empty($fileLocation)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      '', 'getimagesize', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    } elseif (!empty($fileId) && !is_numeric($fileId)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      'fileId', 'getimagesize', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
        $mesg = xarML('Invalid parameter \'#(1)\' to API function \'#(2)\' in module \'#(3)\'', 
                      'fileLocation', 'getimagesize', 'images');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($mesg));
        return;
    }

    if (!empty($fileLocation) && file_exists($fileLocation)) {
        return @getimagesize($fileLocation);

    } elseif (extension_loaded('gd') && xarModAPILoad('uploads','user') &&
              defined('_UPLOADS_STORE_DB_DATA') && ($storeType & _UPLOADS_STORE_DB_DATA)) {
        // get the image data from the database
        $data = xarModAPIFunc('uploads', 'user', 'db_get_file_data', array('fileId' => $fileId));
        if (!empty($data)) {
            $src = implode('', $data);
            unset($data);
            $img = @imagecreatefromstring($src);
            if (!empty($img)) {
                $width  = @imagesx($img);
                $height = @imagesy($img);
                @imagedestroy($img);
                // Simulate the type returned by getimagesize()
                switch ($fileType) {
                    case 'image/gif':
                        $type = 1;
                        break;
                    case 'image/jpeg':
                        $type = 2;
                        break;
                    case 'image/png':
                        $type = 3;
                        break;
                    default:
                        $type = 0;
                        break;
                }
                $string = 'width="' . $width . '" height="' . $height . '"';
                return array($width,$height,$type,$string);
            }
        }
    }

}

?>
