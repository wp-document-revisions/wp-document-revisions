<?php

class WP_Test_Document_Front_End extends WPTestCase {

	function test_revisions_shortcode() {
   		$tdr = new WP_Test_Document_Revisions();
		//$docID = $tdr->test_add_document();
		
		//$output = do_shortcode( '[document_revisions number="2" id="' . $docID . '"' );
	}
	
	function test_document_shortcode() {
   		$tdr = new WP_Test_Document_Revisions();
		//$docID = $tdr->test_add_document();
		//$output = do_shortcode( '[documents]' );

	}

}