<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2018 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

define('ISINCLUDED', 1);
require_once('init.php');

if ($SPB_CONFIG['infinity']) {
    $infinity = array('0');
}

if ($SPB_CONFIG['infinity'] && $SPB_CONFIG['infinity_default']) {
    $SPB_CONFIG['lifespan'] = array_merge((array) $infinity, (array) $SPB_CONFIG['lifespan']);
} elseif ($SPB_CONFIG['infinity'] && !$SPB_CONFIG['infinity_default']) {
    $SPB_CONFIG['lifespan'] = array_merge((array) $SPB_CONFIG['lifespan'], (array) $infinity);
}

$requri = $_SERVER['REQUEST_URI'];
$scrnam = $_SERVER['SCRIPT_NAME'];
$reqhash = NULL;

$info = explode('/', str_replace($scrnam, '', $requri));

$requri = str_replace('?', '', $info[0]);

if (!file_exists('./INSTALL_LOCK') && $requri != 'install') {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?install');
}

if (file_exists('./INSTALL_LOCK') && $SPB_CONFIG['rewrite_enabled']) {
    $requri = $_GET['i'];
}

$SPB_CONFIG['requri'] = $requri;

if (strstr($requri, '@')) {
    $tempRequri = explode('@', $requri, 2);
    $requri = $tempRequri[0];
    $reqhash = $tempRequri[1];
}

$db = new \SPB\DB($SPB_CONFIG);
$bin = new \SPB\Bin($db);

$SPB_CONFIG['admin_password'] = $bin->hasher($SPB_CONFIG['admin_password'], $SPB_CONFIG['salts']);
$db->config['admin_password'] = $SPB_CONFIG['admin_password'];
$bin->db->config['admin_password'] = $SPB_CONFIG['admin_password'];

$ckey = $bin->cookieName();

