<?php
function generateSafeFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = pathinfo($originalName, PATHINFO_FILENAME);
    $cleanName = preg_replace("/[^a-zA-Z0-9]/", "_", $filename);
    return time() . "_" . $cleanName . "." . $extension;
}
?>