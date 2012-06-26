<?php
/**
 * @package ezfaq
 * @subpackage build
 */
$output = '';

 if ($options[xPDOTransport::PACKAGE_ACTION] == 1) {
    $output = '<p>&nbsp;</p>
    <input type="checkbox" name="install_sample" id="install_sample" value="Yes" align="left" />&nbsp;&nbsp;
    <label for="install_sample">Install Sample FAQ Page (recommended).</label>
    <p>&nbsp;</p>
    <p><b>Check the box to install a sample FAQ page that you can easily edit to contain your FAQ content.</b></p>';
}

return $output;