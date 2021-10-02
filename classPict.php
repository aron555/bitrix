<?

namespace Bas;

use Imagick;
use ImagickException;

class Pict
{
    private static function checkFormat($str)
    {
        $mime = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $str);

        if ($mime == 'image/png') {
            return true;
        }

        if ($mime == 'image/jpeg') {
            return true;
        }

        return false;
    }

    // detect User Agent
    private static function checkUA()
    {
        $ua = $_SERVER["HTTP_USER_AGENT"];      // Get user-agent of browser

        $safariOrChrome = strpos($ua, 'Safari') ? true : false;     // Браузер - это либо Safari, либо Chrome (поскольку Chrome User-Agent включает в себя слово "Safari")
        $chrome = strpos($ua, 'Chrome') ? true : false;             // Браузер - Chrome +

        if (($safariOrChrome == true and $chrome == true)) {
            return true;
        }

        return false;
    }

    private static function imageCreateFromAny($imgSrc)
    {
        $mime = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $imgSrc);

        if ($mime == 'image/png') {
            $im = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
            imagepalettetotruecolor($im);
            imagealphablending($im, true);
            imagesavealpha($im, true);
        }

        if ($mime == 'image/jpeg') {
            $im = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
        }

        return $im;
    }

    public static function getWebp($imgSrc, $intQuality = 70)
    {
        // Браузер, формат файла
        if (self::checkUA() == true && self::checkFormat($imgSrc) == true) {

            $info = pathinfo($imgSrc);

            $uploadDirName = \Bitrix\Main\Config\Option::get("main", "upload_dir", "upload");

            $webpIn = '/' . $uploadDirName . '/webp';

            if (strpos($info['dirname'], '/' . $uploadDirName . '/') !== false) {
                $webpDir = str_replace('/' . $uploadDirName, $webpIn, $info['dirname']) . '/';
            } else {
                $webpDir = $webpIn . $info['dirname'] . '/';
            }

            $tmpWebp = $webpDir . $info['filename'] . '.' . 'webp';

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $tmpWebp)) {
                return $tmpWebp;
            } else {
                if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $webpDir)) {
                    mkdir($_SERVER['DOCUMENT_ROOT'] . $webpDir, 0755, true);
                }

                $imageWebp = '';

                if (function_exists('imagewebp') && $imageWebp == 'Y') {
                    // Создадим webp
                    $im = self::imageCreateFromAny($imgSrc);

                    if ($im != false) {
                        $ok = imagewebp($im, $_SERVER['DOCUMENT_ROOT'] . $tmpWebp, $intQuality);

                        if ($ok) {
                            //fix for corrupted WEBPs
                            if (filesize($tmpWebp) % 2 == 1) {
                                file_put_contents($tmpWebp, "\0", FILE_APPEND);
                            }

                            imagedestroy($imgSrc);

                            return $tmpWebp;
                        }

                    }
                } elseif (class_exists('Imagick')) {
                    try {
                        $im = new Imagick();

                        $im->readImage($_SERVER['DOCUMENT_ROOT'] . $imgSrc);

                        $mime = $im->getImageMimeType();

                        if ($mime === 'image/png') {
                            $im->setImageFormat('webp');
                            $im->setImageCompressionQuality($intQuality);
                            $im->setOption('webp:lossless', 'true');
                        }

                        $im->writeImage($_SERVER['DOCUMENT_ROOT'] . $tmpWebp);
                        $im->clear();
                        $im->destroy();

                        return $tmpWebp;
                    } catch (ImagickException $e) {
                        file_put_contents(__DIR__ . '/log_webp.txt', print_r($e->getTraceAsString(), 1));
                    }
                }
            }
        }

        return $imgSrc;
    }

    public static function resizePict($file, $width, $height, $isProportional = true, $intQuality = 70)
    {
        $file = \CFile::ResizeImageGet(
            $file,
            [
                'width' => $width,
                'height' => $height
            ],
            ($isProportional ? BX_RESIZE_IMAGE_PROPORTIONAL : BX_RESIZE_IMAGE_EXACT),
            false,
            false,
            false,
            $intQuality
        );

        return $file['src'];
    }

    public static function getResizeWebp($file, $width, $height, $isProportional = true, $intQuality = 70)
    {
        $file['SRC'] = self::resizePict($file, $width, $height, $isProportional, $intQuality);

        $file = self::getWebp($file, $intQuality);

        return $file;
    }

    public static function getResizeWebpSrc($file, $width, $height, $isProportional = true, $intQuality = 70)
    {
        $file['SRC'] = self::resizePict($file, $width, $height, $isProportional, $intQuality);

        $file = self::getWebp($file, $intQuality);

        return $file['WEBP_SRC'];
    }
}
