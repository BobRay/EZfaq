<?php
/**
 * EZFaq Build Script
 *
 * @name EZFaq
 *
 * @author BobRay <https://bobsguides.com>
 *
 * Copyright Bob Ray 2011-2023
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;

require 'C:\xampp\htdocs\addons\core\config\config.inc.php';

$root = dirname(dirname(__FILE__)).'/';

$sources= array (
    'root' => $root,
    'build' => $root . '_build/',
    'docs' => $root . 'core/components/ezfaq/docs/',
    'lexicon' => $root . 'core/components/ezfaq/lexicon/',
    'source_assets' => $root.'assets/components/ezfaq',
    'source_core' => $root.'core/components/ezfaq',
);

$element_namespace = 'ezfaq';
$element_name = 'EZfaq';
$element_object_type = 'modSnippet';
$element_type = 'snippet';
$element_description = 'EZfaq 3.3.3-pl -  Generates a FAQ page for your site.';
$element_source_file = $sources['source_core'] . '/snippet.ezfaq.php';
$element_category = 0;
$package_name = 'ezfaq';
$package_version = '3.3.3';
$package_release = 'pl';
$assets_resolver_source = $sources['source_assets'];
$assets_resolver_target = "return MODX_ASSETS_PATH . 'components/';";
$core_resolver_source = $sources['source_core'];
$core_resolver_target = "return MODX_CORE_PATH . 'components/';";

set_time_limit(0);

// require_once dirname(__FILE__).'/build.config.php';

require_once (MODX_CORE_PATH . 'model/modx/modx.class.php');
$modx= new modX();
$modx->initialize('mgr');
echo '<pre>';
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');


$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage($package_name,$package_version,$package_release);
$builder->registerNamespace($element_namespace,false,true,'{core_path}components/'.$element_namespace.'/');

if (!file_exists($element_source_file)) {
    $modx->log(modX::LOG_LEVEL_FATAL,"<b>Error</b> - Element source file not found: {$element_source_file}<br />");
}
$modx->log(modX::LOG_LEVEL_INFO,"Creating element from source file: {$element_source_file}<br />");
/* @var $c modElement */
$c= $modx->newObject($element_object_type);
$c->set('name', $element_name);
$c->set('description', $element_description);
$c->set('category', $element_category);
$c->set($element_type, file_get_contents($element_source_file));

/* create a transport vehicle for the data object */
$attributes= array(
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::UPDATE_OBJECT => true,
);
$vehicle = $builder->createVehicle($c, $attributes);

$modx->log(modX::LOG_LEVEL_INFO,"Creating Resolver<br />");

if (!is_dir($core_resolver_source)) {
    $modx->log(modX::LOG_LEVEL_FATAL,"<b>Error</b> - Core resolver source directory not found: {$core_resolver_source}<br />");
}
$modx->log(modX::LOG_LEVEL_INFO,"Source: {$core_resolver_source}<br />");
$modx->log(modX::LOG_LEVEL_INFO,"Target: {$core_resolver_target}<br /><br />");

$vehicle->resolve('file',array(
    'source' => $core_resolver_source,
    'target' => $core_resolver_target,
));

if (!is_dir($assets_resolver_source)) {
    $modx->log(modX::LOG_LEVEL_FATAL,"<b>Error</b> - Assets resolver source directory not found: {$assets_resolver_source}<br />");
}
$modx->log(modX::LOG_LEVEL_INFO,"Source: {$assets_resolver_source}<br />");
$modx->log(modX::LOG_LEVEL_INFO,"Target: {$assets_resolver_target}<br /><br />");

$vehicle->resolve('file',array(
    'source' => $assets_resolver_source,
    'target' => $assets_resolver_target,
));

/* Create the php resolver to install the sample FAQ */
$vehicle->resolve('php',array(
            'type' => 'php',
            'source' => $sources['build'] . "install.script.php",
            'target' => "return '" . $sources['build'] . "';"

        ));
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
    'setup-options' => array(
        'source' => $sources['build'].'user.input.php',
    ),
));

/* zip up the package */
$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"Package completed.<br />Execution time: {$totalTime}<br>");

exit ();