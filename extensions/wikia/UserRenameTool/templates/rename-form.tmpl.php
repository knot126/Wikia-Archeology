<?php
    global $wgTitle;
    $inputLock = $show_confirm ? ' disabled' : null;
?>

<form method='post' action='<?= $submitUrl; ?>' id='renameuser'>
	<input type="hidden" name="token" value="<?= $token; ?>"/>
	<fieldset>
		<legend><?= wfMessage( 'renameuser' )->inContentLanguage()->escaped(); ?></legend>
		<p><?= wfMessage( 'userrenametool-warning' )->inContentLanguage()->parse(); ?></p>
		<table id='mw-renameuser-table'>
			<tr>
				<td class='mw-label'>
					<label for='newusername'><?= wfMessage( 'userrenametool-new' )->inContentLanguage()->escaped(); ?></label>
				</td>
				<td class='mw-input'>
					<input type="text" name="newusername" size="20" tabindex="2" value="<?= $newusername_hsc; ?>"<?= $inputLock ?>/>
					<? if ( $show_confirm ): ?><input type="hidden" name="newusername" value="<?= $newusername_hsc; ?>"/><? endif ?>
					<span id="newUsernameEncoded"><?= wfMessage( 'userrenametool-encoded' )->escaped(); ?> <strong></strong></span>
				</td>
			</tr>
			<tr>
				<td class='mw-label'>
					<label for='newusernamerepeat'><?= wfMessage( 'userrenametool-new-repeat' )->inContentLanguage()->escaped(); ?></label>
				</td>
				<td class='mw-input'>
					<input type="text" name="newusernamerepeat" size="20" tabindex="2" value="<?= $newusername_repeat_hsc; ?>"<?= $inputLock ?>/>
                    <? if ( $show_confirm ): ?><input type="hidden" name="newusernamerepeat" value="<?= $newusername_repeat_hsc; ?>"/><? endif ?>
				</td>
			</tr>
			<tr>
				<td class='mw-label'>
					<label for='reason'><?= wfMessage( 'userrenametool-reason' )->inContentLanguage()->escaped(); ?></label>
				</td>
				<td class='mw-input'>
					<input type="text" name="reason" size="60" tabindex="3" value="<?= $reason ?>"<?= $inputLock ?>/>
					<? if ( $show_confirm ): ?><input type="hidden" name="reason" value="<?= $reason ?>"/><? endif ?>
				</td>
			</tr>
			<? if ( $warnings ): ?>
				<tr>
					<td class='mw-label'><?= wfMessage( 'userrenametool-warnings' )->inContentLanguage()->escaped(); ?></td>
					<td class='mw-input'>
						<ul style="color: orange; font-weight: bold">
							<li>
								<?= implode( '</li><li>', $warnings ); ?>
							</li>
						</ul>
						<? if ( $show_confirm ): ?>
							<p><strong><?= wfMessage( 'userrenametool-confirm-intro' )->inContentLanguage()->escaped(); ?></strong></p>
						<? endif; ?>
					</td>
				</tr>
			<? else: ?>

                <? if ( $show_confirm ): ?>
                    <tr>
                        <td><input type="hidden" name="confirm_action" value="1"/>&nbsp;</td>
                        <td class='mw-submit'>
                            <input id="submit" type="submit" name="submit" tabindex="4" value="<?= wfMessage( 'userrenametool-confirm-yes' )->inContentLanguage()->escaped(); ?>"/>
                            <input id="cancel" type="button" name="cancel" tabindex="5" value="<?= wfMessage( 'userrenametool-confirm-no' )->inContentLanguage()->escaped(); ?>" onclick="window.location.href='<?=$wgTitle->getFullURL();?>';"/>
                        </td>
                    </tr>
                <? else: ?>
                    <tr>
                        <td>&nbsp;
                        </td>
                        <td class='mw-submit'>
                            <input id="submit" type="submit" name="submit" tabindex="4" value="<?= wfMessage( 'userrenametool-submit' )->inContentLanguage()->escaped(); ?>"/>
                        </td>
                    </tr>
                <? endif; ?>

			<? endif; ?>
			<tr id="mw-warnings-row" style="display: none;">
				<td class="mw-label"><?= wfMessage( 'userrenametool-warnings' )->inContentLanguage()->escaped(); ?></td>
				<td class="mw-input">
					<ul id="mw-warnings-list">
						<li style="display: none;" id="mw-warnings-list-characters"><?= wfMessage( 'userrenametool-warnings-characters' )->inContentLanguage()->escaped(); ?></li>
						<li style="display: none;" id="mw-warnings-list-maxlength"><?= wfMessage( 'userrenametool-warnings-maxlength' )->inContentLanguage()->escaped(); ?></li>
					</ul>
				</td>
			</tr>
		</table>
	</fieldset>
</form>

<? foreach ( $errors as $message ): ?>
	<div class="errorbox">
		<?= $message; ?>
	</div>
<? endforeach; ?>

<? foreach ( $infos as $message ): ?>
	<div class="successbox">
		<?= $message; ?>
	</div>
<? endforeach; ?>
