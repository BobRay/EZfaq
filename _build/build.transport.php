<?php
/**
 * EZFaq Build Script
 *
 * @name EZFaq
 * @version 3.2.2
 * @release beta1
 * @author BobRay <bobray@softville.com>
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;

$root = dirname(dirname(__FILE__)).'/';

/*  Uncomment the next line if you need to debug the setting above after checkout */

/* die("ROOT: " . $root); */


$sources= array (
    'root' => $root,
    'build' => $root . '_build/',
    'docs' => $root . 'core/components/ezfaq/docs/',
    'lexicon' => $root . 'core/components/ezfaq/lexicon/',
    'source_assets' => $root.'assets/components/ezfaq',
    'source_core' => $root.'core/components/ezfaq',
);

/* This example assumes that you are creating one element with one namespace, a lexicon, and one file resolver.
*  You'll need to modify it if your situation is different. A snippet with no support files (no images, no css, no js includes, etc.) doesn't need a file
*  resolver so you can comment out that part of the code. If you have no lexicon, you can comment out that part of the code. If you need to create multiple
*  elements (e.g. a snippet, several chunks, and maybe a plugin) you can do it all in this file, but you'll have to duplicate the code below that creates
*  and packages the element. You'll also have to reset the variables for each segment. If you put all your support files in or below in a single
*  directory, you'll only need one file resolver.
*/

$element_namespace = 'ezfaq';    /* lexicon namespace for your add-on */
$element_name = 'EZfaq';         /* name of your element as it will appear in the Manager */
$element_object_type = 'modSnippet';   /* What is it?  modSnippet, modChunk, modPlugin, etc. */
$element_type = 'snippet';   /* What is it without the "mod" */
$element_description = 'EZfaq 3.2.2-beta1 -  Generates a FAQ page for your site.'; /* description field in the element's editing page */
$element_source_file = $sources['source_core'] . '/snippet.ezfaq.php'; /* Where's the file PB will use to create the element */
$element_category = 0;  /* the category of the element */
$package_name = 'ezfaq';  /* The name of the package as it will appear in Workspaces will be this plus the next two variables */
$package_version = '3.2.2';
$package_release = 'beta1';
$assets_resolver_source = $sources['source_assets'];   /* Files in this directory will be packaged */
$assets_resolver_target = "return MODX_ASSETS_PATH . 'components/';"; /* Those files will go here */
$core_resolver_source = $sources['source_core'];   /* Files in this directory will be packaged */
$core_resolver_target = "return MODX_CORE_PATH . 'components/';"; /* Those files will go here */


/* Note that for file resolvers, the named directory itself is also packaged.
*  So the two lines above will copy the ezfaq dir and its contents
*  to the assets/components/ directory in the target install.
*/

/* get rid of time limit */
set_time_limit(0);

/* override with your own defines here (see build.config.sample.php) */
require_once dirname(__FILE__).'/build.config.php';

require_once (MODX_CORE_PATH . 'model/modx/modx.class.php');
$modx= new modX();
$modx->initialize('mgr');
echo '<pre>'; /* used for nice formatting for log messages */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');
/* $modx->setDebug(true); */

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage($package_name,$package_version,$package_release);
$builder->registerNamespace($element_namespace,false,true);

if (!file_exists($element_source_file)) {
    $modx->log(modX::LOG_LEVEL_FATAL,"<b>Error</b> - Element source file not found: {$element_source_file}<br />");
}
$modx->log(modX::LOG_LEVEL_INFO,"Creating element from source file: {$element_source_file}<br />");

/* get the source from the actual element in your database OR
 manually create the object, grabbing the source from a file
 */

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