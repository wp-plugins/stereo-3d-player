<?php
/*
Plugin Name: Stereo 3D Player (Lite Edition)
Plugin URI: http://www.stereo3dweb.com/stereo3dplayer/
Description: Insert a resizable floating player window (capable for stereo 3D photo slideshow on polarized displays) to any post. 
Version: 1.1
Author: Xi Liu
Author URI: http://www.stereo3dweb.com/liuxi0099/
License: Apache License, Version 2.0
*/

/* when activate this plugin */
register_activation_hook( __FILE__, 'StereoCanvasPlayer_install'); 

/* when deactivate this plugin */
register_deactivation_hook( __FILE__, "StereoCanvasPlayer_remove" );

function StereoCanvasPlayer_install() {
	add_option( "StereoCanvasPlayerEnabled", "yes", "", "yes" );
}

function StereoCanvasPlayer_remove() {
	delete_option( "StereoCanvasPlayerEnabled" );
}

function countLabel_3dLabel( $inputContent, $Label ) {
	return $numLabel = substr_count( $inputContent, $Label );
}

/* var1=content var2=1:left, 0:right*/
function legalLagel_3dLabel( $inputContent, $leftTag ) {
	/*  0: label not match */
	/* -1: no label 	 	 */
	/* -2: no URL		 */
	/* -3: URL illegal	 */
	
	if ( $leftTag ) {
		$LabelStart = "[3dL]";
		$LabelEnd = "[/3dL]";
	} else {
		$LabelStart = "[3dR]";
		$LabelEnd = "[/3dR]";
	}
	
	/* if (3dX) doesn't equal to (/3dX) */
	if ( countLabel_3dLabel( $inputContent, $LabelStart ) == countLabel_3dLabel( $inputContent, $LabelEnd) ) {
		if ( countLabel_3dLabel( $inputContent, $LabelStart ) > 0) {
			/* check string length in label. */
			$legalLabel3dLabel = strpos($inputContent,$LabelEnd) - strpos($inputContent,$LabelStart) - 5;
			
			if ( $legalLabel3dLabel) {
				/* get effective URL*/
				$leftImageURL = substr($inputContent,strpos($inputContent,$LabelStart)+5,$legalLabel3dLabel);
				
				/* judge URL */
				$legalURL = preg_match("/^http/",$leftImageURL);
				
				if ($legalURL) {return 1;}
				else {return -3;}
			} else {return -2;}
		} else {return -1;}
	} else {return 0;}
}

function getURL_3dLabel( $inputContent, $leftTag ) {
	if ( $leftTag ) {
		$LabelStart = "[3dL]";
		$LabelEnd = "[/3dL]";
	} else {
		$LabelStart = "[3dR]";
		$LabelEnd = "[/3dR]";
	}
	
	if ( legalLagel_3dLabel( $inputContent, $leftTag ) == 1) {
		/* check string length in label. */
		$legalLabel3dLabel = strpos($inputContent,$LabelEnd) - strpos($inputContent,$LabelStart) - 5;		
		$leftImageURL = substr($inputContent,strpos($inputContent,$LabelStart)+5,$legalLabel3dLabel);
		return $leftImageURL;
	}
}

function deleteURL_3dLabel( $inputContent, $leftTag ) {
	if ( $leftTag ) {
		$LabelStart = "[3dL]";
		$LabelEnd = "[/3dL]";
		$errorLabel = "3dL";
	} else {
		$LabelStart = "[3dR]";
		$LabelEnd = "[/3dR]";
		$errorLabel = "3dR";
	}
	
	if ( legalLagel_3dLabel( $inputContent, $leftTag ) == 0) {
		return "Error 0: ".$errorLabel." labels does not match. ";
	} else if ( legalLagel_3dLabel( $inputContent, $leftTag ) == -1 ) {
		return $inputContent;
		//return "Error 1: no ".$errorLabel." label";
	} else if ( legalLagel_3dLabel( $inputContent, $leftTag ) == -2 ) {
		return "Error 2: no URL in ".$errorLabel." label";
		//$toDelete = $LabelStart.$LabelEnd;
		//$afterDeletion = str_replace($toDelete, "", $inputContent);
	} else if (legalLagel_3dLabel( $inputContent, $leftTag ) == -3) {
		return "Error 3: URL illegal in ".$errorLabel." label";
		//$afterDeletion = $inputContent;
	} else {
		/*delete original sentence*/
		$toDelete = $LabelStart.getURL_3dLabel( $inputContent, $leftTag ).$LabelEnd;
		$afterDeletion = str_replace($toDelete, "", $inputContent);
		return $afterDeletion;
	}
}

