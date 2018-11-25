<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2011 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

define('ISINCLUDED', 1);

require ("config.php");
require_once("lib/classes/SPB/DB.php");
require_once("lib/classes/SPB/Bin.php");

if (strtolower(@$_SERVER['HTTPS']) == "on")
    $CONFIG['pb_protocol'] = "https";
else
    $CONFIG['pb_protocol'] = "http";

/* Start Pastebin */
if (substr(phpversion(), 0, 3) < 5.3)
    die('PHP 5.3 is required to run this pastebin! This version is ' . phpversion() . '. Please contact your host!');

if ($CONFIG['pb_gzip'])
    ob_start("ob_gzhandler");

if ($CONFIG['pb_infinity'])
    $infinity = array('0');

if ($CONFIG['pb_infinity'] && $CONFIG['pb_infinity_default'])
    $CONFIG['pb_lifespan'] = array_merge((array) $infinity, (array) $CONFIG['pb_lifespan']);

elseif ($CONFIG['pb_infinity'] && ! $CONFIG['pb_infinity_default'])
    $CONFIG['pb_lifespan'] = array_merge((array) $CONFIG['pb_lifespan'], (array) $infinity);

if (get_magic_quotes_gpc()) {

    function callback_stripslashes(&$val, $name)
    {
        if (get_magic_quotes_gpc())
            $val = stripslashes($val);
    }

    if (count($_GET))
        array_walk($_GET, 'callback_stripslashes');
    if (count($_POST))
        array_walk($_POST, 'callback_stripslashes');
    if (count($_COOKIE))
        array_walk($_COOKIE, 'callback_stripslashes');
}

$requri = $_SERVER['REQUEST_URI'];
$scrnam = $_SERVER['SCRIPT_NAME'];
$reqhash = NULL;

$info = explode("/", str_replace($scrnam, "", $requri));

$requri = str_replace("?", "", $info[0]);

if (! file_exists('./INSTALL_LOCK') && $requri != "install")
    header("Location: " . $_SERVER['PHP_SELF'] . "?install");

if (file_exists('./INSTALL_LOCK') && $CONFIG['pb_rewrite'])
    $requri = $_GET['i'];

$CONFIG['requri'] = $requri;

if (strstr($requri, "@")) {
    $tempRequri = explode('@', $requri, 2);
    $requri = $tempRequri[0];
    $reqhash = $tempRequri[1];
}

$db = new \SPB\DB($CONFIG);
$bin = new \SPB\Bin($db);

$CONFIG['pb_pass'] = $bin->hasher($CONFIG['pb_pass'], $CONFIG['pb_salts']);
$db->config['pb_pass'] = $CONFIG['pb_pass'];
$bin->db->config['pb_pass'] = $CONFIG['pb_pass'];

if (file_exists('./INSTALL_LOCK') && @$_POST['subdomain'] && $CONFIG['pb_subdomains']) {
    $seed = $bin->makeSubdomain(@$_POST['subdomain']);
    if ($CONFIG['pb_https_class_1'])
        $CONFIG['pb_protocol_fix'] = "http";
    else
        $CONFIG['pb_protocol_fix'] = $CONFIG['pb_protocol'];

    if ($seed)
        header("Location: " . str_replace($CONFIG['pb_protocol'] . "://", $CONFIG['pb_protocol_fix'] . "://" . $seed . ".", $bin->linker()));
    else
        $error_subdomain = TRUE;
}

$CONFIG['subdomain'] = $bin->setSubdomain();
$db->config['subdomain'] = $CONFIG['subdomain'];
$bin->db->config['subdomain'] = $CONFIG['subdomain'];

$ckey = $bin->cookieName();

if (@$_POST['author'] && is_numeric($CONFIG['pb_author_cookie']))
    setcookie($ckey, $bin->checkAuthor(@$_POST['author']), time() + $CONFIG['pb_author_cookie']);

$CONFIG['_temp_pb_author'] = $_COOKIE[$ckey];

switch ($_COOKIE[$ckey]) {
    case NULL:
        $CONFIG['_temp_pb_author'] = $CONFIG['pb_author'];
        break;
    case $CONFIG['pb_author']:
        $CONFIG['_temp_pb_author'] = $CONFIG['pb_author'];
        break;
    default:
        $CONFIG['_temp_pb_author'] = $_COOKIE[$ckey];
        break;
}

