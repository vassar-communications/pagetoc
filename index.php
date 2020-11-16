<?php
/**
 * Plugin Name: PageTOC
 * Version: 1.1
 * Plugin URI: https://www.csilverman.com/
 * Description: A super-simple plugin to auto-generate a page index.
 * Author: Chris Silverman
 * Author URI: https://www.csilverman.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * @package WordPress
 * @author Chris Silverman
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



//	This page has a custom field, has-index, that contains "true" (or any other text).
//	So it should have an index like an FAQ page.

function instandex__page_has_index() {
	return get_post_meta( get_queried_object_id(), 'has-index', true );
}

function instandex__page_tagType() {
	//	By default, instandex indexes anything with an h2 tag
	//	However, you can change this to an h3
	return get_post_meta( get_queried_object_id(), 'instandex_tagToQuery', true );
}

/*

Set up custom field to include JSON settings
- ordered list vs unordered
- use dropdowns

*/

add_filter( 'the_content', 'add_toc_to_content', 1 );


function slugify_local($string){
	//	Get rid of multiple spaces
	$final_string = str_ireplace('  ', ' ', $string);

	//	target all alphanumerics
	//	https://stackoverflow.com/a/17947853/6784304
	$replace_pattern = '/\W|_/';
    $final_string = strtolower(trim(preg_replace($replace_pattern, '-', $final_string), '-'));

	//	get rid of multiple hyphens because I'm OCD about this sort of thing
	//	https://stackoverflow.com/a/29865564/6784304	
    $final_string = preg_replace('/-+/', '-', $final_string);
    
    return $final_string;
}


//	https://stackoverflow.com/questions/27786998/php-nested-array-into-html-list
function printArrayList($array) {
    echo "<ul>";

    foreach($array as $k => $v) {
        if (is_array($v)) {
            echo "<li>" . $k . "</li>";
            printArrayList($v);
            continue;
        }

        echo "<li>" . $v . "</li>";
    }

    echo "</ul>";
}


function add_toc_to_content( $content ) {
	if ( is_singular() && in_the_loop() && is_main_query() && instandex__page_has_index() ) {


		//	The overall process:
		//	- Create a new DOMDocument instance, and pass it the content of this page or post.
		//	- Write an xpath query to grab from that all h1-h6 elements
		//	-  

		//	Remember that the "document" here is not the full page of HTML; it is
		//	only the HTML that comprises the body of the page or post. So no containing divs or
		//	anything; it's just the header tags, paragraphs, etc.

		//	Given that the content I'm working with doesn't have a defined root, I need an anchor
		//	of sorts to add the generated index to once it's been completed.
		
		$content = '<div id="table-of-contents"></div>'.$content;
		
//	https://stackoverflow.com/questions/4912275/automatically-generate-nested-table-of-contents-based-on-heading-tags

		$doc = new DOMDocument();
		@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);

//		$object = $doc->createElement('div');


//		$doc->loadHTML($code);
		
		// create document fragment
		$frag = $doc->createDocumentFragment();
		// create initial list
		$frag->appendChild($doc->createElement('ol'));
		
		
		$head = &$frag->firstChild;
		$xpath = new DOMXPath($doc);
		$last = 1;
		$iteration = 1;
		// get all H1, H2, …, H6 elements
		// https://devhints.io/xpath
		foreach ($xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $headline) {

		    //	get level of current headline
		    //	Iterate across the list of $headline objects returned by the xpath query.
		    
			//	sscanf takes three parameters:
			//	$headline->tagName is the name of the tag, extracted from the current headline object
		    //	h%u is the pattern sscanf looks for. This tells sscanf() to grab anything 
		    //		matching the pattern 'h' with a number (an unsigned integer, hence the %u)
		    //	$curr looks like a parameter, but it's the variable containing whatever %u turns out to be.
		    
		    //	So the following sscanf() function takes a tagname, extracts the specific level - the number - and
		    //	returns the number as the variable $curr.
		    
		    sscanf($headline->tagName, 'h%u', $curr);
		
		    // move head reference if necessary
		    
		    //	If the current header level is less than the last one (say, the last one was
		    //	an h3 and we're on an h2) 
		    if ($curr < $last) {
		        // move upwards
		        for ($i=$curr; $i<$last; $i++) {
			        
			        
		            $head = &$head->parentNode->parentNode;
		        }
		    } else if ($curr > $last && $head->lastChild) {
		        // move downwards and create new lists
		        for ($i=$last; $i<$curr; $i++) {
		            $head->lastChild->appendChild($doc->createElement('ol'));
		            $head = &$head->lastChild->lastChild;
		        }
		    }
		    $last = $curr;

			//	Create the id
			$header_id = slugify_local($headline->textContent);
			$header_id = 's-'.$iteration.'-'.$header_id;

		
		    // add list item
		    $li = $doc->createElement('li');
		    $head->appendChild($li);
		    $a = $doc->createElement('a', $headline->textContent);
		    $head->lastChild->appendChild($a);
			$a->setAttribute('href', '#'.$header_id);
		
			//	Now make sure each header has a proper ID so it can be linked to.
		    $headline->setAttribute('id', $header_id);

/*		
		    // build ID
		    $levels = array();
		    $tmp = &$head;
		    // walk subtree up to fragment root node of this subtree
		    while (!is_null($tmp) && $tmp != $frag) {
		        $levels[] = $tmp->childNodes->length;
		        $tmp = &$tmp->parentNode->parentNode;
		    }
		    $id = 'sect'.implode('.', array_reverse($levels));
		    // set destination
		    $a->setAttribute('href', '#'.$id);
		    // add anchor to headline
		    $a = $doc->createElement('a');
		    $a->setAttribute('name', $id);
		    $a->setAttribute('id', $id);
		    $headline->insertBefore($a, $headline->firstChild);
*/

			$iteration++;
		}
		
		// append fragment to document.
		//	I need to place the xpath fragment at the beginning of it.

		$doc->getElementByID('table-of-contents')->appendChild($frag);

//		$doc->getElementsByTagName('body')->item(0)->appendChild($frag);
		// echo markup

		$content = $doc->saveHTML();
//		return $content;
	}
	return $content;
    
}





// echo get_post_meta( $post->ID, 'has-index', true );

/*
if($page->'has-index') {
	echo "eeee";
}
*/