function loadURLandPlayer( $content ) {
	/*prepare to restore some URLs*/
	$json_leftImageURL = array();
	$json_rightImageURL = array();
	
	/*for left eye*/
	$leftTag = 1;
	$numLoop = countLabel_3dLabel( $content, "[3dL]" );	
	for ($i = 0; $i < $numLoop; $i++) {
		if ( legalLagel_3dLabel( $content, $leftTag ) != 1) {
			$content = deleteURL_3dLabel( $content, $leftTag );
			break;
		} else {
			$leftImageURL[ $i ] = getURL_3dLabel( $content, $leftTag );
			$content = deleteURL_3dLabel( $content, $leftTag );
		}
	}
	
	/*for right eye*/
	$leftTag = 0;
	$numLoop = countLabel_3dLabel( $content, "[3dR]" );	
	for ($i = 0; $i < $numLoop; $i++) {
		if ( legalLagel_3dLabel( $content, $leftTag ) != 1) {
			$content = deleteURL_3dLabel( $content, $leftTag );
			break;
		} else {
			$rightImageURL[ $i ] = getURL_3dLabel( $content, $leftTag );
			$content = deleteURL_3dLabel( $content, $leftTag );
		}
	}
	
	/*json encoding for php->javascript*/
	$json_leftImageURL = json_encode( $leftImageURL );
	$json_rightImageURL = json_encode( $rightImageURL );

	/*load player html*/
	return $content."
	<link rel='stylesheet' type='text/css' href='http://www.stereo3dweb.com/pluginResources/canvasContainer.ver.1.2.css'></link>
	<script type='text/javascript' src='http://www.stereo3dweb.com/pluginResources/stereoImagePlayer.ver.1.2.js'></script>
	<script type='text/javascript' src='http://www.stereo3dweb.com/pluginResources/canvasContainer.ver.1.2.js'></script>
	<div id='canvasContainer'>
		<a id='canvasTitleMin' href='javascript:canvasContainerInit(false);'>MIN</a>
		<div id='canvasTitle'>Stereo 3D Player</div>
		<div id='canvasContent'>
			<div id='buttonZone'>
				<br/>
				<input type=button onClick='stereoImagePlayerPrev();' value='Prev'><br/>
				<input type=button onClick='stereoImagePlayerSwap();' value='Swap'><br/>
				<input type=button onClick='stereoImagePlayerNext();' value='Next'>
			</div>
			<div id='description'></div>
			<div id='canvasZone'>
				<canvas id='leftCanvas' width='1920' height ='1080' hidden='hidden'></canvas>
				<canvas id='rightCanvas' width='1920' height ='1080' hidden='hidden'></canvas>
				<canvas id='stereoImagePlayer' width='1920' height ='1080'>
					loading...
				</canvas>
			</div>
		</div>
	</div>
	<script type='text/javascript' >
		var imageLeft = $json_leftImageURL;
		var imageRight = $json_rightImageURL;
		if (imageLeft) {stereoImagePlayer(imageLeft[currentImage],imageRight[currentImage]);}
		canvasContainerInit(true);
		
	</script>";
}

add_filter( 'the_content',  'loadStereoCanvasPlayer' );

function loadStereoCanvasPlayer( $content ) {
	/*only load player when the single post is shown*/
	if( is_single() && get_option("StereoCanvasPlayerEnabled") && (legalLagel_3dLabel( $content, true ) != -1) ) {
		$content = loadURLandPlayer( $content );
		return $content;
	} else {return $content;}
}

?>