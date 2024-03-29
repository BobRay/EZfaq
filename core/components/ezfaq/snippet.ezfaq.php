<?php
/**
 * @package ezfaq
 * @author Bob Ray <https://bobsguides.com>
 * @created 03-23-2011
 * @Copyright 2011-2023 Bob Ray
*/
/*
(based on an idea from SorenG and JavaScript code from DynamicDrive.com)
Compatibility: MODX Revolution
Thanks to: jaredc for suggestions on .css usage and cssPath parameter
 */

/******************************
*             Usage           *
*******************************


Minimal EZfaq call:
-------------------

[[!EZfaq? &ezfaqDocID=`12`]]

(Use the document ID of your unpublished FAQ content document
-- NOT THE DOCUMENT THAT *DISPLAYS* THE FAQ --.)

&ezfaqDocID is required.

Optional parameters:
--------------------

&showHideAllOption [display the show/hide all buttons - default "true"]

&statusOpenHTML  [symbol to put next to open topics (can be an image URL) - defaults to "[-]"]

&statusClosedHTML [symbol to put next to closed topics (can be an image URL) - defaults to "[+]"]

&openColor  [color for open questions (name or hex value #ffffff) - default "red"]

&closedColor [color for closed questions (name or hex value #ffffff) - default "black"]

&setPersist  [does open state persist across visits/reloads - default "true";]

&collapsePrevious [if set, only one answer can be open at a time - default "true"]

&defaultExpanded  [expand answers n1 through n2 ("0,1" expands items 1 through 2)when page is opened (default, no)]

&ezfaqCssPath [Optional URL to .css file -- set to `` for no .css file]

&faqPath [URL to the EZfaq directory] - default /assets/components/ezfaq

Create a published document to display the FAQ. Put the snippet call in that document.
Then create an unpublished document to hold the FAQ content (it makes sense for it to be a child of the first doc).
Make a note of the document ID of the FAQ content document to use in the snippet call.

File Format - The unpublished FAQ content document should be in the following format:
-------------------------------------------------------------------------------------
This will be rendered above the first question (optional)

Q:How much is two plus two?

A:Four

Extra: This will always be rendered between questions. (optional)
(Important: Be sure to use Extra:, not EXTRA:)

Q:Who is the fifth avatar of Vishnu?

A:Vamana the Dwarf.

Q:What is Sigmund Freud's middle name?

A:Schlomo

(etc.)
Q:FAQ-END

Work in progress can go here and won't be displayed. (optional)



Available open/closed image pairs
---------------------------------

Open            Closed
----------------------
minus.png        plus.png
minus2.png       plus2.png  (these have outlines)
check.png        x.png

Image URL full example:

[[EZfaq? &ezfaqDocID=`12` &statusOpenHTML=`<img srcEQUALS"assets/components/ezfaq/images/minus.png">` &statusClosedHTML=`<img srcEQUALS"assets/components/ezfaq/images/plus.png">`]]


Styling EZfaq
-------------
Some styling is accomplished with the parameters in the snippet call. Other styling issues require changes to the file:
/assets/components/ezfaq/ezfaq.css



Using Flash Videos and lightbox in the FAQ
An FAQ answer can be in the form of a Flash video using the MODX swfObject snippet (http://modxcms.com/SWFObject-1815.html) and the following format:

Q:How do I play Flash in here?

A:[[swfObject? &swfid=`0` &swfFile=`assets/flash/playback.swf` &swfWidth=`325` &swfHeight=`155`]]

For lightbox images, use:

Q:How do I work with lightbox?

A:<a href="http://domain.com/assets/images/small-pic.jpg" rel="lightbox"><img src="http://domain.com/assets/images/large-pic.jpg" alt="" width="120" height="120" border="0" /></a>

***************************************************
*             Code begins here                    *
***************************************************/
/* @var $scriptProperties array */
/* @var $modx modX */

/* set path to resources */
$faqPath = $modx->getOption('faqPath',$scriptProperties,$modx->getOption('base_url') . 'assets/components/ezfaq/');

$error_message = '';

/* get the lexicon entries for the EZfaq prompts */

$modx->lexicon->load('ezfaq:default');

$prefix = $modx->getVersionData()['version'] >= 3
    ? 'MODX\Revolution\\'
    : '';
/* ********************************************
Make sure we have what we need before proceeding
***********************************************/

if (!isset($ezfaqDocID)) { /* User didn't send docID parameter */
    return $modx->lexicon('ezfaq-docID-required');
}

$resource = $modx->getObject($prefix . 'modResource',$ezfaqDocID);
if ($resource == null) { /* user requested a non-existing document */
   return $modx->lexicon('ezfaq-doc-not-found');
}

/****************************************************************************************
Now that we have the language file and the document with the FAQ
content, we'll plug in the .css and .js and  initialize the optional parameters.
*****************************************************************************************/

/*  Inject .css file into document header - maybe.
    Use $cssPath=`` if you want to put the .css in your site's .css file
    rather than using a separate .css file for the FAQ  */

$cssPath = $modx->getOption('ezfaqCssPath',$scriptProperties,'');

if (!empty($cssPath)) {    /* user has set this parameter */
    /* user has specified the .css file to use */
    $modx->regClientCSS($cssPath);
} else { /* not set, use the default .css file */
    $modx->regClientCSS($faqPath.'ezfaq.css');
}

/* inject the .js into the document header. */
$modx->regClientStartupScript($faqPath.'switchcontent.js');


