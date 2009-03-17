<?php
if (isset($_REQUEST['imageProxy'])) {
	if (!isset($_SERVER['HTTP_REFERER'])) die();
	$url = parse_url($_SERVER['HTTP_REFERER']);
	$self = $_SERVER['SCRIPT_NAME'];
	$self = str_replace("index.php", "", $self);
	if ($url['path'] != $self) die();
	
	$ch = curl_init($_REQUEST['url']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_exec($ch);
	die();
}

if (isset($_REQUEST['getImages'])) {

if (trim($_REQUEST['words']) == "") {
	$_REQUEST['words'] = <<<END
paul tarjan
searchmonkey
END;

}
$words = explode("\n", $_REQUEST['words']);
$images = array();
foreach ($words as $word) {

	$url = "http://query.yahooapis.com/v1/public/yql?q=";
//	$q = "select * from flickr.photos.sizes where photo_id in (select id from flickr.photos.search(10) where text='$word') and label='Large'";
	$num = $_REQUEST['num'] ? (int) $_REQUEST['num'] : 1;

	$queryWord = $word;
	preg_match("/[(](.*)[)]/", $word, &$matches);
	if (count($matches) == 2) {
		$n = (int) $matches[1];
		if (trim($n) !== "") {
			$num = $n;
			$queryWord = preg_replace("/[(](.*)[)]/", "", $word);
		}
	}
	if (trim($queryWord) == "") continue;
		
	$q = "select url from search.images($num) where query='" . urlencode($queryWord) . "' | tail(count=1)";
	$fmt = "xml";
	$yql = $url.urlencode($q)."&format=$fmt";

	$x = simplexml_load_file($yql);

	foreach ($x->results->result as $p) {
		$url = (string) $p->url;
		if (!isset($_REQUEST['noProxy']))
			$url = "?imageProxy=true&url=" + urlencode($url);
		$images['urls'][] = $url;
		$images['words'][] = (string) $word;
	}
};

print json_encode($images);
die();
}

?>
<?php print '<?xml version="1.0" encoding="UTF-8"?>' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>SlideMonkey</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="shortcut icon" href="http://developer.search.yahoo.com/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.0.0pr2/build/cssreset/reset-min.css" />
		<style type="text/css">
html, body, #wrapper {
	height: 100%;
}
#wrapper {
	background-color:	black;
	text-align: center;
}
#word {
	color:			white;
}
#image {
	height: 		95%;
	overflow:		none;
}
#words {
	width:			100%;
}
#images {
	display:		none;
}
.controls {
	margin:			5px;
	padding:		5px;
<?php if (isset($_REQUEST['noControls'])) { ?>
	display:		none;
<?php } ?>
}
		</style>
	</head>
	<body class="yui-skin-sam">
		<div id="images"></div>
		<div id="wrapper">
			<div id='word'>Search Term</div>
			<img id='image' />
		</div>

		<div class="controls">
			<form id='f' action="">
				<textarea id='words' rows="10" cols="200">
<?php
if (isset($_REQUEST['words'])) { print $_REQUEST['words']; } else {
?>
searchmonkey logo
hand sewing needle
sitting
stanford logo
thinking
world
massage knead
brain
morning
lightbulb
paris hilton
puppies
computer
telepathy
rubik's cube mixed up
puppies
not useful
lucky horseshoe
morning
stupid
google
identical twins
yahoo instant search
grandmother
pimp my ride
fast forward
interview
yahoo search
amit kumar
lightbulb
cupid
clock hour
insert into a linked list
coles notes
job offer
dollar signs
brain
technology
jump to conclusions mat -homemade
searchmonkey banana
programming
production system
cool
graduating
jerry yang
lightbulb
leadership
the end
<?php } ?>
				</textarea>
				<div class="controls">
					<input type='submit' value="Reload Slideshow" />
					Link to: <a id='wordslink'>these words</a> or <a id='imageslink'>these images</a>. Help: Any key = forward, left / up arrow = back, ctrl + arrows change picture, put (n) after query for nth entry. <a href='http://github.com/ptarjan/slidemonkey/'>Open Source</a>
				</div>
			</form>
		</div>

		<script src="http://yui.yahooapis.com/3.0.0pr2/build/yui/yui-min.js" type="text/javascript"></script> 

		<script type="text/javascript">
var images = {"urls" : [], "words" : []}
var curImage = -1;
var loadingImage = "http://l.yimg.com/a/i/eu/sch/smd/busy_twirl2_1.gif";

