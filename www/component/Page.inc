<?php
function page_shutdown_catch($page) {
	if ($page->done) return;
	$msg = "The page '".$page->path."' on component '".$page->component->name."' didn't finish correctly.";
	$error = error_get_last();
	if ($error <> null)
		$msg.= " Last error was in ".$error["file"]." line ".$error["line"].": ".$error["message"];
	PNApplication::error($msg);
	PNApplication::printErrors();
	$content = ob_get_clean();
	echo $content;
}

/**
 * Abstract class for all pages
 */
abstract class Page {

	/**
	 * Return an array: to access to this service, the user must comply with at least one of the element of this array.
	 * Each element can be either <ul>
	 * <li>the name of a right: the user needs this right</li>
	 * <li>a list of rights' name: the user needs all those rights</li>
	 * <li>a function: if it returns true, the user can access to the service</li>
	 * </ul>
	 */
	public abstract function getRequiredRights();

	/** Indicates if the page may update the session. If not, the session will be closed before the page execute, so parallel requests can be handled.
	 * @return boolean true if the session should not be closed before to execute the page
	 */
	public function mayUpdateSession() { return false; }
	
	/** Execute the page: generate its content */
	protected abstract function execute();
	
	/** @var string[] list of Javascript code to put in the head of the page */
	private $init_scripts = array();
	/** @var string[] list of Javascript file URL to load with the page */
	private $javascripts = array();
	/** @var string[] list of CSS file URL to load with the page */
	private $stylesheets = array();
	/** @var string Javascript to execute within onload event of the body */
	private $onload = "";
	/** @var Component component this page belongs to */
	public $component;
	/** @var string path of this page */
	public $path;
	/** @var boolean internal flag to know if the page execution successfully terminate or a fatal error occured */
	public $done = false;
	/** @var integer counter to generate unique ID */
	private $generate_id = 0;

	/** Add Javascript code that will be placed in the head section, meaning before the body is loaded.
	 * @param string $script Javascript code
	 */
	public final function addInitScript($script) { array_push($this->init_scripts, $script); }
	
	/** Add a javascript to be loaded with the page
	 * @param string $path URL of the script
	 * @param string $onload Javascript code to execute when the script is loaded
	 */
	public final function addJavascript($path, $onload = "") {
		$found = false;
		foreach ($this->javascripts as $js)
			if ($js[0] == $path) {
				$found = true;
				$js[1] .= $onload;
				break;		
			}
		if (!$found)
			array_push($this->javascripts, array($path,$onload));
	}
	/** @var string[] list of javascript files existing in the application */ 
	private $javascript_paths = null;
	/** Add a javascript to be load with the page
	 * @param string $js_filename Javascript filename (no path, only filename)
	 * @param string $onload Javascript code to be executed when the script is loaded
	 */
	public final function requireJavascript($js_filename, $onload = "") {
		if ($this->javascript_paths == null) {
#DEV
			$this->javascript_paths = array();
			foreach (PNApplication::$instance->components as $cname=>$c) {
				if (file_exists("component/$cname/static"))
					$this->browseJavascripts("component/$cname/static", $cname, "/");
			}
#END
#PROD
#$this->javascript_paths = include("component/javascript.paths");
#END
		}
			$this->addJavascript($this->javascript_paths[$js_filename], $onload);
	}
	/** Go through every component to find javascript files
	 * @param string $path current full path
	 * @param string $component current component name
	 * @param string $sub_path current path in the static directory of the component
	 */
	private function browseJavascripts($path, $component, $sub_path) {
		$d = opendir($path);
		while (($filename = readdir($d)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($path."/".$filename))
				$this->browseJavascripts($path."/".$filename, $component, $sub_path.$filename."/");
			else if (substr($filename,strlen($filename)-3) == ".js")
				$this->javascript_paths[$filename] = "/static/".$component.$sub_path.$filename;
		}
		closedir($d);
	}
	