if ($bin->highlight()) {
    include_once ($CONFIG['pb_syntax']);
    $geshi = new GeSHi('//"Paste does not exist!', 'php');
    $geshi->enable_classes();
    $geshi->set_header_type(GESHI_HEADER_PRE_VALID);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    if ($CONFIG['pb_line_highlight_style'])
        $geshi->set_highlight_lines_extra_style($CONFIG['pb_line_highlight_style']);
    $highlighterContainer = "<div id=\"highlightContainer\"><label for=\"highlighter\">Syntax Highlighting</label> <select name=\"highlighter\" id=\"highlighter\"> <option value=\"plaintext\">None</option> <option value=\"plaintext\">-------------</option> <option value=\"bash\">Bash</option> <option value=\"c\">C</option> <option value=\"cpp\">C++</option> <option value=\"css\">CSS</option> <option value=\"html4strict\">HTML</option> <option value=\"java\">Java</option> <option value=\"javascript\">Javascript</option> <option value=\"jquery\">jQuery</option> <option value=\"latex\">LaTeX</option> <option value=\"mirc\">mIRC Scripting</option> <option value=\"perl\">Perl</option> <option value=\"php\">PHP</option> <option value=\"python\">Python</option> <option value=\"rails\">Rails</option> <option value=\"ruby\">Ruby</option> <option value=\"sql\">SQL</option> <option value=\"xml\">XML</option> <option value=\"plaintext\">-------------</option> <option value=\"4cs\">GADV 4CS</option> <option value=\"abap\">ABAP</option> <option value=\"actionscript\">ActionScript</option> <option value=\"actionscript3\">ActionScript 3</option> <option value=\"ada\">Ada</option> <option value=\"apache\">Apache configuration</option> <option value=\"applescript\">AppleScript</option> <option value=\"apt_sources\">Apt sources</option> <option value=\"asm\">ASM</option> <option value=\"asp\">ASP</option> <option value=\"autoconf\">Autoconf</option> <option value=\"autohotkey\">Autohotkey</option> <option value=\"autoit\">AutoIt</option> <option value=\"avisynth\">AviSynth</option> <option value=\"awk\">awk</option> <option value=\"bash\">Bash</option> <option value=\"basic4gl\">Basic4GL</option> <option value=\"bf\">Brainfuck</option> <option value=\"bibtex\">BibTeX</option> <option value=\"blitzbasic\">BlitzBasic</option> <option value=\"bnf\">bnf</option> <option value=\"boo\">Boo</option> <option value=\"c\">C</option> <option value=\"c_mac\">C (Mac)</option> <option value=\"caddcl\">CAD DCL</option> <option value=\"cadlisp\">CAD Lisp</option> <option value=\"cfdg\">CFDG</option> <option value=\"cfm\">ColdFusion</option> <option value=\"chaiscript\">ChaiScript</option> <option value=\"cil\">CIL</option> <option value=\"clojure\">Clojure</option> <option value=\"cmake\">CMake</option> <option value=\"cobol\">COBOL</option> <option value=\"cpp\">C++</option> <option value=\"cpp-qt\" class=\"sublang\">&nbsp;&nbsp;C++ (QT)</option> <option value=\"csharp\">C#</option> <option value=\"css\">CSS</option> <option value=\"cuesheet\">Cuesheet</option> <option value=\"d\">D</option> <option value=\"dcs\">DCS</option> <option value=\"delphi\">Delphi</option> <option value=\"diff\">Diff</option> <option value=\"div\">DIV</option> <option value=\"dos\">DOS</option> <option value=\"dot\">dot</option> <option value=\"ecmascript\">ECMAScript</option> <option value=\"eiffel\">Eiffel</option> <option value=\"email\">eMail (mbox)</option> <option value=\"erlang\">Erlang</option> <option value=\"fo\">FO (abas-ERP)</option> <option value=\"fortran\">Fortran</option> <option value=\"freebasic\">FreeBasic</option> <option value=\"fsharp\">F#</option> <option value=\"gambas\">GAMBAS</option> <option value=\"gdb\">GDB</option> <option value=\"genero\">genero</option> <option value=\"genie\">Genie</option> <option value=\"gettext\">GNU Gettext</option> <option value=\"glsl\">glSlang</option> <option value=\"gml\">GML</option> <option value=\"gnuplot\">Gnuplot</option> <option value=\"groovy\">Groovy</option> <option value=\"gwbasic\">GwBasic</option> <option value=\"haskell\">Haskell</option> <option value=\"hicest\">HicEst</option> <option value=\"hq9plus\">HQ9+</option> <option value=\"html4strict\">HTML</option> <option value=\"icon\">Icon</option> <option value=\"idl\">Uno Idl</option> <option value=\"ini\">INI</option> <option value=\"inno\">Inno</option> <option value=\"intercal\">INTERCAL</option> <option value=\"io\">Io</option> <option value=\"j\">J</option> <option value=\"java\">Java</option> <option value=\"java5\">Java(TM) 2 Platform Standard Edition 5.0</option> <option value=\"javascript\">Javascript</option> <option value=\"jquery\">jQuery</option> <option value=\"kixtart\">KiXtart</option> <option value=\"klonec\">KLone C</option> <option value=\"klonecpp\">KLone C++</option> <option value=\"latex\">LaTeX</option> <option value=\"lisp\">Lisp</option> <option value=\"locobasic\">Locomotive Basic</option> <option value=\"logtalk\">Logtalk</option> <option value=\"lolcode\">LOLcode</option> <option value=\"lotusformulas\">Lotus Notes @Formulas</option> <option value=\"lotusscript\">LotusScript</option> <option value=\"lscript\">LScript</option> <option value=\"lsl2\">LSL2</option> <option value=\"lua\">Lua</option> <option value=\"m68k\">Motorola 68000 Assembler</option> <option value=\"magiksf\">MagikSF</option> <option value=\"make\">GNU make</option> <option value=\"mapbasic\">MapBasic</option> <option value=\"matlab\">Matlab M</option> <option value=\"mirc\">mIRC Scripting</option> <option value=\"mmix\">MMIX</option> <option value=\"modula2\">Modula-2</option> <option value=\"modula3\">Modula-3</option> <option value=\"mpasm\">Microchip Assembler</option> <option value=\"mxml\">MXML</option> <option value=\"mysql\">MySQL</option> <option value=\"newlisp\">newlisp</option> <option value=\"nsis\">NSIS</option> <option value=\"oberon2\">Oberon-2</option> <option value=\"objc\">Objective-C</option> <option value=\"ocaml\">OCaml</option> <option value=\"ocaml-brief\" class=\"sublang\">&nbsp;&nbsp;OCaml (brief)</option> <option value=\"oobas\">OpenOffice.org Basic</option> <option value=\"oracle11\">Oracle 11 SQL</option> <option value=\"oracle8\">Oracle 8 SQL</option> <option value=\"oxygene\">Oxygene (Delphi Prism)</option> <option value=\"oz\">OZ</option> <option value=\"pascal\">Pascal</option> <option value=\"pcre\">PCRE</option> <option value=\"per\">per</option> <option value=\"perl\">Perl</option> <option value=\"perl6\">Perl 6</option> <option value=\"pf\">OpenBSD Packet Filter</option> <option value=\"php\">PHP</option> <option value=\"php-brief\" class=\"sublang\">&nbsp;&nbsp;PHP (brief)</option> <option value=\"pic16\">PIC16</option> <option value=\"pike\">Pike</option> <option value=\"pixelbender\">Pixel Bender 1.0</option> <option value=\"plsql\">PL/SQL</option> <option value=\"postgresql\">PostgreSQL</option> <option value=\"povray\">POVRAY</option> <option value=\"powerbuilder\">PowerBuilder</option> <option value=\"powershell\">PowerShell</option> <option value=\"progress\">Progress</option> <option value=\"prolog\">Prolog</option> <option value=\"properties\">PROPERTIES</option> <option value=\"providex\">ProvideX</option> <option value=\"purebasic\">PureBasic</option> <option value=\"python\">Python</option> <option value=\"q\">q/kdb+</option> <option value=\"qbasic\">QBasic/QuickBASIC</option> <option value=\"rails\">Rails</option> <option value=\"rebol\">REBOL</option> <option value=\"reg\">Microsoft Registry</option> <option value=\"robots\">robots.txt</option> <option value=\"rpmspec\">RPM Specification File</option> <option value=\"rsplus\">R / S+</option> <option value=\"ruby\">Ruby</option> <option value=\"sas\">SAS</option> <option value=\"scala\">Scala</option> <option value=\"scheme\">Scheme</option> <option value=\"scilab\">SciLab</option> <option value=\"sdlbasic\">sdlBasic</option> <option value=\"smalltalk\">Smalltalk</option> <option value=\"smarty\">Smarty</option> <option value=\"sql\">SQL</option> <option value=\"systemverilog\">SystemVerilog</option> <option value=\"tcl\">TCL</option> <option value=\"teraterm\">Tera Term Macro</option> <option value=\"text\">Text</option> <option value=\"thinbasic\">thinBasic</option> <option value=\"tsql\">T-SQL</option> <option value=\"typoscript\">TypoScript</option> <option value=\"unicon\">Unicon (Unified Extended Dialect of Icon)</option> <option value=\"vala\">Vala</option> <option value=\"vb\">Visual Basic</option> <option value=\"vbnet\">vb.net</option> <option value=\"verilog\">Verilog</option> <option value=\"vhdl\">VHDL</option> <option value=\"vim\">Vim Script</option> <option value=\"visualfoxpro\">Visual Fox Pro</option> <option value=\"visualprolog\">Visual Prolog</option> <option value=\"whitespace\">Whitespace</option> <option value=\"whois\">Whois (RPSL format)</option> <option value=\"winbatch\">Winbatch</option> <option value=\"xbasic\">XBasic</option> <option value=\"xml\">XML</option> <option value=\"xorg_conf\">Xorg configuration</option> <option value=\"xpp\">X++</option> <option value=\"yaml\">YAML</option> <option value=\"z80\">ZiLOG Z80 Assembler</option> </select> </div>";
}

if ($requri != "install" && $requri != NULL && $bin->checkIfRedir($requri) != false && substr($requri, - 1) != "!" && ! $_POST['adminProceed']) {
    header("Location: " . $bin->checkIfRedir($requri));
    die("This is a URL/Mailto forward holding page!");
}

if ($requri != "install" && $requri != NULL && substr($requri, - 1) != "!" && ! $_POST['adminProceed'] && $reqhash == "raw") {
    if ($pasted = $db->readPaste($requri)) {
        if ($db->dbt == "mysql")
            $pasted = $pasted[0];

        header("Content-Type: text/plain; charset=utf-8");
        die($db->rawHTML($bin->noHighlight($pasted['Data'])));
    } else
        die('There was an error!');
}

