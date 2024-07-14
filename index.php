<!DOCTYPE html>
<html>
<head>
    <title>Upload JSON File</title>
</head>
<body>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="file">Choose JSON file:</label>
        <input type="file" name="jsonfile" id="jsonfile" accept=".json" required>
        <br><br>
        <input type="submit" value="Upload">
    </form>
</body>
</html>
