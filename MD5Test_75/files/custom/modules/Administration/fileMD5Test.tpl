<html>
<head>
    <title>SugarCRM File Validation</title>
    <link rel="stylesheet" type="text/css" href="custom/modules/Administration/fileMD5Test.css">
</head>
<body>
<div id="bar_blank">
    <div id="bar_color"></div>
</div>
<div id="status"></div>
<form action="index.php" method="POST" id="MD5Test" enctype="multipart/form-data">
    <input type="hidden" name="action" value="fileMD5Test">
    <input type="hidden" name="module" value="Administration">
    <input type="hidden" name="step" value="start">
    <input type="button" id='start_button' value="Start Test">
</form>
<script type="text/javascript" src="custom/modules/Administration/fileMD5Test.js"></script>
</body>
</html>