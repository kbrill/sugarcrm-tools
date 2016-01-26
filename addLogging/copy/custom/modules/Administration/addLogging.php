<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Logging</title>
    <link rel="stylesheet" href="custom/modules/Administration/jaofiletree.css">
    <script type="text/javascript" src="custom/modules/Administration/jquery-1.8.3.js"></script>
    <script type="text/javascript" src="custom/modules/Administration/jaofiletree.js"></script>
    <script type="text/javascript">
        jQuery.noConflict();
        jQuery(document).ready(function($) {
            $('#jao').jaofiletree({
                script  : 'index.php?entryPoint=jaoconnector',
                onclick : function(elem,type,file){
                    //alert('You clicked on '+file);
                },
                root    : '<?php echo getcwd(); ?>/'


            });
            $('#jao').bind('afteropen',function(){jQuery('#debugcontent').prepend('A folder has been opened<br/>');});
            $('#jao').bind('afterclose',function(){jQuery('#debugcontent').prepend('A folder has been closed<br/>');});
        });
    </script>
    <link href='http://fonts.googleapis.com/css?family=Archivo+Black' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Archivo+Narrow' rel='stylesheet' type='text/css'>
</head>
<body>
<div id="wrapper">
    <h1>SugarCRM Files</h1>
    <div id="jao"></div>
</div>
<div id="content">
    <h2>Actions</h2>
    <a href="#" onclick="jQuery(jQuery('#jao').jaofiletree('getchecked')).each(function(){addLogChecked(this.file);jQuery('#debugcontent').prepend(this.type+' : '+this.file+'<br/>');});return false;">Add Logging to these files</a>&nbsp;
    <br><a href="#" onclick="jQuery(jQuery('#jao').jaofiletree('getchecked')).each(function(){removeLogChecked(this.file);jQuery('#debugcontent').prepend(this.type+' : '+this.file+'<br/>');});return false;">Remove Logging from these files</a>&nbsp;
    <br><a href="#" onclick="removeLogAll();return false;">Remove Logging from all files</a>&nbsp;
</div>
<div id="debug" hidden="true">
    <a href="#" onclick="jQuery('#debugcontent').html('');return false;">Clear</a>
    <h2>Action bar</h2>
    <div id="debugcontent"></div>
</div>
<script type="text/javascript">
    function addLogChecked( fileName ) {
        $.get("index.php?entryPoint=addLogging&file="+fileName, function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });
    }
    function removeLogChecked( fileName ) {
        $.get("index.php?entryPoint=addLogging&remove=1&file="+fileName, function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });
    }
    function removeLogAll() {
        $.get("index.php?entryPoint=addLogging&remove=2", function(data, status) {
            alert("Data: " + data + "\nStatus: " + status);
        });
    }
</script>
</body>
</html>