/*Show the buttons that show and hide all answers. */
$showHideAllOption = $modx->getOption('showHideAllOption',$scriptProperties,true);

/* Set prefix markers for open and closed answers (can be string or an image URL). Defaults to plus and minus signs. */
$statusOpenHTML = $modx->getOption('statusOpenHTML',$scriptProperties,'[-]');
$statusClosedHTML = $modx->getOption('statusClosedHTML',$scriptProperties,'[+]');

/* Set colors for open and closed (applies to question; answer is styled in .css). Can be color name or hex color value (#ffffff); */
$openColor = $modx->getOption('openColor',$scriptProperties,'red');
$closedColor = $modx->getOption('closedColor',$scriptProperties,'black');

/* set whether state persists on return visits  */
$setPersist = $modx->getOption('setPersist',$scriptProperties,'true');

/* when set to true, only one answer can be expanded at a time (default) */
$collapsePrevious = $modx->getOption('collapsePrevious',$scriptProperties,'true');

/* expand answers n1 through n2 when page is opened (default, no)to set, use "0,2" to expand first three answers, "0" to expand just the first */
$defaultExpanded = $modx->getOption('defaultExpanded',$scriptProperties,'');

/* debug setting */
$debug = $modx->getOption('debug',$scriptProperties,false);

/************************************
 *  Work starts here
*************************************/

/* replace "EQUALS" in URLs with "=" */
$statusOpenHTML = str_replace("EQUALS","=",$statusOpenHTML);
$statusClosedHTML = str_replace("EQUALS","=",$statusClosedHTML);

$output = '';

$docString = $resource->get('content');
/* if $showHideAllOption == true, this puts the show all/hide all buttons
 * at the top of the page.
 */

if ($showHideAllOption) {
    $output .= '<div class="faqExpand">';
    $output .= '<p>'.$modx->lexicon('ezfaq-show-hide-msg').'</p>';

    $output .= '<a href="javascript:faq.sweepToggle('."'expand')".'"> '.$modx->lexicon('ezfaq-expand-button-msg').' </a>';
    $output .='&nbsp;&nbsp;&nbsp;&nbsp;';
    $output .= '<a href="javascript:faq.sweepToggle('."'contract')".'"> '.$modx->lexicon('ezfaq-contract-button-msg').' </a>';

    $output .= '</div>';

}



$docArray = explode("Q:",$docString);

/* for debugging  */

if ($debug) {
    $i = count($docArray);
    echo "count: ".$i.'<br />';
}

$itemCount=0; /* used to make every id different */


$output .= '<div class="faqContainer">'."\n";
foreach($docArray as $value) {

    if ($itemCount==0) {  /* very first item (or pre first item) */
        if (!strstr($value,"A:")) {   /* no answer, assume it's before the beginning */
           $output .= $value;  /* send it out without processing */
           continue;  /* got to the next (first real item) */
        }
    } else {
        /* normal item, continue */
    }
  $items = explode("A:",$value);
      if ( (isset($items[0]) && stristr($items[0],"FAQ-END") ) || stristr($items[0],"END-FAQ")) {
         break;
      }

  if ( (isset($items[0]) && $items[0] != "") && (isset($items[1]) && $items[1] != "") ) {

     $output .= '<p id="faq'.$itemCount.'-title" class="handcursor">';
     $output .= '<span class="faqQuestion">';
     $output .= $items[0];
     $output .= '</span>';
     $output .= '</p>';
     $aArray = explode("Extra:",$items[1]);
     $output .= '<div id="faq'.$itemCount.'" class="switchgroup1">';
     $output .= $aArray[0];
     $output .= '</div>';

     if ( isset($aArray[1]) && ($aArray[1] != "")) {
       $output .= $aArray[1];
     }

  }
  $itemCount++;
}
$output .= '</div>'."\n"; /* end of faqContainer div */

/*   MAIN FUNCTION: new switchcontent("class name", "[optional_element_type_to_scan_for]") REQUIRED
1) Instance.setStatus(openHTML, closedHTML)- Sets optional HTML to prefix the headers to indicate open/closed states
2) Instance.setColor(openheaderColor, closedheaderColor)- Sets optional color of header when it's open/closed
3) Instance.setPersist(true/false)- Enables or disabled session only persistence (recall contents' expand/contract states)
4) Instance.collapsePrevious(true/false)- Sets whether previous content should be contracted when current one is expanded
5) Instance.defaultExpanded(indices)- Sets contents that should be expanded by default (ie: 0, 1). Persistence feature overrides this setting!
6) Instance.init() REQUIRED
*/

$output .= '<script type="text/javascript">;'."\n";
$output .= 'var faq=new switchcontent("switchgroup1", "div");'."\n";  /* Limit scanning of switch contents to just "div" elements */


$output .= "faq.setStatus('".$statusOpenHTML." ','".$statusClosedHTML." ');"."\n"; /* set open/closed indicators */
$output .= 'faq.setColor("'.$openColor.'", "'.$closedColor.'");'."\n"; /* set open/closed text colors */
$output .= 'faq.setPersist('.$setPersist.');'."\n";
$output .= 'faq.collapsePrevious('.$collapsePrevious.');'."\n"; /* Only one content open at any given time */
$output .= 'faq.defaultExpanded("'.$defaultExpanded.'");'."\n"; /* expand some on open?  */
$output .= 'faq.init();'."\n";
$output .= '</script>'."\n";

return $output;