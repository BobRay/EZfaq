  <?php

  $root = dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/';

$sources= array (
    'root' => $root,
    'docs' => $root . 'assets/components/ezfaq/docs/'

);

$default_template = $object->xpdo->config['default_template'];

$success = false;
switch($options[XPDO_TRANSPORT_PACKAGE_ACTION]) {



    case XPDO_TRANSPORT_ACTION_INSTALL:
        if (isset($options['install_sample']) && $options['install_sample'] == 'Yes' ) {

            $object->xpdo->log(XPDO_LOG_LEVEL_INFO,"Creating resource: Sample FAQ Page<br />");
            $r = $object->xpdo->newObject('modResource');
            $r->set('class_key','modDocument');
            $r->set('context_key','web');
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


            $object->xpdo->log(XPDO_LOG_LEVEL_INFO,"<br>Creating resource: FAQ Contents<br />");
            $r = $object->xpdo->newObject('modResource');

            $r->set('class_key','modDocument');
            $r->set('context_key','web');
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
            $r->setContent(file_get_contents($sources['docs'] . 'sample-content'));
            $r->set('searchable','0');
            $r->set('cacheable','1');
            $r->set('template',$default_template);
            $r->set('donthit','1');
            $r->set('hidemenu','1');

            $r->save();
            $faqContentId = $r->get('id');  /* need this to set docID in the snippet */

            $resource = $object->xpdo->getObject('modDocument', array('pagetitle' => 'Sample FAQ Page') );
            $resource->setContent("[[EZfaq? &docID=`" . $faqContentId . "`]]" );
            $resource->save();

            }

            $success = true;
            break;

        case XPDO_TRANSPORT_ACTION_UPGRADE:
            $success = true;
            break;
        case XPDO_TRANSPORT_ACTION_UNINSTALL:
            $object->xpdo->log(XPDO_LOG_LEVEL_INFO,"<br /><b>NOTE: You will have to remove the two Resources (the FAQ <br /> and FAQ Content documents) manually</b><br />");

            $success = true;
            break;

}
return $success;
?>
