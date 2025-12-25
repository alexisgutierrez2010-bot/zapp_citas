<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

/**
 * Redimensiona una imagen a un tamaño máximo, manteniendo la proporción.
 *
 * @param string $file_path Ruta al archivo temporal de la imagen subida.
 * @param int $max_width Ancho máximo deseado.
 * @param int $max_height Alto máximo deseado.
 * @param int $quality Calidad para imágenes JPEG (0-100).
 * @return string|false Los datos binarios de la nueva imagen, o false si falla.
 */
function resize_image_to_blob($file_path, $max_width, $max_height, $quality = 80) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return false;
    }

    list($width, $height, $type) = getimagesize($file_path);
    if (!$width || !$height) return false;

    $src_image = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($file_path);
            break;
        default: // Si no es JPG o PNG, devolvemos el original sin procesar
            return file_get_contents($file_path);
    }

    $ratio = $width / $height;
    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_height = $max_width / $ratio;
        $new_width = $max_width;
    }

    $dst_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    ob_start();
    imagejpeg($dst_image, null, $quality); // Guardar siempre como JPEG para optimizar tamaño
    $image_data = ob_get_clean();

    imagedestroy($src_image);
    imagedestroy($dst_image);

    return $image_data;
}
?>