if (@$_POST['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
    setcookie($ckey, $bin->checkAuthor(@$_POST['author']), time() + $SPB_CONFIG['author_cookie']);
}

$SPB_CONFIG['_temp_author'] = $_COOKIE[$ckey];

switch ($_COOKIE[$ckey]) {
    case NULL:
        $SPB_CONFIG['_temp_author'] = $SPB_CONFIG['author'];
        break;
    case $SPB_CONFIG['author']:
        $SPB_CONFIG['_temp_author'] = $SPB_CONFIG['author'];
        break;
    default:
        $SPB_CONFIG['_temp_author'] = $_COOKIE[$ckey];
        break;
}

if ($requri != 'install' && $requri != NULL && substr($requri, - 1) != "!" && !$_POST['adminProceed'] && $reqhash == 'raw') {
    if ($pasted = $db->readPaste($requri)) {
        header('Content-Type: text/plain; charset=utf-8');
        die($db->rawHTML($bin->noHighlight($pasted['Data'])));
    } else {
        die('There was an error!');
    }
}

$pasteinfo = array();
if ($requri != 'install') {
    $bin->cleanUp($SPB_CONFIG['recent_posts']);
}

$title = $SPB_CONFIG['pastebin_title']
         ? htmlspecialchars($SPB_CONFIG['pastebin_title'], ENT_COMPAT, 'UTF-8', FALSE)
         : 'Pastebin on ' . $_SERVER['SERVER_NAME'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title><?php echo $title; ?> &raquo; <?php echo $requri ? $requri : 'Welcome!'; ?></title>
<meta name="robots" content="<?php echo $bin->robotPrivacy($requri); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $SPB_CONFIG['stylesheet']; ?>" media="screen, print" />
<script type="text/javascript" src="js/main.js"></script>
</head>
<body>
<div id="siteWrapper">
<?php
if ($requri != 'install' && !$db->connect()) {
    echo '<div class="error">Data storage is unavailable - check your config.</div>';
}

if (@$_POST['adminAction'] == 'delete' && $bin->hasher(hash($SPB_CONFIG['algo'], @$_POST['adminPass']), $SPB_CONFIG['salts']) === $SPB_CONFIG['admin_password']) {
    $db->dropPaste($requri);
    echo '<div class="success">Paste, ' . $requri . ', has been deleted!</div>';
    $requri = NULL;
}

if ($requri != 'install' && @$_POST['submit']) {
    $acceptTokens = $bin->token();

    if (@$_POST['email'] != '' || !in_array($_POST['ajax_token'], $acceptTokens)) {
        die('<div class="result"><div class="error">Spambot detected, I don\'t like that!</div></div></div></body></html>');
    }

    $pasteID = $bin->generateID();

    $exclam = NULL;

    $paste = array('ID' => $pasteID, 'Author' => $bin->checkAuthor(@$_POST['author']), 'IP' => $_SERVER['REMOTE_ADDR'], 'Lifespan' => $_POST['lifespan'], 'Protect' => $_POST['privacy'], 'Parent' => $requri, 'Content' => @$_POST['pasteEnter']);

    if (@$_POST['pasteEnter'] == @$_POST['originalPaste'] && strlen($_POST['pasteEnter']) > 10) {
        die('<div class="error">Please don\'t just repost what has already been said!</div></div></body></html>');
    }

    if (strlen(@$_POST['pasteEnter']) > 10 && mb_strlen($paste['Content']) <= $SPB_CONFIG['max_bytes'] && $db->insertPaste($paste['ID'], $paste)) {
        die('<div class="result"><div class="success">Your paste has been successfully recorded!</div><div class="confirmURL">URL to your paste is <a href="' . $bin->linker($paste['ID']) . $exclam . '">' . $bin->linker($paste['ID']) . '</a></div></div></div></body></html>');
    } else {
        echo '<div class="error">Hmm, something went wrong.</div>';
        echo '<div class="warn">Pasted text must be between 10 characters and ' . $bin->humanReadableFilesize($SPB_CONFIG['max_bytes']) . '</div>';
    }
}

if ($requri != 'install' && $SPB_CONFIG['recent_posts'] && substr($requri, - 1) != '!') {
    echo '<div id="recentPosts" class="recentPosts">';
    $recentPosts = $bin->getLastPosts($SPB_CONFIG['recent_posts']);
    echo '<h2 id="newPaste"><a href="' . $bin->linker() . '">New Paste</a></h2><div class="spacer">&nbsp;</div>';
    if ($requri || count($recentPosts) > 0) {
        if (count($recentPosts) > 0) {
            echo '<h2>Recent Pastes</h2>';
            echo '<ul id="postList" class="recentPosts">';
            foreach ($recentPosts as $paste_) {
                $rel = NULL;
                $exclam = NULL;

                echo '<li id="' . $paste_['ID'] . '" class="postItem"><a href="' . $bin->linker($paste_['ID']) . $exclam . '"' . $rel . '>' . stripslashes($paste_['Author']) . '</a><br />' . $bin->event($paste_['Datetime']) . ' ago.</li>';
            }
            echo '</ul>';
        } else {
            echo '&nbsp;';
        }
    }
    if ($requri) {
        echo '<div id="showAdminFunctions"><a href="#" onclick="return toggleAdminTools();">Show Admin tools</a></div><div id="hiddenAdmin"><h2>Administrate</h2>';
        echo '<div id="adminFunctions">
							<form id="adminForm" action="' . $bin->linker($requri) . '" method="post">
								<label for="adminPass">Password</label><br />
								<input id="adminPass" type="password" name="adminPass" value="' . @$_POST['adminPass'] . '" />
								<br /><br />
								<select id="adminAction" name="adminAction">
									<option value="ip">Show Author\'s IP</option>
									<option value="delete">Delete Paste</option>
								</select>
								<input type="submit" name="adminProceed" value="Proceed" />
							</form>
						</div></div>';
    }
    echo '</div>';
} else {
    if ($requri && $requri != 'install' && substr($requri, - 1) != '!') {
        echo '<div id="recentPosts" class="recentPosts">';
        echo '<h2><a href="' . $bin->linker() . '">New Paste</a></h2><div class="spacer">&nbsp;</div>';
        echo '<div id="showAdminFunctions"><a href="#" onclick="return toggleAdminTools();">Show Admin tools</a></div><div id="hiddenAdmin"><h2>Administrate</h2>';
        echo '<div id="adminFunctions">
							<form id="adminForm" action="' . $bin->linker($requri) . '" method="post">
								<label for="adminPass">Password</label><br />
								<input id="adminPass" type="password" name="adminPass" value="' . @$_POST['adminPass'] . '" />
								<br /><br />
								<select id="adminAction" name="adminAction">
									<option value="ip">Show Author\'s IP</option>
									<option value="delete">Delete Paste</option>
								</select>
								<input type="submit" name="adminProceed" value="Proceed" />
							</form>
						</div></div>';
        echo '</div>';
    }
}

if ($requri && $requri != 'install' && substr($requri, - 1) != '!') {
    $pasteinfo['Parent'] = $requri;
    echo '<div id="pastebin" class="pastebin"><h1>' . $title . '</h1>';
     if ($SPB_CONFIG['tagline']) {
        echo '<div id="tagline">' . $SPB_CONFIG['tagline'] . '</div>';
    }
    echo '<div id="result"></div>';

    if ($pasted = $db->readPaste($requri)) {

        $pasted['Data'] = array('Orig' => $pasted['Data'], 'noHighlight' => array());

        $pasted['Data']['Dirty'] = $db->dirtyHTML($pasted['Data']['Orig']);
        $pasted['Data']['noHighlight']['Dirty'] = $bin->noHighlight($pasted['Data']['Dirty']);

        $pasteSize = $bin->humanReadableFilesize(mb_strlen($pasted['Data']['Orig']));

        if ($pasted['Lifespan'] == 0) {
            $pasted['Lifespan'] = time() + time();
            $lifeString = 'Never';
        } else {
            $lifeString = 'in ' . $bin->event(time() - ($pasted['Lifespan'] - time()));
        }

        if (gmdate('U') > $pasted['Lifespan']) {
            $db->dropPaste($requri);
            die('<div class="result"><div class="warn">This paste has either expired or doesn\'t exist!</div></div></div></body></html>');
        }

        echo '<div id="aboutPaste"><div id="pasteID"><strong>PasteID</strong>: ' . $requri . '</div><strong>Pasted by</strong> ' . stripslashes($pasted['Author']) . ', <em title="' . $bin->event($pasted['Datetime']) . ' ago">' . gmdate($SPB_CONFIG['datetime_format'], $pasted['Datetime']) . ' GMT</em><br />
					<strong>Expires</strong> ' . $lifeString . '<br />
					<strong>Paste size</strong> ' . $pasteSize . '</div>';

        if (@$_POST['adminAction'] == 'ip' && $bin->hasher(hash($SPB_CONFIG['algo'], @$_POST['adminPass']), $SPB_CONFIG['salts']) === $SPB_CONFIG['admin_password']) {
            echo '<div class="success"><strong>Author IP Address</strong> <a href="http://whois.domaintools.com/' . base64_decode($pasted['IP']) . '">' . base64_decode($pasted['IP']) . '</a></div>';
        }

        if (strlen($pasted['Parent']) > 0) {
            echo '<div class="warn"><strong>This is an edit of</strong> <a href="' . $bin->linker($pasted['Parent']) . '">' . $bin->linker($pasted['Parent']) . '</a></div>';
        }

        echo '<div id="styleBar"><strong>Toggle</strong> <a href="#" onclick="return toggleExpand();">Expand</a> &nbsp;  <a href="#" onclick="return toggleWrap();">Wrap</a> &nbsp; <a href="#" onclick="return toggleStyle();">Style</a> &nbsp; <a href="' . $bin->linker($pasted['ID'] . '@raw') . '">Raw</a></div>';

        echo '<div class="spacer">&nbsp;</div>';

        echo '<div id="retrievedPaste"><div id="lineNumbers"><ol id="orderedList" class="monoText">';
        $lines = explode("\n", $pasted['Data']['Dirty']);
        foreach ($lines as $line) {
            echo '<li class="line"><pre>' . str_replace(array("\n", "\r"), '&nbsp;', $bin->filterHighlight($line)) . '&nbsp;</pre></li>';
        }
        echo '</ol></div></div>';

        if ($bin->lineHighlight()) {
            $lineHighlight = 'To highlight lines, prefix them with <em>' . $bin->lineHighlight() . '</em>';
        } else {
            $lineHighlight = NULL;
        }

        if ($SPB_CONFIG['editing']) {
            echo '<div id="formContainer">
					<form id="pasteForm" name="pasteForm" action="' . $bin->linker($pasted['ID']) . '" method="post">
						<div><label for="pasteEnter" class="pasteEnterLabel">Edit this post! ' . $lineHighlight . '</label>
						<textarea id="pasteEnter" name="pasteEnter" onkeydown="return catchTab(event)" onkeyup="return true;">' . $pasted['Data']['noHighlight']['Dirty'] . '</textarea></div>
						<div class="spacer">&nbsp;</div>';

            if (is_array($SPB_CONFIG['lifespan']) && count($SPB_CONFIG['lifespan']) > 1) {
                echo '<div id="lifespanContainer"><label for="lifespan">Paste Expiration</label> <select name="lifespan" id="lifespan">';

                foreach ($SPB_CONFIG['lifespan'] as $span) {
                    $key = array_keys($SPB_CONFIG['lifespan'], $span);
                    $key = $key[0];
                    $options .= '<option value="' . $key . '">' . $bin->event(time() - ($span * 24 * 60 * 60), TRUE) . '</option>';
                }

                $selecter = '/\>0 seconds/';
                $replacer = '>Never';
                $options = preg_replace($selecter, $replacer, $options, 1);

                echo $options;

                echo '</select></div>';
            } elseif (is_array($SPB_CONFIG['lifespan']) && count($SPB_CONFIG['lifespan']) == 1) {
                echo '<div id="lifespanContainer"><label for="lifespan">Paste Expiration</label>';

                echo ' <div id="expireTime"><input type="hidden" name="lifespan" value="0" />' . $bin->event(time() - ($SPB_CONFIG['lifespan'][0] * 24 * 60 * 60), TRUE) . '</div>';

                echo '</div>';
            } else {
                echo '<input type="hidden" name="lifespan" value="0" />';
            }

            $enabled = NULL;

            if ($pasted['Protection']) {
                $enabled = 'disabled';
            }

            $privacyContainer = '<div id="privacyContainer"><label for="privacy">Paste Visibility</label> <select name="privacy" id="privacy" ' . $enabled . '><option value="0">Public</option> <option value="1">Private</option></select></div>';

            $selecter = '/value="' . $pasted['Protection'] . '"/';
            $replacer = 'value="' . $pasted['Protection'] . '" selected="selected"';
            $privacyContainer = preg_replace($selecter, $replacer, $privacyContainer, 1);

            if ($pasted['Protection']) {
                $selecter = '/\<\/select\>/';
                $replacer = '</select><input type="hidden" name="privacy" value="' . $pasted['Protection'] . '" />';
                $privacyContainer = preg_replace($selecter, $replacer, $privacyContainer, 1);
            }

            if ($SPB_CONFIG['private']) {
                echo $privacyContainer;
            }

            echo '<div class=\"spacer\">&nbsp;</div>';

            echo '<div id="authorContainerReply"><label for="authorEnter">Your Name</label><br />
						<input type="text" name="author" id="authorEnter" value="' . $SPB_CONFIG['_temp_author'] . '" onfocus="if(this.value==\'' . $SPB_CONFIG['_temp_author'] . '\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'' . $SPB_CONFIG['_temp_author'] . '\';" maxlength="32" /></div>
						<div class="spacer">&nbsp;</div>
						<input type="text" name="email" id="poison" style="display: none;" />
						<input type="hidden" name="ajax_token" value="' . $bin->token(TRUE) . '" />
						<input type="hidden" name="originalPaste" id="originalPaste" value="' . $pasted['Data']['noHighlight']['Dirty'] . '" />
						<input type="hidden" name="parent" id="parentThread" value="' . $requri . '" />
						<input type="hidden" name="thisURI" id="thisURI" value="' . $bin->linker($pasted['ID']) . '" />
						<div id="submitContainer" class="submitContainer">
							<input type="submit" name="submit" value="Submit your paste" onclick="return submitPaste(this);" id="submitButton" />
						</div>
					</form>
				</div>
				<div class="spacer">&nbsp;</div><div class="spacer">&nbsp;</div>';
        } else {
            echo '<form id="pasteForm" name="pasteForm" action="' . $bin->linker($pasted['ID']) . '" method="post">
							<input type="hidden" name="originalPaste" id="originalPaste" value="' . $pasted['Data']['Dirty'] . '" />
							<input type="hidden" name="parent" id="parentThread" value="' . $requri . '" />
							<input type="hidden" name="thisURI" id="thisURI" value="' . $bin->linker($pasted['ID']) . '" />
						</form><div class="spacer">&nbsp;</div><div class="spacer">&nbsp;</div>';
        }

    } else {
        echo '<div class="result"><div class="warn">This paste has either expired or doesn\'t exist!</div></div>';
        $requri = NULL;
    }
    echo '</div>';
} elseif ($requri && $requri != 'install' && substr($requri, - 1) == '!') {

    echo '<div class="result"><h1>Just a sec!</h1><div class="warn">You are about to visit a post that the author has marked as requiring confirmation to view.</div>
          <div class="infoMessage">If you wish to view the content <strong><a href="' . $bin->linker(substr($requri, 0, - 1)) . '">click here</a></strong>. Please note that the owner of this pastebin will not be held responsible for the content of the site.<br /><br /><a href="' . $bin->linker() . '">Take me back...</a></div></div>';

    echo '<div id="showAdminFunctions"><a href="#" onclick="return toggleAdminTools();">Show Admin tools</a></div><div id="hiddenAdmin"><div class="spacer">&nbsp;</div><h2>Administrate</h2>';
    echo '<div id="adminFunctions">
							<form id="adminForm" action="' . $bin->linker(substr($requri, 0, - 1)) . '" method="post">
								<label for="adminPass">Password</label><br />
								<input id="adminPass" type="password" name="adminPass" value="' . @$_POST['adminPass'] . '" />
								<br /><br />
								<select id="adminAction" name="adminAction">
									<option value="ip">Show Author\'s IP</option>
									<option value="delete">Delete Paste</option>
								</select>
								<input type="submit" name="adminProceed" value="Proceed" />
							</form>
						</div></div>';
    die('</div></body></html>');
} elseif (isset($requri) && $requri == 'install') {
    $stage = array();
    echo '<div id="installer" class="installer"><h1>Installing Pastebin</h1>';

    if (file_exists('./INSTALL_LOCK')) {
        die('<div class="warn"><strong>Already Installed!</strong></div></div></body></html>');
    }

    echo '<ul id="installList">';
    echo '<li>Checking Directory is writable. ';
    if (!is_writable($bin->thisDir())) {
        echo '<span class="error">Directory is not writable!</span> - CHMOD to 0777';
    } else {
        echo '<span class="success">Directory is writable!</span>';
        $stage[] = 1;
    }
    echo '</li>';

    if (count($stage) > 0) {
        echo '<li>Quick password check. ';
        $passLen = array(8, 9, 10, 11, 12);
        shuffle($passLen);
        if ($SPB_CONFIG['admin_password'] === $bin->hasher(hash($SPB_CONFIG['algo'], 'password'), $SPB_CONFIG['salts']) || !isset($SPB_CONFIG['admin_password'])) {
            echo '<span class="error">Password is still default!</span> &nbsp; &raquo; &nbsp; Suggested password: <em>' . $bin->generateRandomString($passLen[0]) . '</em>';
        } else {
            echo '<span class="success">Password is not default!</span>';
            $stage[] = 1;
        }
        echo '</li>';
    }

    if (count($stage) > 1) {
        echo '<li>Quick Salt Check. ';
        $no_salts = count($SPB_CONFIG['salts']);
        $saltLen = array(8, 9, 10, 11, 12, 14, 16, 25, 32);
        shuffle($saltLen);
        if ($no_salts < 4 || ($SPB_CONFIG['salts'][1] == 'str001' || $SPB_CONFIG['salts'][2] == 'str002' || $SPB_CONFIG['salts'][3] == 'str003' || $SPB_CONFIG['salts'][4] == 'str004')) {
            echo '<span class="error">Salt strings are inadequate!</span> &nbsp; &raquo; &nbsp; Suggested salts: <ol><li>' . $bin->generateRandomString($saltLen[0]) . '</li><li>' . $bin->generateRandomString($saltLen[1]) . '</li><li>' . $bin->generateRandomString($saltLen[2]) . '</li><li>' . $bin->generateRandomString($saltLen[3]) . '</li></ol>';
        } else {
            echo '<span class="success">Salt strings are adequate!</span>';
            $stage[] = 1;
        }
        echo '</li>';
    }

    if (count($stage) > 2) {
        echo '<li>Checking Database Connection. ';

        if (!is_dir($SPB_CONFIG['data_dir'])) {
            mkdir($SPB_CONFIG['data_dir']);
            chmod($SPB_CONFIG['data_dir'], $SPB_CONFIG['dir_bitmask']);
        }
        $db->write($db->serializer(array()), $SPB_CONFIG['data_dir'] . '/' . $SPB_CONFIG['index_file']);
        $db->write('FORBIDDEN', $SPB_CONFIG['data_dir'] . '/index.html');
        chmod($SPB_CONFIG['data_dir'] . '/' . $SPB_CONFIG['index_file'], $SPB_CONFIG['file_bitmask']);
        chmod($SPB_CONFIG['data_dir'] . '/index.html', $SPB_CONFIG['file_bitmask']);

        if (!$db->connect()) {
            echo '<span class="error">Cannot connect to database!</span> - Check Config in index.php';
        } else {
            echo '<span class="success">Connected to database!</span>';
            $stage[] = 1;
        }
        echo '</li>';
    }

    if (count($stage) > 3) {
        $stage[] = 1;
    }

    if (count($stage) > 4) {
        $stage[] = 1;
    }

    if (count($stage) > 5) {
        echo '<li>Locking Installation. ';
        if (!$db->write(time(), './INSTALL_LOCK')) {
            echo '<span class="error">Writing Error</span>';
        } else {
            echo '<span class="success">Complete</span>';
            $stage[] = 1;
            chmod('./INSTALL_LOCK', $SPB_CONFIG['file_bitmask']);
        }
        echo '</li>';
    }
    echo '</ul>';
    if (count($stage) > 6) {
        $paste_new = array('ID' => $bin->generateRandomString($SPB_CONFIG['id_length']), 'Author' => 'System', 'IP' => $_SERVER['REMOTE_ADDR'], 'Lifespan' => 1800, 'Protect' => 0, 'Content' => $SPB_CONFIG['line_highlight'] . "Congratulations, your pastebin has now been installed!\nThis message will expire in 30 minutes!");
        $db->insertPaste($paste_new['ID'], $paste_new, TRUE);
        echo '<div id="confirmInstalled"><a href="' . $bin->linker() . '">Continue</a> to your new installation!<br /></div>';
        echo '<div id="confirmInstalled" class="warn">It is recommended that you now CHMOD this directory to 755</div>';
    }
    echo '</div>';
} else {

    if ($SPB_CONFIG['editing']) {
        $service['editing'] = array('style' => 'success', 'status' => 'Enabled');
    } else {
        $service['editing'] = array('style' => 'error', 'status' => 'Disabled');
    }

    if ($bin->lineHighlight()) {
        $service['highlight'] = array('style' => 'success', 'status' => 'Enabled', 'tip' => ' To highlight lines, prefix them with <em>' . $bin->lineHighlight() . '</em>');
    } else {
        $service['highlight'] = array('style' => 'error', 'status' => 'Disabled', 'tip' => NULL);
    }

    echo '<div id="pastebin" class="pastebin">' . '<h1>' . $title . '</h1>';
     if ($SPB_CONFIG['tagline']) {
        echo '<div id="tagline">' . $SPB_CONFIG['tagline'] . '</div>';
    }
    echo '<div id="result"></div>
				<div id="formContainer">
				<div><span id="showInstructions">[ <a href="#" onclick="return toggleInstructions();">more info</a> ]</span>
				<div id="instructions" class="instructions"><h2>How to use</h2><div>Fill out the form with data you wish to store online. You will be given an unique address to access your content that can be sent over IM/Chat/(Micro)Blog for online collaboration (eg, ' . $bin->linker('z3n') . '). The following services have been made available by the administrator of this server:</div><ul id="serviceList"><li><span class="success">Enabled</span> Text</li><li><span class="' . $service['highlight']['style'] . '">' . $service['highlight']['status'] . '</span> Line Highlighting</li><li><span class="' . $service['editing']['style'] . '">' . $service['editing']['status'] . '</span> Editing</li></ul><div class="spacer">&nbsp;</div><div><strong>What to do</strong></div><div>Just paste your text, sourcecode or conversation into the textbox below, add a name if you wish then hit submit!' . $service['highlight']['tip'] . '</div><div class="spacer">&nbsp;</div><div><strong>Some tips about usage;</strong> If you want to put a message up asking if the user wants to continue, add an &quot;!&quot; suffix to your URL (eg, ' . $bin->linker('z3n') . '!).</div><div class="spacer">&nbsp;</div></div>
					<form id="pasteForm" action="' . $bin->linker() . '" method="post" name="pasteForm" enctype="multipart/form-data">
						<div><label for="pasteEnter" class="pasteEnterLabel">Paste your text here!</label>
						<textarea id="pasteEnter" name="pasteEnter" onkeydown="return catchTab(event)" onkeyup="return true;"></textarea></div>
						<div class="spacer">&nbsp;</div>
						<div id="secondaryFormContainer"><input type="hidden" name="ajax_token" value="' . $bin->token(TRUE) . '" />';

    if (is_array($SPB_CONFIG['lifespan']) && count($SPB_CONFIG['lifespan']) > 1) {
        echo '<div id="lifespanContainer"><label for="lifespan">Paste Expiration</label> <select name="lifespan" id="lifespan">';

        foreach ($SPB_CONFIG['lifespan'] as $span) {
            $key = array_keys($SPB_CONFIG['lifespan'], $span);
            $key = $key[0];
            $options .= '<option value="' . $key . '">' . $bin->event(time() - ($span * 24 * 60 * 60), TRUE) . '</option>';
        }

        $selecter = '/\>0 seconds/';
        $replacer = '>Never';
        $options = preg_replace($selecter, $replacer, $options, 1);

        echo $options;

        echo '</select></div>';
    } elseif (is_array($SPB_CONFIG['lifespan']) && count($SPB_CONFIG['lifespan']) == 1) {
        echo '<div id="lifespanContainer"><label for="lifespan">Paste Expiration</label>';
        echo ' <div id="expireTime"><input type="hidden" name="lifespan" value="0" />' . $bin->event(time() - ($SPB_CONFIG['lifespan'][0] * 24 * 60 * 60), TRUE) . '</div>';
        echo '</div>';
    } else {
        echo '<input type="hidden" name="lifespan" value="0" />';
    }

    if ($SPB_CONFIG['private']) {
        echo '<div id="privacyContainer"><label for="privacy">Paste Visibility</label> <select name="privacy" id="privacy"><option value="0">Public</option> <option value="1">Private</option></select></div>';
    }

    echo '<div class="spacer">&nbsp;</div>';
    echo '<div id="authorContainer"><label for="authorEnter">Your Name</label><br />
						<input type="text" name="author" id="authorEnter" value="' . $SPB_CONFIG['_temp_author'] . '" onfocus="if(this.value==\'' . $SPB_CONFIG['_temp_author'] . '\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'' . $SPB_CONFIG['_temp_author'] . '\';" maxlength="32" /></div>
						<div class="spacer">&nbsp;</div>
						<input type="text" name="email" id="poison" style="display: none;" />
						<div id="submitContainer" class="submitContainer">
							<input type="submit" name="submit" value="Submit your paste" onclick="return submitPaste(this);" id="submitButton" />
						</div>
						</div>
					</form>
				</div>';
    echo '</div>';
}
?>
</body>
</html>
