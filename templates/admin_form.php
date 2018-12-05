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

// Prevent template from direct access
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}
?>
<!-- Begin of admin form -->
                <div id="showAdminFunctions">
                    <a href="#" onclick="return toggleAdminTools();"><?php echo t('Administrate'); ?></a>
                </div>
                <div id="hiddenAdmin">
                    <h2><?php echo t('Administrate'); ?></h2>
                    <div id="adminFunctions">
                        <form id="adminForm" action="<?php echo $page['thisURL']; ?>" method="post">
                            <label for="adminPass"><?php echo t('Password'); ?></label>
                            <br/>
                            <input id="adminPass" type="password" name="adminPass" value="<?php echo $post_values['adminPass']; ?>"/>
                            <br/>
                            <br/>
                            <select id="adminAction" name="adminAction">
                                <option value="ip"><?php echo t('Show author\'s IP'); ?></option>
                                <option value="delete"><?php echo t('Delete data'); ?></option>
                            </select>
                            <input type="submit" name="adminProceed" value="<?php echo t('OK'); ?>"/>
                        </form>
                    </div>
                </div>
<!-- End of admin form -->
