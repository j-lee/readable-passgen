<?php
/***************************************************************************************************
 *
 * Readable Passgen v1.0 (August 24, 2010)
 * generator.php
 * http://github.com/j-lee/readable-passgen
 *
 * Copyright 2010 James Lee
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 **************************************************************************************************/

require('class.readable-passgen.php');

function getmicrotime() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

// create a new PasswordGenerator class
$pass = new PasswordGenerator;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title>Readable Password Generator</title>
<style>
body			{ font:11px Verdana; }
a,a:visited		{ color:#00f; text-decoration:none; }
#wrapper		{ margin:10px; }
input,submit	{ font:11px Verdana; }
.success		{ color:#060; }
.failed			{ color:#f00; }
.lite			{ color:#999; }
.dirty			{ color:#00f; cursor:pointer; }
.dirty_words	{ visibility:hidden; }
</style>
<script src="jquery-1.4.2.min.js"></script>
<script>
$(function() {
	$("#dirty").bind("click", function() {
		var display = $("#dirty_words").css("visibility");
		if (display == 'hidden') {
			$("#dirty_words").css("visibility","visible");
			$("#dirty").text('hide');
		}
		else {
			$("#dirty_words").css("visibility","hidden");
			$("#dirty").text('show');
		}
	});
});
</script>
</head>

<body>
<div id="wrapper">
<h3>Unique Readable Random Password Generator</h3>

<?php
// if generating passwords
if (isset($_GET['action']) && $_GET['action'] == 'generate') {
	$start = getmicrotime();

	$list = array();
	$exclude = array();

	// if doing upload of passwords to exclude
	if (isset($_FILES['upload']) && $_FILES['upload']['name'] != '') {
		if ($_FILES['upload']['type'] != 'text/plain') die('Invalid File. Make sure it\'s a text file.');
		$column = 0;  // default to column 0 as password column
		$separator = "\t";  // tab
		$buffer = array();
		$handle = fopen($_FILES['upload']['tmp_name'], "r");
		if ($handle) {
			if (isset($_POST['header'])) {  // handle headers, look for 'password' column
				$first_row = fgets($handle, 4096);
				$headers = explode($separator, $first_row);
				for ($i=0; $i<count($headers); $i++) {
					if (strpos(strtolower($headers[$i]), 'pass') !== FALSE) {
						$column = $i;
						break;
					}
				}
			}
			while (!feof($handle)) {  // loop though rows and store passwords
				$row = fgets($handle, 4096);
				if ($row != '' && $row != NULL) {
					$buffer = explode($separator, $row);
					if ($buffer[$column] != '' && $buffer[$column] != NULL)
						$exclude[] = $buffer[$column];
				}
			}
			fclose($handle);
		}
		$pass->exclude = $exclude;
	}

	// set values in form submission
	if (isset($_POST['entries']) && $_POST['entries'] != '') $pass->total = $_POST['entries'];
	if (isset($_POST['capitalize'])) $pass->capitalize = 1;
	if (isset($_POST['min']) && $_POST['min'] != '') $pass->minlength = $_POST['min'];
	if (isset($_POST['max']) && $_POST['max'] != '') $pass->maxlength = $_POST['max'];
	if (isset($_POST['tries']) && $_POST['tries'] != '') $pass->maxtries = $_POST['tries'];
	if (isset($_POST['shuffle'])) $pass->shuffle = 1;

	// some header text
	if ($pass->minlength == $pass->maxlength)
		echo "Generating ".$pass->total." unique passwords with ".$pass->minlength." characters...<br />";
	else
		echo "Generating ".$pass->total." unique passwords with ".$pass->minlength." to ".$pass->maxlength." characters...<br />";
	if (isset($_FILES['upload']) && $_FILES['upload']['name'] != '') echo "Exclude list: " . basename($_FILES['upload']['name']) . "<br /></br>";

	// get passwords
	echo '<br />Generating Process Started: ' . date('h:i:s') . '<br /><br />';
	$list = $pass->generatePasswords();
	echo '<br />Generating Process Ended: ' . date('h:i:s') . '<br /><br />';

	// do checks
	$listCount = count($list);
	echo "<br />List Total: $listCount<br />";
	$uniqueList = array_unique($list);
	$uniqueListCount = count($uniqueList);
	echo "Unique List Total: " . $uniqueListCount . (($uniqueListCount == $listCount) ? ' <span class="success">(success)</span>' : ' <span class="failed">(failed)</span>') . "<br />";
	$excludeCount = count($pass->exclude);
	echo "Exclude List Total: $excludeCount<br />";
	$fullList = array_unique(array_merge($list, $pass->exclude));
	$fullListCount = count($fullList);
	echo "Unique Full+Exclude List Total: " . $fullListCount . (($fullListCount == $listCount+$excludeCount) ? ' <span class="success">(success)</span>' : ' <span class="failed">(failed)</span>') . "<br />";

	// export file
	if (isset($_POST['export'])) {
		$filename = 'passwords_' . time() . '.txt';
		$fp = fopen($filename, "w+");
		fwrite($fp, "#\tPassword");  // write header
		if (isset($_POST['hash'])) fwrite($fp, "\tHash");  // write header
		fwrite($fp, "\r\n");  // write header
		for ($i=0; $i<count($list); $i++) {
			$data = ($i+1) . "\t" . $list[$i];
			if (isset($_POST['hash'])) $data .= "\t" . md5($list[$i]);
			$data .= "\r\n";
			fwrite($fp, $data);
		}
		fclose($fp);
		echo '<br />Export Passwords: <a href="./'.$filename.'" target="_blank">'.$filename.'</a><br />';
	}

	// various statistics
	$totaltime = getmicrotime() - $start;
	echo "<br />Overall Execution Time: $totaltime"."s<br /><br />";
	echo "Collisions: ".$pass->collisions."<br />";
	echo "First Collision Occurence: " . (($pass->collisions==0) ? 'n/a' : "#".$pass->firstCollision) . "<br />";
	echo "Has Numbers Attached: ".$pass->withnum." (" . (($listCount > 0) ? round(($pass->withnum/$listCount)*100, 2) : '0') . "%)<br />";
	echo "First Password w/ Number: " . (($pass->withnum==0) ? 'n/a' : "#".$pass->firstNum) . "<br />";
	echo "Potential Dirty Words Filtered Count: ".$pass->filtered;
	if ($pass->filtered > 0) {
		echo ' (<span id="dirty" class="dirty">show</span>)<br />';
		echo '<div id="dirty_words" class="dirty_words">Dirty Words: ';
		$dirty = $pass->dirty;
		sort($dirty);
		foreach (array_unique($dirty) as $filteredWord) echo $filteredWord . " ";
		echo '</div><br />';
	}
}

// main page
else {
?>
<form action="?action=generate" method="post" enctype="multipart/form-data">
	<input type="text" name="entries" value="5000" size="5" /> Entries
	<input type="checkbox" name="capitalize" value="1" /> Capitalize results<br />
	<input type="text" name="min" value="6" size="2" /> Minimum length<br />
	<input type="text" name="max" value="8" size="2" /> Maximum length<br /><br />
	<input type="text" name="tries" value="10" size="2" /> Minimum tries to generate unique words before affixing numbers at the end<br />
	<input type="checkbox" name="shuffle" value="1" checked="checked" /> Shuffle ordering of results after generating (recommended if list is larger than 5000)<br /><br />
	List to Exclude (tab-delimited text file with 'password' column)<br />
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	<input type="file" name="upload" /><br />
	<input type="checkbox" name="header" value="1" checked="checked" /> First line is headers<br /><br />
	<input type="checkbox" name="export" value="1" checked="checked" /> Export passwords to text file
	(<input type="checkbox" name="hash" value="1" /> Include md5 hash)<br /><br />
	<input type="submit" value="Generate Passwords" /> &nbsp; <input type="reset" value="Reset" />
</form>
<?php
}
?>

</div>
</body>
</html>