YUI({filter:'raw'}).use("node", "io-base", "json", function(Y) {
	var putImage = function() {
		var image = Y.get("#image");
		image.set("display", "none");
		image.set("src", images.urls[curImage]);
		image.setStyle("height", "95%");
		Y.get("#word").set("innerHTML", images.words[curImage]);
		
		/*
		I never got this working. Please try if you want :) scale images either height or width
		var ev = Y.on("load", function (e) {
			Y.detach(ev);
			image.setStyle("width", "");
			image.setStyle("height", "95%");
			Y.log(image.get("width") + " " + image.get("winWidth"));
			if (image.get("width") * 0.90 >= image.get('winWidth')) {
				image.setStyle("width", "95%");
				image.setStyle("height", "");
			};
			image.set("display", "inline");
		}, image);
		*/

	}
	var enabled = true;
	Y.on("focus", function (e) {
		Y.log("disabling keypress");
		enabled = false;
	}, "#words");
	Y.on("blur", function (e) {
		Y.log("enabling keypress");
		enabled = true;
	}, "#words");
	function updateWordsLink() {
		Y.get("#wordslink").set("href", "?words=" + encodeURIComponent(Y.get("#words").get("value")));
	}
	function updateImagesLink() {
		Y.get("#imageslink").set("href", "?images=" + Y.JSON.stringify(images));
	}
	function updateWords() {
		Y.get("#words").set("value", images.words.toString().replace(/,/g, "\n"));
		updateWordsLink();
	}
	updateWordsLink();
	Y.on("keydown", function(e) {
		updateWordsLink();
	}, "#words");

	// Meta -> or -<
	var getNextImage = function (index, direction) {
		var n = images.words[index].match(/\(.+\)/);
		if (n == null) n = 1;
		else n = parseInt(n[0].replace("(", "").replace(")", ""));

		n += direction;
		if (n < 1) n = 99;
		if (n > 99) n = 1;

		var boom = images.words[index].replace(/\s*\(.+\).*/, "");

		if (n != 1)  boom += " (" + n + ")";
		images.words[index] = boom;

		var url = "?getImages=true&words=" + encodeURIComponent(images.words[index]);

		var ev = Y.on('io:complete', function(id, o, args) {
			Y.detach(ev);
			if (o.responseText == "") return;
			images.urls[index] = Y.JSON.parse(o.responseText).urls[0];
			updateImagesLink();
			putImage();
		}, this, []);

		updateWords();
		Y.get("#word").set("innerHTML", "Fetching image");
		Y.get("#image").set("src", loadingImage);
		var request = Y.io(url);
	}

	// Keyhandler
	Y.on("keydown", function (e) {
		if (! enabled) return;
		if (typeof(e.keyCode) == "undefined") e.keyCode = e.charCode;
		if ((e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) && (e.keyCode >= 37 && e.keyCode <= 40)) {	
			e.halt();
			if (e.keyCode == "37" || e.keyCode == "38") var direction = -1;
			else var direction = 1;
			getNextImage(curImage, direction);
			return;
		}

		if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
		if (
			(e.keyCode < 65 || e.keyCode > 65 + 26) &&  // lowercase characters
			e.keyCode != 32 && // space
			(e.keyCode < 37 || e.keyCode > 40) && // arrows
			(e.keyCode < 48 || e.keyCode > 48 + 10) && // numbers
			e.keyCode != 13 && // enter
			true
		) return;
		if (images.urls.length == 0) return;
		if (e.keyCode == "37" || e.keyCode == "38")
			curImage = (curImage - 1 + images.urls.length) % images.urls.length;
		else
			curImage = (curImage + 1) % images.urls.length;
		putImage();
		e.halt();
	}, document);

	// Wrapper for all the stuff to do once the images come in
	var loadImages = function (e) {
		// Preload
		for (var id in images.urls) {
			var img = document.createElement("img");
			var url = images.urls[id];
			img.src = url;
			Y.get("#images").appendChild(img);
		}
		Y.get("#word").set("innerHTML", "READY!");
		Y.log("Images ready");
		Y.log(images);
		updateImagesLink();
		curImage = 0;
		putImage();
	}
	
	var getImages = function (e) {
		if (e && e.halt) e.halt();
		var url = "?getImages=true&words=" + encodeURIComponent(Y.get("#words").get("value"));

		var ev = Y.on('io:complete', function(id, o, args) {
			Y.detach(ev);
			if (o.responseText == "") return;
			Y.log("Overwriting images");
			images = Y.JSON.parse(o.responseText);
			loadImages();
		}, this, []);
		Y.get("#word").set("innerHTML", "Fetching images");
		Y.get("#image").set("src", loadingImage);
		var request = Y.io(url);
	}
	Y.on("submit", getImages, "#f");
	
	Y.on("domready", function() {
<?php if (isset($_REQUEST['images'])) { ?>
		var images = <?php print json_encode($_REQUEST['images']) ?>;
		updateWords();
		loadImages();
<?php } else { ?>
		getImages();
		// Y.Event.simulate(Y.get("#form"), "submit");
<?php } ?>
	});
});
		</script>
	</body>
</html>
