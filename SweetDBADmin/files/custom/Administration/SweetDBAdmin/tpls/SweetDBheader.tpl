{$TITLE}
{$JQUERY}
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/ddlevelsmenu-base.css" />
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/ddlevelsmenu-topbar.css" />
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/ddlevelsmenu-sidebar.css" />

<script type="text/javascript" src="custom/modules/Administration/SweetDBAdmin/js/ddlevelsmenu.js"></script>
<script type="text/javascript" src="custom/modules/Administration/SweetDBAdmin/js/SweetDBAdmin.js"></script>

<div id="ddtopmenubar" class="slantedmenu">
<ul type="none">
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=query&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_RUN}</a></li>
<li id='searchTab'><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=search&currentTable={$TABLE}" rel="ddsubmenu4">{$mod.LBL_SWEETDBADMIN_SEARCH}</a></li>
<li id='indexTab'><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=indexes&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_INDEXES}</a></li>
<li id='historyTab'><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=history&currentTable={$TABLE}"  rel="ddsubmenu1">{$mod.LBL_SWEETDBADMIN_HISTORY}</a></li>
<li id='readlogTab'><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=readLog&currentTable={$TABLE}" rel="ddsubmenu2">{$mod.LBL_SWEETDBADMIN_READLOG}</a></li>
<li id='operationsTab'><a href="#" rel="ddsubmenu5">{$mod.LBL_SWEETDBADMIN_OPERATIONS}</a></li>
<li id='aboutTab'><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=about" rel="ddsubmenu6">{$mod.LBL_SWEETDBADMIN_ABOUT}</a></li>
</ul>
</div>

<script type="text/javascript">
ddlevelsmenu.setup("ddtopmenubar", "topbar");
</script>

<ul id="ddsubmenu1" class="ddsubmenustyle" type="none">
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=history&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_HISTORY}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=historyDeleteAll&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_DELETE_ALL_HISTORY}</a></li>
<li><a href="#">{$mod.LBL_SWEETDBADMIN_RECENT_QUERIES}</a>
<ul>
{foreach from=$HISTORYITEMS key=k item=v name=historyLoop}
	{if $smarty.foreach.historyLoop.iteration < 6}
		<li><a href="index.php?module=Administration&action=SweetDBAdmin&command=historyQuery&id={$k}">{$v.query|truncate:40:"...":true}</a></li>
	{/if}
{/foreach}
</ul>
</li>
<li><a href="#">{$mod.LBL_SWEETDBADMIN_TOP_QUERIES}</a>
<ul>
{foreach from=$TOPHISTORYITEMS key=k item=v name=historyLoop}
	{if $smarty.foreach.historyLoop.iteration < 6}
		<li><a href="index.php?module=Administration&action=SweetDBAdmin&command=historyQuery&id={$k}">{$v.query|truncate:40:"...":true}</a></li>
	{/if}
{/foreach}
</ul>
</li>
</ul>

<ul id="ddsubmenu5" class="ddsubmenustyle" type="none">
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=copyTable">{$mod.LBL_SWEETDBADMIN_COPY_TABLE}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=dropTable">{$mod.LBL_SWEETDBADMIN_DROP_TABLE}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=truncateTable">{$mod.LBL_SWEETDBADMIN_TUNC_TABLE}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=describeTable">{$mod.LBL_SWEETDBADMIN_DESC_TABLE}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=deleteCaches">{$mod.LBL_SWEETDBADMIN_DELETE_CACHES}</a></li>
{*
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=importSQL">{$mod.LBL_IMPORT_SQL}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=importCSV">{$mod.LBL_IMPORT_CSV}</a></li>
*}
</ul>

<ul id="ddsubmenu2" class="ddsubmenustyle" type="none">
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=readLog&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_READLOG}</a></li>
</ul>

<ul id="ddsubmenu4" class="ddsubmenustyle" type="none">
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&skip=0&sql=&command=search&currentTable={$TABLE}">{$mod.LBL_SWEETDBADMIN_SEARCH}</a></li>
<li><a href="index.php?module=Administration&action={$SCRIPTNAME}&command=searchAllTables">{$mod.LBL_SWEETDBADMIN_SEARCHALL}</a></li>
</ul>

<hr>
<form name=sql method='POST' action='index.php'>
    <input type='hidden' name='action' value='{$SCRIPTNAME}'>
    <input type='hidden' name='module' value='Administration'>
    <input type='hidden' name='currentTable' value='{$TABLE}'>
    <input type='hidden' name='currentModule' value='{$MODULE}'>