$pasteinfo = array();
if ($requri != "install")
    $bin->cleanUp($CONFIG['pb_recent_posts']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title><?php
echo $bin->setTitle($CONFIG['pb_name']);
?> &raquo; <?php
echo $bin->titleID($requri);
?></title>
<meta name="Robots"
	content="<?php
echo $bin->robotPrivacy($requri);
?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $CONFIG['pb_style']; ?>" media="screen, print" />
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript">
<?php
if ($CONFIG['pb_url']) {
            ?>
function checkIfURL(checkMe){
	var checking = checkMe.value;
	var regExpression = new RegExp();
	regExpression.compile('^[A-Za-z]+://[A-Za-z0-9-_]+\\.[A-Za-z0-9-_%&\?\/\!.=]+$');
	if(regExpression.test(checking)){
		checkMe.setAttribute("id", "urlField");
		document.getElementById('foundURL').style.display = "block";
		document.getElementById('fileUploadContainer').style.visibility = "hidden";
		return true;
	}
	else {
		if(checkMe.id != "pasteEnter")
			checkMe.setAttribute("id", "pasteEnter");

		document.getElementById('foundURL').style.display = "none";
		document.getElementById('fileUploadContainer').style.visibility = "visible";
		return false;
	}
}
<?php
} else {
            ?>
function checkIfURL(checkMe){
	return true;
}
<?php
}
?>
</script>
<?php
        /* end JS */
?>
	</head>
<body>
<div id="siteWrapper">
<?php
if ($requri != "install" && ! $db->connect())
    echo "<div class=\"error\">No database connection could be established - check your config.</div>";
elseif ($requri != "install" && $db->connect())
    $db->disconnect();
else
    echo "<!-- No Check is required... -->";

if (@$_POST['adminAction'] == "delete" && $bin->hasher(hash($CONFIG['pb_algo'], @$_POST['adminPass']), $CONFIG['pb_salts']) === $CONFIG['pb_pass']) {
    $db->dropPaste($requri);
    echo "<div class=\"success\">Paste, " . $requri . ", has been deleted!</div>";
    $requri = NULL;
}

if (@$_POST['subdomain'] && $error_subdomain)
    die("<div class=\"result\"><div class=\"error\">Subdomain invalid or already taken!</div></div></div></body></html>");

if ($requri != "install" && @$_POST['submit']) {
    $acceptTokens = $bin->token();

    if (@$_POST['email'] != "" || ! in_array($_POST['ajax_token'], $acceptTokens))
        die("<div class=\"result\"><div class=\"error\">Spambot detected, I don't like that!</div></div></div></body></html>");

    $pasteID = $bin->generateID();

    if (@$_POST['urlField'])
        $postedURL = htmlspecialchars($_POST['urlField']);
    elseif (preg_match('/^((ht|f)(tp|tps)|mailto|irc|skype|git|svn|cvs|aim|gtalk|feed):/', @$_POST['pasteEnter']) && count(explode("\n", $_POST['pasteEnter'])) < 2)
        $postedURL = htmlspecialchars($_POST['pasteEnter']);
    else
        $postedURL = NULL;

    $exclam = NULL;

    if ($postedURL != NULL) {
        $_POST['pasteEnter'] = $postedURL;
        $exclam = "!";
        $postedURLInfo = pathinfo($postedURL);

        if ($CONFIG['pb_url'])
            $_FILES['pasteImage'] = NULL;
    }

    $imageUpload = FALSE;
    $uploadAttempt = FALSE;

    if (strlen(@$_FILES['pasteImage']['name']) > 4 && $CONFIG['pb_images']) {
        $imageUpload = $db->uploadFile($_FILES['pasteImage'], $pasteID);
        if ($imageUpload != FALSE) {
            $postedURL = NULL;
        }
        $uploadAttempt = TRUE;
    }

    if (in_array(strtolower($postedURLInfo['extension']), $CONFIG['pb_image_extensions']) && $CONFIG['pb_images'] && $CONFIG['pb_download_images'] && ! $imageUpload) {
        $imageUpload = $db->downTheImg($postedURL, $pasteID);
        if ($imageUpload != FALSE) {
            $postedURL = NULL;
            $exclam = NULL;
        }
        $uploadAttempt = TRUE;
    }

    if (! $imageUpload && ! $uploadAttempt)
        $imageUpload = TRUE;

    if (@$_POST['pasteEnter'] == NULL && strlen(@$_FILES['pasteImage']['name']) > 4 && $CONFIG['pb_images'] && $imageUpload)
        $_POST['pasteEnter'] = "Image file (" . $_FILES['pasteImage']['name'] . ") uploaded...";

    if (! $CONFIG['pb_url'])
        $postedURL = NULL;

    if ($bin->highlight() && $_POST['highlighter'] != "plaintext") {
        $geshi->set_language($_POST['highlighter']);
        $geshi->set_source($bin->noHighlight(@$_POST['pasteEnter']));
        $geshi->highlight_lines_extra($bin->highlightNumbers(@$_POST['pasteEnter']));
        $geshiCode = $geshi->parse_code();
        $geshiStyle = $geshi->get_stylesheet();
    } else {
        $geshiCode = NULL;
        $geshiStyle = NULL;
    }

    $paste = array('ID' => $pasteID , 'Author' => $bin->checkAuthor(@$_POST['author']) , 'Subdomain' => $bin->db->config['subdomain'] , 'IP' => $_SERVER['REMOTE_ADDR'] , 'Image' => $imageUpload , 'ImageTxt' => "Image file (" . @$_FILES['pasteImage']['name'] . ") uploaded..." , 'URL' => $postedURL , 'Syntax' => $_POST['highlighter'] , 'Lifespan' => $_POST['lifespan'] , 'Protect' => $_POST['privacy'] , 'Parent' => $requri , 'Content' => @$_POST['pasteEnter'] , 'GeSHI' => $geshiCode , 'Style' => $geshiStyle);

    if (@$_POST['pasteEnter'] == @$_POST['originalPaste'] && strlen($_POST['pasteEnter']) > 10)
        die("<div class=\"error\">Please don't just repost what has already been said!</div></div></body></html>");

    if (strlen(@$_POST['pasteEnter']) > 10 && $imageUpload && mb_strlen($paste['Content']) <= $CONFIG['pb_max_bytes'] && $db->insertPaste($paste['ID'], $paste)) {
        die("<div class=\"result\"><div class=\"success\">Your paste has been successfully recorded!</div><div class=\"confirmURL\">URL to your paste is <a href=\"" . $bin->linker($paste['ID']) . $exclam . "\">" . $bin->linker($paste['ID']) . "</a></div></div></div></body></html>");
    } else {
        echo "<div class=\"error\">Hmm, something went wrong.</div>";
        if (strlen(@$_FILES['pasteImage']['name']) > 4 && $_SERVER['CONTENT_LENGTH'] > $CONFIG['pb_image_maxsize'] && $CONFIG['pb_images'])
            echo "<div class=\"warn\">Is the file too big?</div>";
        elseif (strlen(@$_FILES['pasteImage']['name']) > 4 && $CONFIG['pb_images'])
            echo "<div class=\"warn\">File is the wrong extension?</div>";
        elseif (! $CONFIG['pb_images'] && strlen(@$_FILES['pasteImage']['name']) > 4)
            echo "<div class=\"warn\">Nope, we don't host images!</div>";
        else
            echo "<div class=\"warn\">Pasted text must be between 10 characters and " . $bin->humanReadableFilesize($CONFIG['pb_max_bytes']) . "</div>";
    }
}

if ($requri != "install" && $CONFIG['pb_recent_posts'] && substr($requri, - 1) != "!") {
    echo "<div id=\"recentPosts\" class=\"recentPosts\">";
    $recentPosts = $bin->getLastPosts($CONFIG['pb_recent_posts']);
    echo "<h2 id=\"newPaste\"><a href=\"" . $bin->linker() . "\">New Paste</a></h2><div class=\"spacer\">&nbsp;</div>";
    if ($requri || count($recentPosts) > 0)
        if (count($recentPosts) > 0) {
            echo "<h2>Recent Pastes</h2>";
            echo "<ul id=\"postList\" class=\"recentPosts\">";
            foreach ($recentPosts as $paste_) {
                $rel = NULL;
                $exclam = NULL;
                if ($paste_['URL'] != NULL && $CONFIG['pb_url']) {
                    $exclam = "!";
                    $rel = " rel=\"link\"";
                }

                if (! is_bool($paste_['Image']) && ! is_numeric($paste_['Image']) && $paste_['Image'] != NULL && $CONFIG['pb_images']) {
                    if ($CONFIG['pb_media_warn'])
                        $exclam = "!";
                    else
                        $exclam = NULL;

                    $rel = " rel=\"image\"";
                }

                echo "<li id=\"" . $paste_['ID'] . "\" class=\"postItem\"><a href=\"" . $bin->linker($paste_['ID']) . $exclam . "\"" . $rel . ">" . stripslashes($paste_['Author']) . "</a><br />" . $bin->event($paste_['Datetime']) . " ago.</li>";
            }
            echo "</ul>";
        } else
            echo "&nbsp;";
    if ($requri) {
        echo "<div id=\"showAdminFunctions\"><a href=\"#\" onclick=\"return toggleAdminTools();\">Show Admin tools</a></div><div id=\"hiddenAdmin\"><h2>Administrate</h2>";
        echo "<div id=\"adminFunctions\">
							<form id=\"adminForm\" action=\"" . $bin->linker($requri) . "\" method=\"post\">
								<label for=\"adminPass\">Password</label><br />
								<input id=\"adminPass\" type=\"password\" name=\"adminPass\" value=\"" . @$_POST['adminPass'] . "\" />
								<br /><br />
								<select id=\"adminAction\" name=\"adminAction\">
									<option value=\"ip\">Show Author's IP</option>
									<option value=\"delete\">Delete Paste</option>
								</select>
								<input type=\"submit\" name=\"adminProceed\" value=\"Proceed\" />
							</form>
						</div></div>";
    }
    echo "</div>";
} else {
    if ($requri && $requri != "install" && substr($requri, - 1) != "!") {
        echo "<div id=\"recentPosts\" class=\"recentPosts\">";
        echo "<h2><a href=\"" . $bin->linker() . "\">New Paste</a></h2><div class=\"spacer\">&nbsp;</div>";
        echo "<div id=\"showAdminFunctions\"><a href=\"#\" onclick=\"return toggleAdminTools();\">Show Admin tools</a></div><div id=\"hiddenAdmin\"><h2>Administrate</h2>";
        echo "<div id=\"adminFunctions\">
							<form id=\"adminForm\" action=\"" . $bin->linker($requri) . "\" method=\"post\">
								<label for=\"adminPass\">Password</label><br />
								<input id=\"adminPass\" type=\"password\" name=\"adminPass\" value=\"" . @$_POST['adminPass'] . "\" />
								<br /><br />
								<select id=\"adminAction\" name=\"adminAction\">
									<option value=\"ip\">Show Author's IP</option>
									<option value=\"delete\">Delete Paste</option>
								</select>
								<input type=\"submit\" name=\"adminProceed\" value=\"Proceed\" />
							</form>
						</div></div>";
        echo "</div>";
    }
}

if ($requri && $requri != "install" && substr($requri, - 1) != "!") {
    $pasteinfo['Parent'] = $requri;
    echo "<div id=\"pastebin\" class=\"pastebin\">" . "<h1>" . $bin->setTitle($CONFIG['pb_name']) . "</h1>" . $bin->setTagline($CONFIG['pb_tagline']) . "<div id=\"result\"></div>";

    if ($pasted = $db->readPaste($requri)) {

        if ($db->dbt == "mysql")
            $pasted = $pasted[0];

        $pasted['Data'] = array('Orig' => $pasted['Data'] , 'noHighlight' => array());

        $pasted['Data']['Dirty'] = $db->dirtyHTML($pasted['Data']['Orig']);
        $pasted['Data']['noHighlight']['Dirty'] = $bin->noHighlight($pasted['Data']['Dirty']);

        if ($pasted['Syntax'] == NULL || is_bool($pasted['Syntax']) || is_numeric($pasted['Syntax']))
            $pasted['Syntax'] = "plaintext";

        if ($pasted['Subdomain'] != NULL && ! $CONFIG['subdomain'])
            $bin->setSubdomain($pasted['Subdomain']);

        if ($bin->highlight() && $pasted['Syntax'] != "plaintext") {
            echo "<style type=\"text/css\">";
            echo stripslashes($pasted['Style']);
            echo "</style>";
        }

        if (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image']))
            $pasteSize = $bin->humanReadableFilesize(filesize($db->setDataPath($pasted['Image'])));
        else
            $pasteSize = $bin->humanReadableFilesize(mb_strlen($pasted['Data']['Orig']));

        if ($pasted['Lifespan'] == 0) {
            $pasted['Lifespan'] = time() + time();
            $lifeString = "Never";
        } else
            $lifeString = "in " . $bin->event(time() - ($pasted['Lifespan'] - time()));

        if (gmdate('U') > $pasted['Lifespan']) {
            $db->dropPaste($requri);
            die("<div class=\"result\"><div class=\"warn\">This paste has either expired or doesn't exist!</div></div></div></body></html>");
        }

        echo "<div id=\"aboutPaste\"><div id=\"pasteID\"><strong>PasteID</strong>: " . $requri . "</div><strong>Pasted by</strong> " . stripslashes($pasted['Author']) . ", <em title=\"" . $bin->event($pasted['Datetime']) . " ago\">" . gmdate($CONFIG['pb_datetime'], $pasted['Datetime']) . " GMT</em><br />
					<strong>Expires</strong> " . $lifeString . "<br />
					<strong>Paste size</strong> " . $pasteSize . "</div>";

        if (@$_POST['adminAction'] == "ip" && $bin->hasher(hash($CONFIG['pb_algo'], @$_POST['adminPass']), $CONFIG['pb_salts']) === $CONFIG['pb_pass'])
            echo "<div class=\"success\"><strong>Author IP Address</strong> <a href=\"http://whois.domaintools.com/" . base64_decode($pasted['IP']) . "\">" . base64_decode($pasted['IP']) . "</a></div>";

        if (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image']))
            echo "<div id=\"imageContainer\"><a href=\"" . $bin->linker() . $db->setDataPath($pasted['Image']) . "\" rel=\"external\"><img src=\"" . $bin->linker() . $db->setDataPath($pasted['Image']) . "\" alt=\"" . $pasted['ImageTxt'] . "\" class=\"pastedImage\" /></a></div>";

        if (strlen($pasted['Parent']) > 0)
            echo "<div class=\"warn\"><strong>This is an edit of</strong> <a href=\"" . $bin->linker($pasted['Parent']) . "\">" . $bin->linker($pasted['Parent']) . "</a></div>";

        if (! $bin->highlight() || (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image'])) || $pasted['Syntax'] == "plaintext")
            echo "<div id=\"styleBar\"><strong>Toggle</strong> <a href=\"#\" onclick=\"return toggleExpand();\">Expand</a> &nbsp;  <a href=\"#\" onclick=\"return toggleWrap();\">Wrap</a> &nbsp; <a href=\"#\" onclick=\"return toggleStyle();\">Style</a> &nbsp; <a href=\"" . $bin->linker($pasted['ID'] . '@raw') . "\">Raw</a></div>";
        else
            echo "<div id=\"styleBar\"><strong>Toggle</strong> <a href=\"#\" onclick=\"return toggleExpand();\">Expand</a> &nbsp;  <a href=\"#\" onclick=\"return toggleWrap();\">Wrap</a> &nbsp; <a href=\"" . $bin->linker($pasted['ID'] . '@raw') . "\">Raw</a></div>";

        echo "<div class=\"spacer\">&nbsp;</div>";

        if (! $bin->highlight() || (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image'])) || $pasted['Syntax'] == "plaintext") {
            echo "<div id=\"retrievedPaste\"><div id=\"lineNumbers\"><ol id=\"orderedList\" class=\"monoText\">";
            $lines = explode("\n", $pasted['Data']['Dirty']);
            foreach ($lines as $line)
                echo "<li class=\"line\"><pre>" . str_replace(array("\n" , "\r"), "&nbsp;", $bin->filterHighlight($line)) . "&nbsp;</pre></li>";
            echo "</ol></div></div>";
        } else {
            echo "<div class=\"spacer\">&nbsp;</div><div id=\"retrievedPaste\"><div id=\"lineNumbers\">";
            echo stripslashes($pasted['GeSHI']);
            echo "</div></div><div class=\"spacer\">&nbsp;</div>";
        }

        if ($bin->lineHighlight())
            $lineHighlight = "To highlight lines, prefix them with <em>" . $bin->lineHighlight() . "</em>";
        else
            $lineHighlight = NULL;

        $event = "onblur=\"return checkIfURL(this);\" onkeyup";

        if (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image']))
            $pasted['Data']['noHighlight']['Dirty'] = $bin->linker() . $db->setDataPath($pasted['Image']);

        if ($CONFIG['pb_editing']) {
            echo "<div id=\"formContainer\">
					<form id=\"pasteForm\" name=\"pasteForm\" action=\"" . $bin->linker($pasted['ID']) . "\" method=\"post\">
						<div><label for=\"pasteEnter\" class=\"pasteEnterLabel\">Edit this post! " . $lineHighlight . "</label>
						<textarea id=\"pasteEnter\" name=\"pasteEnter\" onkeydown=\"return catchTab(event)\" " . $event . "=\"return checkIfURL(this);\">" . $pasted['Data']['noHighlight']['Dirty'] . "</textarea></div>
						<div id=\"foundURL\" style=\"display: none;\">URL has been detected...</div>
						<div class=\"spacer\">&nbsp;</div>";

            $selecter = '/value="' . $pasted['Syntax'] . '"/';
            $replacer = 'value="' . $pasted['Syntax'] . '" selected="selected"';
            $highlighterContainer = preg_replace($selecter, $replacer, $highlighterContainer, 1);

            if ($bin->highlight())
                echo $highlighterContainer;

            if (is_array($CONFIG['pb_lifespan']) && count($CONFIG['pb_lifespan']) > 1) {
                echo "<div id=\"lifespanContainer\"><label for=\"lifespan\">Paste Expiration</label> <select name=\"lifespan\" id=\"lifespan\">";

                foreach ($CONFIG['pb_lifespan'] as $span) {
                    $key = array_keys($CONFIG['pb_lifespan'], $span);
                    $key = $key[0];
                    $options .= "<option value=\"" . $key . "\">" . $bin->event(time() - ($span * 24 * 60 * 60), TRUE) . "</option>";
                }

                $selecter = '/\>0 seconds/';
                $replacer = '>Never';
                $options = preg_replace($selecter, $replacer, $options, 1);

                echo $options;

                echo "</select></div>";
            } elseif (is_array($CONFIG['pb_lifespan']) && count($CONFIG['pb_lifespan']) == 1) {
                echo "<div id=\"lifespanContainer\"><label for=\"lifespan\">Paste Expiration</label>";

                echo " <div id=\"expireTime\"><input type=\"hidden\" name=\"lifespan\" value=\"0\" />" . $bin->event(time() - ($CONFIG['pb_lifespan'][0] * 24 * 60 * 60), TRUE) . "</div>";

                echo "</div>";
            } else
                echo "<input type=\"hidden\" name=\"lifespan\" value=\"0\" />";

            $enabled = NULL;

            if ($pasted['Protection'])
                $enabled = "disabled";

            $privacyContainer = "<div id=\"privacyContainer\"><label for=\"privacy\">Paste Visibility</label> <select name=\"privacy\" id=\"privacy\" " . $enabled . "><option value=\"0\">Public</option> <option value=\"1\">Private</option></select></div>";

            $selecter = '/value="' . $pasted['Protection'] . '"/';
            $replacer = 'value="' . $pasted['Protection'] . '" selected="selected"';
            $privacyContainer = preg_replace($selecter, $replacer, $privacyContainer, 1);

            if ($pasted['Protection']) {
                $selecter = '/\<\/select\>/';
                $replacer = '</select><input type="hidden" name="privacy" value="' . $pasted['Protection'] . '" />';
                $privacyContainer = preg_replace($selecter, $replacer, $privacyContainer, 1);
            }

            if ($CONFIG['pb_private'])
                echo $privacyContainer;

            echo "<div class=\"spacer\">&nbsp;</div>";

            echo "<div id=\"authorContainerReply\"><label for=\"authorEnter\">Your Name</label><br />
						<input type=\"text\" name=\"author\" id=\"authorEnter\" value=\"" . $CONFIG['_temp_pb_author'] . "\" onfocus=\"if(this.value=='" . $CONFIG['_temp_pb_author'] . "')this.value='';\" onblur=\"if(this.value=='')this.value='" . $CONFIG['_temp_pb_author'] . "';\" maxlength=\"32\" /></div>
						<div class=\"spacer\">&nbsp;</div>
						<input type=\"text\" name=\"email\" id=\"poison\" style=\"display: none;\" />
						<input type=\"hidden\" name=\"ajax_token\" value=\"" . $bin->token(TRUE) . "\" />
						<input type=\"hidden\" name=\"originalPaste\" id=\"originalPaste\" value=\"" . $pasted['Data']['noHighlight']['Dirty'] . "\" />
						<input type=\"hidden\" name=\"parent\" id=\"parentThread\" value=\"" . $requri . "\" />
						<input type=\"hidden\" name=\"thisURI\" id=\"thisURI\" value=\"" . $bin->linker($pasted['ID']) . "\" />
						<div id=\"fileUploadContainer\" style=\"display: none;\">&nbsp;</div>
						<div id=\"submitContainer\" class=\"submitContainer\">
							<input type=\"submit\" name=\"submit\" value=\"Submit your paste\" onclick=\"return submitPaste(this);\" id=\"submitButton\" />
						</div>
					</form>
				</div>
				<div class=\"spacer\">&nbsp;</div><div class=\"spacer\">&nbsp;</div>";
        } else {
            echo "<form id=\"pasteForm\" name=\"pasteForm\" action=\"" . $bin->linker($pasted['ID']) . "\" method=\"post\">
							<input type=\"hidden\" name=\"originalPaste\" id=\"originalPaste\" value=\"" . $pasted['Data']['Dirty'] . "\" />
							<input type=\"hidden\" name=\"parent\" id=\"parentThread\" value=\"" . $requri . "\" />
							<input type=\"hidden\" name=\"thisURI\" id=\"thisURI\" value=\"" . $bin->linker($pasted['ID']) . "\" />
						</form><div class=\"spacer\">&nbsp;</div><div class=\"spacer\">&nbsp;</div>";
        }

    } else {
        echo "<div class=\"result\"><div class=\"warn\">This paste has either expired or doesn't exist!</div></div>";
        $requri = NULL;
    }
    echo "</div>";
} elseif ($requri && $requri != "install" && substr($requri, - 1) == "!") {
    if (! $bin->checkIfRedir(substr($requri, 0, - 1)))
        echo "<div class=\"result\"><h1>Just a sec!</h1><div class=\"warn\">You are about to visit a post that the author has marked as requiring confirmation to view.</div>
				<div class=\"infoMessage\">If you wish to view the content <strong><a href=\"" . $bin->linker(substr($requri, 0, - 1)) . "\">click here</a></strong>. Please note that the owner of this pastebin will not be held responsible for the content of this paste.<br /><br /><a href=\"" . $bin->linker() . "\">Take me back...</a></div></div>";
    else
        echo "<div class=\"result\"><h1>Warning!</h1><div class=\"error\">You are about to leave the site!</div>
				<div class=\"infoMessage\">This paste redirects you to<br /><br /><div id=\"emphasizedURL\">" . $bin->checkIfRedir(substr($requri, 0, - 1)) . "</div><br /><br />Danger lurks on the world wide web, if you want to visit the site <strong><a href=\"" . $bin->checkIfRedir(substr($requri, 0, - 1)) . "\">click here</a></strong>. Please note that the owner of this pastebin will not be held responsible for the content of the site.<br /><br /><a href=\"" . $bin->linker() . "\">Take me back...</a></div></div>";

    echo "<div id=\"showAdminFunctions\"><a href=\"#\" onclick=\"return toggleAdminTools();\">Show Admin tools</a></div><div id=\"hiddenAdmin\"><div class=\"spacer\">&nbsp;</div><h2>Administrate</h2>";
    echo "<div id=\"adminFunctions\">
							<form id=\"adminForm\" action=\"" . $bin->linker(substr($requri, 0, - 1)) . "\" method=\"post\">
								<label for=\"adminPass\">Password</label><br />
								<input id=\"adminPass\" type=\"password\" name=\"adminPass\" value=\"" . @$_POST['adminPass'] . "\" />
								<br /><br />
								<select id=\"adminAction\" name=\"adminAction\">
									<option value=\"ip\">Show Author's IP</option>
									<option value=\"delete\">Delete Paste</option>
								</select>
								<input type=\"submit\" name=\"adminProceed\" value=\"Proceed\" />
							</form>
						</div></div>";
    die("</div></body></html>");
} elseif (isset($requri) && $requri == "install") {
    $stage = array();
    echo "<div id=\"installer\" class=\"installer\">" . "<h1>Installing Pastebin</h1>";

    if (file_exists('./INSTALL_LOCK'))
        die("<div class=\"warn\"><strong>Already Installed!</strong></div></div></body></html>");

    echo "<ul id=\"installList\">";
    echo "<li>Checking Directory is writable. ";
    if (! is_writable($bin->thisDir()))
        echo "<span class=\"error\">Directory is not writable!</span> - CHMOD to 0777";
    else {
        echo "<span class=\"success\">Directory is writable!</span>";
        $stage[] = 1;
    }
    echo "</li>";

    if (count($stage) > 0) {
        echo "<li>Quick password check. ";
        $passLen = array(8 , 9 , 10 , 11 , 12);
        shuffle($passLen);
        if ($CONFIG['pb_pass'] === $bin->hasher(hash($CONFIG['pb_algo'], "password"), $CONFIG['pb_salts']) || ! isset($CONFIG['pb_pass']))
            echo "<span class=\"error\">Password is still default!</span> &nbsp; &raquo; &nbsp; Suggested password: <em>" . $bin->generateRandomString($passLen[0]) . "</em>";
        else {
            echo "<span class=\"success\">Password is not default!</span>";
            $stage[] = 1;
        }
        echo "</li>";
    }

    if (count($stage) > 1) {
        echo "<li>Quick Salt Check. ";
        $no_salts = count($CONFIG['pb_salts']);
        $saltLen = array(8 , 9 , 10 , 11 , 12 , 14 , 16 , 25 , 32);
        shuffle($saltLen);
        if ($no_salts < 4 || ($CONFIG['pb_salts'][1] == "str001" || $CONFIG['pb_salts'][2] == "str002" || $CONFIG['pb_salts'][3] == "str003" || $CONFIG['pb_salts'][4] == "str004"))
            echo "<span class=\"error\">Salt strings are inadequate!</span> &nbsp; &raquo; &nbsp; Suggested salts: <ol><li>" . $bin->generateRandomString($saltLen[0]) . "</li><li>" . $bin->generateRandomString($saltLen[1]) . "</li><li>" . $bin->generateRandomString($saltLen[2]) . "</li><li>" . $bin->generateRandomString($saltLen[3]) . "</li></ol>";
        else {
            echo "<span class=\"success\">Salt strings are adequate!</span>";
            $stage[] = 1;
        }
        echo "</li>";
    }

    if (count($stage) > 2) {
        echo "<li>Checking Database Connection. ";
        if ($db->dbt == "txt") {
            if (! is_dir($CONFIG['txt_config']['db_folder'])) {
                mkdir($CONFIG['txt_config']['db_folder']);
                mkdir($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images']);
                mkdir($CONFIG['txt_config']['db_folder'] . "/subdomain");
                chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'], $CONFIG['txt_config']['dir_mode']);
                chmod($CONFIG['txt_config']['db_folder'], $CONFIG['txt_config']['dir_mode']);
                chmod($CONFIG['txt_config']['db_folder'] . "/subdomain", $CONFIG['txt_config']['dir_mode']);
            }
            $db->write($db->serializer(array()), $CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_index']);
            $db->write($db->serializer($bin->generateForbiddenSubdomains()), $CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_index'] . "_SUBDOMAINS");
            $db->write("FORBIDDEN", $CONFIG['txt_config']['db_folder'] . "/index.html");
            $db->write("FORBIDDEN", $CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'] . "/index.html");
            chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_index'], $CONFIG['txt_config']['file_mode']);
            chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_index'] . "_SUBDOMAINS", $CONFIG['txt_config']['file_mode']);
            chmod($CONFIG['txt_config']['db_folder'] . "/index.html", $CONFIG['txt_config']['file_mode']);
            chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'] . "/index.html", $CONFIG['txt_config']['file_mode']);
        }
        if (! $db->connect())
            echo "<span class=\"error\">Cannot connect to database!</span> - Check Config in index.php";
        else {
            echo "<span class=\"success\">Connected to database!</span>";
            $stage[] = 1;
        }
        echo "</li>";
    }

    if (count($stage) > 3) {
        echo "<li>Creating Database Tables. ";
        $structure = "CREATE TABLE IF NOT EXISTS " . $CONFIG['mysql_connection_config']['db_table'] . " (ID varchar(255), Subdomain varchar(100), Datetime bigint, Author varchar(255), Protection int, Syntax varchar(255) DEFAULT 'plaintext', Parent longtext, Image longtext, ImageTxt longtext, URL longtext, Lifespan int, IP varchar(225), Data longtext, GeSHI longtext, Style longtext, INDEX (id)) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci";
        if ($db->dbt == "mysql") {
            if (! mysql_query($structure, $db->link) && ! $CONFIG['mysql_connection_config']['db_existing']) {
                echo "<span class=\"error\">Structure failed</span> - Check Config in index.php (Does the table already exist?)";
            } else {
                echo "<span class=\"success\">Table created!</span>";
                mysql_query("ALTER TABLE `" . $CONFIG['mysql_connection_config']['db_table'] . "` ORDER BY `Datetime` DESC", $db->link);
                $stage[] = 1;
                if ($CONFIG['mysql_connection_config']['db_existing'])
                    echo "<span class=\"warn\">Attempting to use an existing table!</span> If this is not a Pastebin table a fault will occur.";

                mkdir($CONFIG['txt_config']['db_folder']);
                chmod($CONFIG['txt_config']['db_folder'], $CONFIG['txt_config']['dir_mode']);
                mkdir($CONFIG['txt_config']['db_folder'] . "/subdomain");
                chmod($CONFIG['txt_config']['db_folder'] . "/subdomain", $CONFIG['txt_config']['dir_mode']);
                mkdir($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images']);
                chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'], $CONFIG['txt_config']['dir_mode']);
                $db->write("FORBIDDEN", $CONFIG['txt_config']['db_folder'] . "/index.html");
                chmod($CONFIG['txt_config']['db_folder'] . "/index.html", $CONFIG['txt_config']['file_mode']);
                $db->write("FORBIDDEN", $CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'] . "/index.html");
                chmod($CONFIG['txt_config']['db_folder'] . "/" . $CONFIG['txt_config']['db_images'] . "/index.html", $CONFIG['txt_config']['file_mode']);

                $forbidden_array = array('ID' => 'forbidden' , 'Time_offset' => 10 , 'Author' => 'System' , 'IP' => $_SERVER['REMOTE_ADDR'] , 'Lifespan' => 0 , 'Image' => TRUE , 'Protect' => 1 , 'Content' => serialize($bin->generateForbiddenSubdomains(TRUE)));

                $db->insertPaste($forbidden_array['ID'], $forbidden_array, TRUE);
            }
        } else {
            echo "<span class=\"success\">Table created!</span>";
            $stage[] = 1;
        }
        echo "</li>";
    }

    if (count($stage) > 4) {
        $stage[] = 1;
    }

    if (count($stage) > 5) {
        echo "<li>Locking Installation. ";
        if (! $db->write(time(), './INSTALL_LOCK'))
            echo "<span class=\"error\">Writing Error</span>";
        else {
            echo "<span class=\"success\">Complete</span>";
            $stage[] = 1;
            chmod('./INSTALL_LOCK', $CONFIG['txt_config']['file_mode']);
        }
        echo "</li>";
    }
    echo "</ul>";
    if (count($stage) > 6) {
        $paste_new = array('ID' => $bin->generateRandomString($CONFIG['pb_id_length']) , 'Author' => 'System' , 'IP' => $_SERVER['REMOTE_ADDR'] , 'Lifespan' => 1800 , 'Image' => TRUE , 'Protect' => 0 , 'Content' => $CONFIG['pb_line_highlight'] . "Congratulations, your pastebin has now been installed!\nThis message will expire in 30 minutes!");
        $db->insertPaste($paste_new['ID'], $paste_new, TRUE);
        echo "<div id=\"confirmInstalled\"><a href=\"" . $bin->linker() . "\">Continue</a> to your new installation!<br /></div>";
        echo "<div id=\"confirmInstalled\" class=\"warn\">It is recommended that you now CHMOD this directory to 755</div>";
    }
    echo "</div>";
} else {
    if ($CONFIG['pb_subdomains'])
        $subdomainClicker = " [ <a href=\"#\" onclick=\"return toggleSubdomain();\">make a subdomain</a> ]";
    else
        $subdomainClicker = NULL;

    if ($CONFIG['subdomain']) {
        $domain_name = str_replace(array($CONFIG['pb_protocol'] . "://" , $CONFIG['subdomain'] . "." , "www."), "", $bin->linker());
        $subdomain_action = str_replace($CONFIG['subdomain'] . ".", "", $bin->linker());
    } else {
        $domain_name = str_replace(array($CONFIG['pb_protocol'] . "://" , "www."), "", $bin->linker());
        $subdomain_action = $bin->linker();
    }

    $subdomainForm = "<div id=\"subdomainForm\ style=\"display: none\"><strong>Subdomain</strong><br /><form id=\"subdomain_form\" action=\"" . $subdomain_action . "\" method=\"POST\">" . $CONFIG['pb_protocol'] . "://<input type=\"text\" name=\"subdomain\" id=\"subdomain\" maxlength=\"32\" />." . $domain_name . " <input type=\"submit\" id=\"new_subdomain\" name=\"new_subdomain\" value=\"Create Subdomain\" /></form><div class=\"spacer\">&nbsp;</div></div>";

    if (strlen($bin->linker()) < 16)
        $isShortURL = " If your text is a URL, the pastebin will recognize it and will create a Short URL forwarding page! (Like bit.ly, is.gd, etc)";
    else
        $isShortURL = " If your text is a URL, the pastebin will recognize it and will create a URL forwarding page!";

    if ($CONFIG['pb_editing'])
        $service['editing'] = array('style' => 'success' , 'status' => 'Enabled');
    else
        $service['editing'] = array('style' => 'error' , 'status' => 'Disabled');

    if ($CONFIG['pb_images'])
        $service['images'] = array('style' => 'success' , 'status' => 'Enabled' , 'tip' => ', you can even upload a ' . $bin->humanReadableFilesize($CONFIG['pb_image_maxsize']) . ' image,');
    else
        $service['images'] = array('style' => 'error' , 'status' => 'Disabled' , 'tip' => NULL);

    if ($CONFIG['pb_download_images'] && $CONFIG['pb_images']) {
        $service['image_download'] = array('style' => 'success' , 'status' => 'Enabled');
        $service['images']['tip'] = ', you can even upload or copy from another site a ' . $bin->humanReadableFilesize($CONFIG['pb_image_maxsize']) . ' image,';
    } else
        $service['image_download'] = array('style' => 'error' , 'status' => 'Disabled' , 'tip' => NULL);

    if ($CONFIG['pb_url'])
        $service['url'] = array('style' => 'success' , 'status' => 'Enabled' , 'tip' => $isShortURL , 'str' => '/url');
    else
        $service['url'] = array('style' => 'error' , 'status' => 'Disabled' , 'tip' => NULL , 'str' => NULL);

    if ($CONFIG['pb_subdomains'])
        $service['subdomains'] = array('style' => 'success' , 'status' => 'Enabled' , 'tip' => $subdomainForm);
    else
        $service['subdomains'] = array('style' => 'error' , 'status' => 'Disabled' , 'tip' => NULL);

    if ($bin->highlight())
        $service['syntax'] = array('style' => 'success' , 'status' => 'Enabled');
    else
        $service['syntax'] = array('style' => 'error' , 'status' => 'Disabled');

    if ($bin->lineHighlight())
        $service['highlight'] = array('style' => 'success' , 'status' => 'Enabled' , 'tip' => ' To highlight lines, prefix them with <em>' . $bin->lineHighlight() . '</em>');
    else
        $service['highlight'] = array('style' => 'error' , 'status' => 'Disabled' , 'tip' => NULL);

    $uploadForm = NULL;

    $event = "onblur=\"return checkIfURL(this);\" onkeyup";

    if ($CONFIG['pb_images'])
        $uploadForm = "<div id=\"fileUploadContainer\"><input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . $CONFIG['pb_image_maxsize'] . "\" /><label>Attach an Image (" . implode(", ", $CONFIG['pb_image_extensions']) . " &raquo; Max size " . $bin->humanReadableFilesize($CONFIG['pb_image_maxsize']) . ")</label><br /><input type=\"file\" name=\"pasteImage\" id=\"pasteImage\" /><br />(Optional)</div>";
    else
        $uploadForm = "<div id=\"fileUploadContainer\">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;</div>";

    echo "<div id=\"pastebin\" class=\"pastebin\">" . "<h1>" . $bin->setTitle($CONFIG['pb_name']) . "</h1>" . $bin->setTagline($CONFIG['pb_tagline']) . "<div id=\"result\"></div>
				<div id=\"formContainer\">
				<div><span id=\"showInstructions\">[ <a href=\"#\" onclick=\"return toggleInstructions();\">more info</a> ]</span><span id=\"showSubdomain\">" . $subdomainClicker . "</span>
				<div id=\"instructions\" class=\"instructions\"><h2>How to use</h2><div>Fill out the form with data you wish to store online. You will be given an unique address to access your content that can be sent over IM/Chat/(Micro)Blog for online collaboration (eg, " . $bin->linker('z3n') . "). The following services have been made available by the administrator of this server:</div><ul id=\"serviceList\"><li><span class=\"success\">Enabled</span> Text</li><li><span class=\"" . $service['syntax']['style'] . "\">" . $service['syntax']['status'] . "</span> Syntax Highlighting</li><li><span class=\"" . $service['highlight']['style'] . "\">" . $service['highlight']['status'] . "</span> Line Highlighting</li><li><span class=\"" . $service['editing']['style'] . "\">" . $service['editing']['status'] . "</span> Editing</li><li><span class=\"" . $service['images']['style'] . "\">" . $service['images']['status'] . "</span> Image hosting</li><li><span class=\"" . $service['image_download']['style'] . "\">" . $service['image_download']['status'] . "</span> Copy image from URL</li><li><span class=\"" . $service['url']['style'] . "\">" . $service['url']['status'] . "</span> URL Shortening/Redirection</li><li><span class=\"" . $service['subdomains']['style'] . "\">" . $service['subdomains']['status'] . "</span> Custom Subdomains</li></ul><div class=\"spacer\">&nbsp;</div><div><strong>What to do</strong></div><div>Just paste your text, sourcecode or conversation into the textbox below, add a name if you wish" . $service['images']['tip'] . " then hit submit!" . $service['url']['tip'] . "" . $service['highlight']['tip'] . "</div><div class=\"spacer\">&nbsp;</div><div><strong>Some tips about usage;</strong> If you want to put a message up asking if the user wants to continue, add an &quot;!&quot; suffix to your URL (eg, " . $bin->linker('z3n') . "!).</div><div class=\"spacer\">&nbsp;</div></div>" . $service['subdomains']['tip'] . "
					<form id=\"pasteForm\" action=\"" . $bin->linker() . "\" method=\"post\" name=\"pasteForm\" enctype=\"multipart/form-data\">
						<div><label for=\"pasteEnter\" class=\"pasteEnterLabel\">Paste your text" . $service['url']['str'] . " here!" . $service['highlight']['tip'] . "</label>
						<textarea id=\"pasteEnter\" name=\"pasteEnter\" onkeydown=\"return catchTab(event)\" " . $event . "=\"return checkIfURL(this);\"></textarea></div>
						<div id=\"foundURL\" style=\"display: none;\">URL has been detected...</div>
						<div class=\"spacer\">&nbsp;</div>
						<div id=\"secondaryFormContainer\"><input type=\"hidden\" name=\"ajax_token\" value=\"" . $bin->token(TRUE) . "\" />";

    if ($bin->highlight())
        echo $highlighterContainer;

    if (is_array($CONFIG['pb_lifespan']) && count($CONFIG['pb_lifespan']) > 1) {
        echo "<div id=\"lifespanContainer\"><label for=\"lifespan\">Paste Expiration</label> <select name=\"lifespan\" id=\"lifespan\">";

        foreach ($CONFIG['pb_lifespan'] as $span) {
            $key = array_keys($CONFIG['pb_lifespan'], $span);
            $key = $key[0];
            $options .= "<option value=\"" . $key . "\">" . $bin->event(time() - ($span * 24 * 60 * 60), TRUE) . "</option>";
        }

        $selecter = '/\>0 seconds/';
        $replacer = '>Never';
        $options = preg_replace($selecter, $replacer, $options, 1);

        echo $options;

        echo "</select></div>";
    } elseif (is_array($CONFIG['pb_lifespan']) && count($CONFIG['pb_lifespan']) == 1) {
        echo "<div id=\"lifespanContainer\"><label for=\"lifespan\">Paste Expiration</label>";
        echo " <div id=\"expireTime\"><input type=\"hidden\" name=\"lifespan\" value=\"0\" />" . $bin->event(time() - ($CONFIG['pb_lifespan'][0] * 24 * 60 * 60), TRUE) . "</div>";
        echo "</div>";
    } else
        echo "<input type=\"hidden\" name=\"lifespan\" value=\"0\" />";

    if ($CONFIG['pb_private'])
        echo "<div id=\"privacyContainer\"><label for=\"privacy\">Paste Visibility</label> <select name=\"privacy\" id=\"privacy\"><option value=\"0\">Public</option> <option value=\"1\">Private</option></select></div>";

    echo "<div class=\"spacer\">&nbsp;</div>";
    echo "<div id=\"authorContainer\"><label for=\"authorEnter\">Your Name</label><br />
						<input type=\"text\" name=\"author\" id=\"authorEnter\" value=\"" . $CONFIG['_temp_pb_author'] . "\" onfocus=\"if(this.value=='" . $CONFIG['_temp_pb_author'] . "')this.value='';\" onblur=\"if(this.value=='')this.value='" . $CONFIG['_temp_pb_author'] . "';\" maxlength=\"32\" /></div>
						" . $uploadForm . "
						<div class=\"spacer\">&nbsp;</div>
						<input type=\"text\" name=\"email\" id=\"poison\" style=\"display: none;\" />
						<div id=\"submitContainer\" class=\"submitContainer\">
							<input type=\"submit\" name=\"submit\" value=\"Submit your paste\" onclick=\"return submitPaste(this);\" id=\"submitButton\" />
						</div>
						</div>
					</form>
				</div>";
    echo "</div>";
}
?>

<?php

if (($requri && $requri != "install") && (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image'])))
    echo "<script type=\"text/javascript\">setTimeout(\"toggleWrap()\", 1000); setTimeout(\"toggleStyle()\", 1000);</script>";
elseif (($requri && $requri != "install") && (! is_bool($pasted['Image']) && ! is_numeric($pasted['Image'])))
    echo "<script type=\"text/javascript\">$(document).ready(function() { setTimeout(\"toggleWrap()\", 1000); setTimeout(\"toggleStyle()\", 1000); });</script>";
else
    echo "<!-- End of Document -->";
?>
</body>
</html>
