<?php
$path = realpath(dirname(__FILE__)."/..");
if (!file_exists($path."/data")) mkdir($path."/data");
if (!file_exists($path."/data/init")) mkdir($path."/data/init"); 
?><html>
<head>
<title>PN Students Management Software - Installation</title>
<script type='text/javascript' src='deploy_utils.js'></script>
<style type='text/css'>
html, body {
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}
html, body, table {
	font-family: Verdana;
}
#container {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	background-color: #D0D0D0;
}
#box {
	border: 1px solid #22bbea;
	border-radius: 5px;
	box-shadow: 5px 5px 5px 0px #808080;
}
#title {
	border-bottom: 1px solid #22bbea;
	font-weight: bold;
	text-align: center;
	font-size: 14pt;
	background-color: #22bbea;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	padding: 3px;
	color: white;
	text-shadow: #40a0c0 0.1em 0.1em 0.1em;
}
#content {
	padding: 10px;
	font-size: 10pt;
	background-color: white;
}
#box>#content:last-child {
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}
#footer {
	border-top: 1px solid #22bbea;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
	padding: 5px;
	background-color: white;
}
button {
    padding: 1px 3px 1px 3px;
    border-radius: 7px;
    background: linear-gradient(to bottom, #3498db, #2980b9);
    color: white;
    font-size: 9pt;
    font-weight: bold;
    text-transform: uppercase;
    border: 1px solid rgba(0,0,0,0);
	text-align: center;
    box-shadow: 1px 1px 1px rgba(170,170,170,0);
    display: inline-block;
    font-family: Arial;
    white-space: nowrap;
    cursor: pointer;
    margin: 1px;
    outline: 1px solid rgba(0,0,0,0);
}
button:hover {
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 1px 1px 1px rgba(170,170,170,0.6);
    background: linear-gradient(to bottom, #44a8eb, #3990c9);
}
button:active {
    box-shadow: 0px 0px 0px rgba(170,170,170,0);
    position: relative;
    top: 1px;
    left: 1px;
}
button:focus {
	outline: 1px dotted #404080;
}
</style>
</head>
<body>
<div id='container'>
	<div id='box'>
		<div id='title'>
			Students Management Software - Installation
		</div>
		<div id='content'>
<?php 
if (!file_exists(realpath(dirname(__FILE__))."/conf/channel")) {
	if (isset($_GET["channel"])) {
		$f = fopen(realpath(dirname(__FILE__))."/conf/channel", "w");
		fwrite($f, $_GET["channel"]);
		fclose($f);
	} else { ?>
		<form method="GET">
		Which channel do you want to install ?<br/>
		<input type='radio' name='channel' value='stable' checked='checked'/> Stable (recommended)<br/>
		<input type='radio' name='channel' value='beta'/> Beta (for tests purpose only)<br/>
		<input type='submit' value='Ok'/>
		</form>
	<?php }
}
?>
		</div>
	</div>
</div>
<?php if (file_exists(realpath(dirname(__FILE__))."/conf/channel")) { ?>
<script type='text/javascript'>
var content = document.getElementById('content');
function request(url,params,handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","bridge.php", true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else
			handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(url)+params;
	xhr.send(data);
}
<?php require_once("update_urls.inc");?>
function getLatestVersion() {
	content.innerHTML = "Retrieving latest version...";
	getURLFile("bridge.php", <?php echo json_encode(getLatestVersionURL());?>, function(error,res) {
		if (error) { content.innerHTML = "Error: "+res; return; }
		var version = res;
		content.innerHTML = "Latest version: "+version+"<br/>Downloading Students Management Software "+version+": ";
		var span_progress = document.createElement("SPAN");
		content.appendChild(span_progress);
		var url = <?php echo json_encode(getGenericUpdateURL());?>;
		url = url.replace("##FILE##","Students_Management_Software_"+version+".zip");
		<?php if (isset($_GET["speed"])) echo "window.download_init_speed = ".$_GET["speed"].";";?>
		download("bridge.php", url, "Students_Management_Software_"+version+".zip", span_progress, function(error) {
			if (error) {
				content.innerHTML = "Latest version: "+version+"<br/>";
				content.innerHTML += "Error downloading: "+error+"<br/>";
				var retry_button = document.createElement("BUTTON");
				retry_button.innerHTML = "Retry";
				retry_button.onclick = function() { getLatestVersion(); };
				content.appendChild(retry_button);
				return;
			}
			url = <?php echo json_encode(getGenericUpdateURL());?>;
			url = url.replace("##FILE##","Students_Management_Software_"+version+"_init_data.zip");
			content.innerHTML = "Software downloaded.<br/>Downloading initial data: ";
			span_progress = document.createElement("SPAN");
			content.appendChild(span_progress);
			download("bridge.php", url, "Students_Management_Software_"+version+"_init_data.zip", span_progress, function(error) {
				if (error) {
					content.innerHTML = "Latest version: "+version+"<br/>";
					content.innerHTML += "Error downloading: "+error+"<br/>";
					var retry_button = document.createElement("BUTTON");
					retry_button.innerHTML = "Retry";
					retry_button.onclick = function() { window.location.reload(); };
					content.appendChild(retry_button);
					return;
				}
				content.innerHTML = "Version "+version+" downloaded.<br/>Extracting files...";
				request("Students_Management_Software_"+version,"&unzip=true",function(error,res) {
					if (error) { content.innerHTML += "<br/>Error: "+res; return; }
					window.location.href = "/";
				});
			});
		});
	});
}
getLatestVersion();
</script>
<?php } ?>
</body>
</html>