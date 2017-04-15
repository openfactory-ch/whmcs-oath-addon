<div class="logincontainer" style="text-align:center; width:400px">

{if $incorrect}
	<div class="alert alert-error alert-danger">{$OATH.incorrect}</div>
{/if}

{if $login}
	<p class="alert alert-info">{$OATH.enterCodeNote}</p><br />
	<form method="post" action="{$modulelink}">
	<input type="hidden" name="action" value="login" />
	<input type="text" name="code" placeholder="{$OATH.enterCode}" autocomplete="off" /><br />
	<button type="submit" class="btn btn-primary">{$OATH.btnLogin}</button>
	</form><br /><br />
	<div>
		<button class="btn" onclick="this.parentNode.innerHTML = '<form method=\'post\' action=\'{$modulelink}\'><input type=\'hidden\' name=\'action\' value=\'login\' /><input type=\'text\' name=\'emergencycode\' placeholder=\'{$OATH.enterEmCode}\' /><br /><br /><button type=\'submit\' class=\'btn btn-primary\'>{$OATH.emLogin}</button></form>'">{$OATH.lostDevice}</button>
	</div>
{elseif !$enable_clients}
	<div class="alert alert-error alert-danger">{$OATH.inactive}</div>
{elseif !$active}
	{if !$verify}
		<p class="alert alert-info">{$OATH.disabled}</p><br />
		<form method="post" action="{$modulelink}">
		<input type="hidden" name="action" value="enable" />
		<button type="submit" class="btn btn-primary">{$OATH.btnEnable}</button>
		</form>
	{else}
		<p class="alert alert-info">{$OATH.scanNote}</p><br />
		<img src="{$modulelink}&qr=1&secret={$secret}" /><br />
		<p class="alert alert-warning">{$OATH.unableScan}<br /><strong>{$secret}</strong></p><br />
		<form method="post" action="{$modulelink}">
		<input type="hidden" name="action" value="verify" />
		<input type="hidden" name="secret" value="{$secret}" />
		<input type="text" name="code" placeholder="{$OATH.enterCode}" autocomplete="off" /><br /><br />
		<button type="submit" class="btn btn-primary">{$OATH.verify}</button>
		</form><br /><br />
		<p class="alert alert-warning">{$OATH.recomApp}:<br />
			<strong>Google Authenticator</strong><br />
			(<a href="https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8" target="_blank">iOS</a>
			/ <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en" target="_blank">Android</a>)<br />
			<strong>Microsoft Authenticator Beta</strong><br />
			(<a href="https://www.microsoft.com/en-us/store/p/microsoft-authenticator-beta/9nblggh5lb73" target="_blank">Windows 10 Mobile</a>)
		</p>
	{/if}
{elseif $backupCode}
	<p class="alert alert-success">{$OATH.backupCodeInfo}</p><br />
	<h2 class="text-center">{$OATH.newBackupCodeNote}</h2>
	<p class="alert alert-warning text-center">{$newBackupCode}</p>
	<p class="text-center">{$OATH.emCodeNote}</p>
	<p class="btn"><a href="{$modulelink}" target="_self">Continue &raquo;</a></p>

{else}
	<p class="alert alert-success">{$OATH.enabled}</p><br />
	{if $allow_secret_review}
		<div>
			<button class="btn btn-primary" onclick="this.parentNode.innerHTML = '<img src=\'{$modulelink}&qr=1&secret={$secret}\' /><br /><br /><p lass=\'alert alert-warning\'>{$OATH.unableScan}<br />{$secret}</p><br /><br />{if !$firstactivation}<p>{$OATH.emCode}: {$emergencycode}<br /><br />{$OATH.emCodeNote}</p><br /><br />{/if}'">{$OATH.btnSecret}</button><br />
		</div>
	{/if}
	{if $firstactivation}
		<p>{$OATH.emCode}: {$emergencycode}<br /><br />{$OATH.emCodeNote}{if !$allow_secret_review} {$OATH.emCodeNote2}{/if}</p><br />
	{/if}
	<form method="post" action="{$modulelink}">
	<input type="hidden" name="action" value="disable" />
	<button type="submit" class="btn btn-danger">{$OATH.btnDisable}</button>
	</form><br />
	<p class="alert alert-info">{$OATH.note}</p>
{/if}

</div>