	/** Add a CSS file to be loaded with the page
	 * @param string $path URL of the file
	 */
	public final function addStylesheet($path) {
		if (!in_array($path, $this->stylesheets))
			array_push($this->stylesheets, $path);
	}
	/** Add Javascript code to be executed when the page is loaded (onload event of the body)
	 * @param string $script Javascript code
	 */
	public final function onload($script) { $this->onload .= str_replace("\"", "&quot;", $script); }
	
	/** Generate a unique ID in the page (composed of the page path, and a unique integer)
	 * @return string the unique ID
	 */
	public final function generateID() {
		return str_replace("/", "_", str_replace(".", "_", $this->path))."_".($this->generate_id++);
	}

	/** Called by the Component, to generate the page
	 * @param Component $component the component this page belongs to
	 * @param string $path the path of this page
	 */
	public final function generate(&$component, $path) {
		$this->component = &$component;
		$this->path = $path;
		ob_start();
		register_shutdown_function("page_shutdown_catch", $this);
		try {
		/* DIM : execute page specific code (implemented in the inherited class) */ 
			$this->execute();
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		$content = ob_get_clean();
		ob_end_clean();
#DEV
		PNApplication::$instance->development->check("Page ".$path);
#END
		$this->startPage();
		echo $content;
		$this->endPage();
		$this->done = true;
	}

	/** Start the page, until the opening of the body tag */
	private function startPage() {
		theme::css($this, "global.css");
		pageHeaderStart();
		foreach ($this->init_scripts as $s) echo "<script type='text/javascript'>".$s."</script>";
		foreach ($this->javascripts as $js) {
			echo "<script type='text/javascript' src='".$js[0]."' onload='javascriptLoaded(this);".$js[1].";this._loaded=true;'>";
			echo "</script>";
		}
		foreach ($this->stylesheets as $css) echo "<link rel='stylesheet' href='".$css."' onload='if (window.layout && window.document.body) window.layout.changed(window.document.body); this._loaded = true;' media='all'/>";
		pageHeaderEnd($this->onload);
	}

	/** End the page: include errors if any, then close the body */
	private function endPage() {
		PNApplication::printErrors();
		pageFooter();
	}
	
	/**
	 * Performed the lock of the given data
	 * If well performed, the managing script is generated
	 * @param string $table table name
	 * @param string|null $row key of the row or null
	 * @param string|null $column column name or null
	 * @param string|null $sm sub model or null
	 * @return null|integer null if the lock cannot be performed, else the lock id 
	 */
	public final function performRequiredLocks($table, $row = null, $column = null, $sm = null, &$locked_by){
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		$locked_by = null;
		$name = null;
		$db_lock = null;
		//Call the matching method
		if($column <> null){
			if($row <> null){
				$db_lock = DataBaseLock::lockCell($table->getSQLNameFor($sm), $row, $column, $locked_by);
				$name = "cell";
			} else {
				$db_lock = DataBaseLock::lockColumn($table->getSQLNameFor($sm), $column, $locked_by);
				$name = "column";
			}
		} else if ($row <> null){
			$db_lock = DataBaseLock::lockRow($table->getSQLNameFor($sm), $row, $locked_by);
			$name = "row";
		} else {
			$db_lock = DataBaseLock::lockTable($table->getSQLNameFor($sm), $locked_by);
			$name = "table";
		}
		if ($db_lock <> null)
			DataBaseLock::generateScript($db_lock);
		return $db_lock;
	}
}

function pageHeaderStart() {
#DEV
	require_once("component/Component.inc");
	require_once("component/theme/theme.inc");
#END
#PROD
#require_once("component/PNApplication.inc");
#END
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
	<title>PN Students Management</title>
	<script type="text/javascript">
	if (window==window.top){location.href='/?page='+encodeURIComponent(location.pathname+location.search+location.hash);};
	</script>
	<script id='init_js_1' type="text/javascript" src="/static/javascript/utils.js" onload="this._loaded=true;"></script>
	<script id='init_js_3' type="text/javascript" src="/static/javascript/browser.js" onload="this._loaded=true;"></script>
	<script id='init_js_4' type="text/javascript" src="/static/javascript/utils_js.js" onload="this._loaded=true;"></script>
	<script id='init_js_5' type="text/javascript" src="/static/javascript/utils_html.js" onload="this._loaded=true;"></script>
	<script id='init_js_6' type="text/javascript" src="/static/javascript/utils_dom.js" onload="this._loaded=true;"></script>
	<script id='init_js_7' type="text/javascript" src="/static/javascript/utils_scroll.js" onload="this._loaded=true;"></script>
	<script id='init_js_8' type="text/javascript" src="/static/application/config.js.php" onload="this._loaded=true;"></script>
	<script id='init_js_9' type="text/javascript" src="/static/javascript/layout.js" onload="this._loaded=true;"></script>
	<script id='init_js_10' type="text/javascript" src="/static/application/application.js" onload="this._loaded=true;"></script>
	<script id='init_js_11' type="text/javascript" src="/static/theme/theme.js.php" onload="this._loaded=true;"></script>
	<script id='init_js_12' type="text/javascript" src="/static/javascript/ajax.js" onload="this._loaded=true;"></script>
	<script id='init_js_13' type="text/javascript" src="/static/application/service.js" onload="this._loaded=true;"></script>
	<script id='init_js_14' type="text/javascript" src="/static/widgets/common_dialogs.js" onload="this._loaded=true;"></script>
	<script id='init_js_15' type="text/javascript" src="/static/data_model/databaselock.js" onload="this._loaded=true;"></script>
	<script type="text/javascript">
	javascriptLoaded(document.getElementById('init_js_1'));
	javascriptLoaded(document.getElementById('init_js_3'));
	javascriptLoaded(document.getElementById('init_js_4'));
	javascriptLoaded(document.getElementById('init_js_5'));
	javascriptLoaded(document.getElementById('init_js_6'));
	javascriptLoaded(document.getElementById('init_js_7'));
	javascriptLoaded(document.getElementById('init_js_8'));
	javascriptLoaded(document.getElementById('init_js_9'));
	javascriptLoaded(document.getElementById('init_js_10'));
	javascriptLoaded(document.getElementById('init_js_11'));
	javascriptLoaded(document.getElementById('init_js_12'));
	javascriptLoaded(document.getElementById('init_js_13'));
	javascriptLoaded(document.getElementById('init_js_14'));
	javascriptLoaded(document.getElementById('init_js_15'));
	window._page_ready = false;
	window._page_ready_step = 0;
	function _pageReadyStep() {
		if (++window._page_ready_step == 2) {
			layout.changed(document.body);
			layout.forceLayout();
			window._page_ready = true;
		}
	}
	</script>
<?php 
}
function pageHeaderEnd($onload = "") {
?>
</head>
<body onload="<?php echo $onload;?>;window._pageReadyStep();">
<?php
}
function pageFooter() {
?>
<script type='text/javascript' defer>if (typeof window._pageReadyStep == 'function') window._pageReadyStep();</script>
<?php 
#DEV
require_once("component/data_model/DataBaseLock.inc");
$locks_ids = DataBaseLock::getCurrentLocksIds();
?>
<script type='text/javascript'>
var user_locks_ids = <?php echo json_encode($locks_ids);?>;
for (var i = 0; i < user_locks_ids.length; ++i) {
	var found = false;
	for (var j = 0; j < window.top.databaselock._locks.length; ++j)
		if (window.top.databaselock._locks[j].id == user_locks_ids[i]) { found = true; break; }
	if (!found)
		window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_WARNING, "Lock id "+user_locks_ids[i]+" is in database, but not registered in javascript!<br/>Please fix this issue.", [{action:"close"}], 15000));
}
for (var i = 0; i < window.top.databaselock._locks.length; ++i) {
	var found = false;
	for (var j = 0; j < user_locks_ids.length; ++j)
		if (user_locks_ids[j] == window.top.databaselock._locks[i].id) { found = true; break; }
	if (!found)
		window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_WARNING, "Lock id "+window.top.databaselock._locks[i].id+" is still registered in javascript, but is not anymore in database!<br/>Please fix this issue.", [{action:"close"}], 15000));
}
</script>
<?php 
#END
?>
</body>
</html>
<?php
}
?>