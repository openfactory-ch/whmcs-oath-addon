<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset={$charset}" />
    <title>{if $kbarticle.title}{$kbarticle.title} - {/if}{$pagetitle} - {$companyname}</title>

    {if $systemurl}<base href="{$systemurl}" />
    {/if}<script type="text/javascript" src="includes/jscript/jquery.js"></script>
    {if $livehelpjs}{$livehelpjs}
    {/if}
    <link href="templates/{$template}/css/bootstrap.css" rel="stylesheet">
    <link href="templates/{$template}/css/whmcs.css" rel="stylesheet">

    <script src="templates/{$template}/js/whmcs.js"></script>

    {$headoutput}

  </head>

  <body>

{$headeroutput}

<div id="whmcsheader">
    <div class="whmcscontainer">
        <div id="whmcstxtlogo"><a href="index.php">{$companyname}</a></div>
        <div id="whmcsimglogo"><a href="index.php"><img src="templates/{$template}/img/whmcslogo.png" alt="{$companyname}" /></a></div>
    </div>
</div>

  <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container">
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <div class="nav-collapse">
		<ul class="nav">
			<li><a href="{if $loggedin}clientarea{else}index{/if}.php">{$LANG.hometitle}</a></li>
		</ul>
{if $loggedin}
    <ul class="nav">
        <li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navservices}&nbsp;<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="clientarea.php?action=products">{$LANG.clientareanavservices}</a></li>
            {if $condlinks.pmaddon}<li><a href="index.php?m=project_management">{$LANG.clientareaprojects}</a></li>{/if}
            <li class="divider"></li>
            <li><a href="cart.php">{$LANG.navservicesorder}</a></li>
            <li><a href="cart.php?gid=addons">{$LANG.clientareaviewaddons}</a></li>
          </ul>
        </li>
      </ul>


		  {if $condlinks.domainreg || $condlinks.domaintrans}<ul class="nav">
			<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navdomains}&nbsp;<b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li><a href="clientarea.php?action=domains">{$LANG.clientareanavdomains}</a></li>
				<li class="divider"></li>
				<li><a href="cart.php?gid=renewals">{$LANG.navrenewdomains}</a></li>
				{if $condlinks.domainreg}<li><a href="cart.php?a=add&domain=register">{$LANG.navregisterdomain}</a></li>{/if}
				{if $condlinks.domaintrans}<li><a href="cart.php?a=add&domain=transfer">{$LANG.navtransferdomain}</a></li>{/if}
                {if $enomnewtldsenabled}<li><a href="index.php?m=enomnewtlds">Preregister New TLDs</a></li>{/if}
				<li class="divider"></li>
				<li><a href="domainchecker.php">{$LANG.navwhoislookup}</a></li>
			  </ul>
			</li>
		  </ul>{/if}

		  <ul class="nav">
			<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navbilling}&nbsp;<b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li><a href="clientarea.php?action=invoices">{$LANG.invoices}</a></li>
				<li><a href="clientarea.php?action=quotes">{$LANG.quotestitle}</a></li>
				<li class="divider"></li>
				{if $condlinks.addfunds}<li><a href="clientarea.php?action=addfunds">{$LANG.addfunds}</a></li>{/if}
				{if $condlinks.masspay}<li><a href="clientarea.php?action=masspay&all=true">{$LANG.masspaytitle}</a></li>{/if}
				{if $condlinks.updatecc}<li><a href="clientarea.php?action=creditcard">{$LANG.navmanagecc}</a></li>{/if}
			  </ul>
			</li>
		  </ul>

		  <ul class="nav">
			<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navsupport}&nbsp;<b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li><a href="supporttickets.php">{$LANG.navtickets}</a></li>
				<li><a href="knowledgebase.php">{$LANG.knowledgebasetitle}</a></li>
				<li><a href="downloads.php">{$LANG.downloadstitle}</a></li>
				<li><a href="serverstatus.php">{$LANG.networkstatustitle}</a></li>
			  </ul>
			</li>
		  </ul>

		  <ul class="nav">
			<li><a href="submitticket.php">{$LANG.navopenticket}</a></li>
		  </ul>

		  {if $condlinks.affiliates}<ul class="nav">
            <li><a href="affiliates.php">{$LANG.affiliatestitle}</a></li>
		  </ul>{/if}

		  <ul class="nav pull-right">
			<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">{$LANG.hello}, {$loggedinuser.firstname}!&nbsp;<b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li><a href="clientarea.php?action=details">{$LANG.editaccountdetails}</a></li>
				{if $condlinks.updatecc}<li><a href="clientarea.php?action=creditcard">{$LANG.navmanagecc}</a></li>{/if}
				<li><a href="clientarea.php?action=contacts">{$LANG.clientareanavcontacts}</a></li>
				{if $condlinks.addfunds}<li><a href="clientarea.php?action=addfunds">{$LANG.addfunds}</a></li>{/if}
				<li><a href="clientarea.php?action=emails">{$LANG.navemailssent}</a></li>
				<li><a href="clientarea.php?action=changepw">{$LANG.clientareanavchangepw}</a></li>
				<li><a href="index.php?m=oath">Two-Factor Authentication</a></li>
				<li class="divider"></li>
				<li><a href="logout.php">{$LANG.logouttitle}</a></li>
			  </ul>
			</li>
		  </ul>
{else}
		  <ul class="nav">
			<li><a href="announcements.php">{$LANG.announcementstitle}</a></li>
		  </ul>
          
		  <ul class="nav">
			<li><a href="knowledgebase.php">{$LANG.knowledgebasetitle}</a></li>
		  </ul>
          
		  <ul class="nav">
			<li><a href="serverstatus.php">{$LANG.networkstatustitle}</a></li>
		  </ul>
          
		  <ul class="nav">
			<li><a href="affiliates.php">{$LANG.affiliatestitle}</a></li>
		  </ul>
          
		  <ul class="nav">
			<li><a href="contact.php">{$LANG.contactus}</a></li>
		  </ul>

		  <ul class="nav pull-right">
			<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.account}&nbsp;<b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li><a href="clientarea.php">{$LANG.login}</a></li>
				<li><a href="register.php">{$LANG.register}</a></li>
				<li class="divider"></li>
				<li><a href="pwreset.php">{$LANG.forgotpw}</a></li>
			  </ul>
			</li>
		  </ul>
{/if}

        </div><!-- /.nav-collapse -->
      </div>
    </div><!-- /navbar-inner -->
  </div><!-- /navbar -->


<div class="whmcscontainer">
    <div class="contentpadded">

{if $pagetitle eq $LANG.carttitle}<div id="whmcsorderfrm">{/if}

