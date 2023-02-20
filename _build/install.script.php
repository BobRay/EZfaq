<?php
/**
 * @package ezfaq
 *
 * @author BobRay <https://bobsguides.com>
 *
 * Copyright Bob Ray 2011-2023
 */

/* @var $transport modTransportPackage */
/* @var $object modTransportPackage */
/* @var $options array */




if ($transport) {
    $modx =& $transport->xpdo;
} else {
    $modx =& $object->xpdo;
}

$prefix = $modx->getVersionData()['version'] >= 3
    ? 'MODX\Revolution\\'
    : '';

$context = $modx->getOption('default_context', null, 'web');

$root = $modx->getOption('core_path');
$sources= array (
    'docs' => $root . 'components/ezfaq/docs/'
);
$default_template = $modx->getOption('default_template');

$success = false;
$modx->log(xPDO::LOG_LEVEL_INFO,'Running PHP Resolver.');
switch($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
        $install_sample = $modx->getOption('install_sample',$options,'No');
        if ($modx->getObject($prefix . 'modResource',array('pagetitle'=>'Sample FAQ Page'))) {
            /* don't install resources if they're already there */
            $success = true;
            break;
        }
/* @var $r modResource */
        if ($install_sample == 'Yes') {
            $modx->log(xPDO::LOG_LEVEL_INFO,"Creating resource: Sample FAQ Page");
            $r = $modx->newObject($prefix . 'modResource');
            $r->set('context_key', $context);
            $r->set('type','document');
            $r->set('contentType','text/html');
            $r->set('pagetitle','Sample FAQ Page');
            $r->set('longtitle','Sample FAQ Page');
            $r->set('description','Sample FAQ Page');
            $r->set('alias','faq');
            $r->set('published','1');
            $r->set('parent','0');
            $r->set('isfolder','1');
            $r->setContent('[[EZfaq]]');
            $r->set('richtext','0');
            $r->set('menuindex','99');
            $r->set('searchable','1');
            $r->set('cacheable','1');
            $r->set('menutitle','FAQ');
            $r->set('donthit','0');
            $r->set('hidemenu','0');
            $r->set('template',$default_template);

            $r->save();
            $faqId = $r->get('id');  /* need this to set content page parent */

            /* now create FAQ content page */
            $modx->log(xPDO::LOG_LEVEL_INFO,"<br>Creating resource: FAQ Contents");
            $r = $modx->newObject($prefix . 'modResource');
            $r->set('context_key', $context);
            $r->set('type','document');
            $r->set('contentType','text/html');
            $r->set('pagetitle','FAQ Content');
            $r->set('longtitle','Sample Page Content');
            $r->set('description','Sample FAQ Page content -- leave unpublished');
            $r->set('alias','faqContent');
            $r->set('published','0');
            $r->set('parent',$faqId);
            $r->set('isfolder','0');
            $r->setContent('[[EZfaq]]');
            $r->set('richtext','0');
            $r->setContent(file_get_contents($sources['docs'] . 'sample-content.txt'));
            $r->set('searchable','0');
            $r->set('cacheable','1');
            $r->set('template',$default_template);
            $r->set('donthit','1');
            $r->set('hidemenu','1');

            $r->save();
            $faqContentId = $r->get('id');  /* need this to set ezfaqDocID in the snippet */

            /* @var $resource modResource */
            $resource = $modx->getObject($prefix . 'modResource', array('pagetitle' => 'Sample FAQ Page') );
            if ($resource) {
                $resource->setContent("[[EZfaq? &ezfaqDocID=`" . $faqContentId . "`]]" );
                $resource->save();
            }
        }

        $success = true;
        break;

   case xPDOTransport::ACTION_UPGRADE:
        $success = true;
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $success = false;

        /* remove sample content page */
        $resource = $modx->getObject($prefix . 'modResource',array(
            'pagetitle' => 'FAQ Content',
        ));
        if ($resource != null) {
            $resource->remove();
        } else {
            $modx->log(xPDO::LOG_LEVEL_INFO,"<br /><b>NOTE: You will have to remove the FAQ page manually</b><br />");
        }
        /* @var $resource2 modResource */
        /* remove sample faq page */
        $resource2 = $modx->getObject($prefix . 'modResource',array(
            'pagetitle' => 'Sample FAQ Page',
        ));
        if ($resource2 != null) {
            $resource2->remove();
        } else {
            $modx->log(xPDO::LOG_LEVEL_INFO,"<br /><b>NOTE: You will have to remove the FAQ Content page manually</b><br />");
        }


        $success = true;
        break;
}
return $success;