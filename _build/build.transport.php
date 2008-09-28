<?php
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;

$sources= array (
    'root' => dirname(dirname(__FILE__)) . '/',
    'assets' => dirname(dirname(__FILE__)) . '/assets/',
);

$element_namespace = 'ezfaq';
$element_name = 'EZfaq';
$element_object_type = 'modSnippet';
$element_type = 'snippet';
$element_description = '<strong>3.0.5-beta</strong> Generates a FAQ page for your site.';
$element_source_file = $sources['assets'] . 'ezfaq/snippet.ezfaq.php';
$element_filename = 'snippet.ezfaq.php';
$element_category = 0;
$package_name = $element_name;
$package_version = '3.0.5';
$package_release = 'beta';
$lexicon_path = $sources['root'] . '_build/lexicon/';
$resolver_source = $sources['assets'] . 'ezfaq';
$resolver_target = "return MODX_ASSETS_PATH . 'snippets/';";


// get rid of time limit
set_time_limit(0);

// override with your own defines here (see build.config.sample.php)
require_once dirname(__FILE__).'/build.config.php';

require_once (MODX_CORE_PATH . 'model/modx/modx.class.php');
$modx= new modX();
$modx->initialize('mgr');
//$modx->setDebug(true);

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->create($package_name,$package_version,$package_release);
$builder->registerNamespace($element_namespace,false,true);

if (!file_exists($element_source_file)) {
    echo "<b>Error</b> - Element source file not found: {$element_source_file}<br>";
    exit();
}

print "Creating element from source file: {$element_source_file}<br>";

// get the source from the actual element in your database OR
// manually create the object, grabbing the source from a file
$c= $modx->newObject($element_object_type);
$c->set('name', $element_name);
$c->set('description', $element_description);
$c->set('category', $element_category);
$c->set($element_type, file_get_contents($element_source_file));

// create a transport vehicle for the data object
$attributes= array(
    XPDO_TRANSPORT_UNIQUE_KEY => 'name',
    XPDO_TRANSPORT_UPDATE_OBJECT => true,
);
$vehicle = $builder->createVehicle($c, $attributes);

echo "<br>Creating Resolver<br>";

if (!is_dir($resolver_source)) {
    echo "<b>Error</b> - Resolver source directory not found: {$resolver_source}<br>";
    exit();
}
echo "&nbsp;&nbsp;&nbsp;&nbsp;Source: {$resolver_source}<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;Target: {$resolver_target}<br><br>";

$vehicle->resolve('file',array(
    'source' => $resolver_source,
    'target' => $resolver_target,
));
$builder->putVehicle($vehicle);

if (!is_dir($lexicon_path)) {
    echo "<b>Error</b> - Lexicon path not found: {$lexicon_path}";
    exit();
}
echo "Creating lexicon from: {$lexicon_path}<br><br>";

// load lexicon strings
$builder->buildLexicon($lexicon_path);

// zip up the package
$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

echo "Package completed<br>Execution time: {$totalTime}<br>";

exit ();