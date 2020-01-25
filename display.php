<!DOCTYPE html>
<html>
<head>
    <title>DXF BRIEF</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap.min.css">
    <style type="text/css">
        body {
            margin: 30px;
        }
        td, th {
            text-align: center;
        }
    </style>
</head>
<body>
    <form action = "" method = "POST" enctype = "multipart/form-data">
        <div class="form-group">
            <label for="input_XLSX">Input Excel File: </label>
            <br />
            <input multiple id="input_XLSX" name="input_XLSX" type="file" accept=".xlsx" required />
        </div>
        <div class="form-group">
            <label for="input_DXF">Input DXF Folder: </label>
            <br />
            <input id="input_DXF" name="input_DXF[]" type="file" 
                    webkitdirectory directory multiple required />
        </div>
        <div class="form-group">
            <label for="dxfType">Input Dxf Folder Name: </label>
            <br />
            <input id="dxfType" name="dxfType" type="text" required />
        </div>
        <button type="submit" name="getTable" class="btn btn-default">Get Table</button>
    </form>
    <br />
    <br />
    <?php
        if(!empty($_POST['dxfType'])) {
            $moduleName = $_POST['dxfType'];
            $dxfFolderName = 'uploads/' . $moduleName . '/' . $moduleName . '_dxf';
            //The name of the directory that we need to create.
            $uploadDirectory = 'uploads';
                    
            //Check if the directory already exists.
            if(!is_dir($uploadDirectory))
                mkdir($uploadDirectory, 0755);

            if(!is_dir('uploads/' . $moduleName))
                mkdir('uploads/' . $moduleName , 0755);

            if(!is_dir($dxfFolderName))
                mkdir($dxfFolderName, 0755);
        }
        
        if(!empty($_FILES['input_XLSX'])) {
            $target_dir = 'uploads/' . $moduleName . '/';
            
            $uploadOk = 1;
            
            $target_file = $target_dir . $moduleName . '_list.xlsx';
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $excelFile = $target_file;
            
            if ($_FILES["input_XLSX"]["size"] > 200000) {
                echo "Sorry, your Excel file is too large." . "<br />";
                $uploadOk = 0;
            }
            // Allow certain file formats
            else if($fileType != "xlsx" && $fileType != "xls" ) {
                echo "Sorry, only xlsx, xls files are allowed." . "<br />";
                $uploadOk = 0;
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your Excel file was not uploaded." . "<br />";
            // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["input_XLSX"]["tmp_name"], $target_file)) {
                    echo "The file ". basename( $_FILES["input_XLSX"]["name"]). " has been uploaded." . "<br />";
                } else {
                    echo "Sorry, there was an error uploading your Excel file." . "<br />";
                }
            }
        }
        if(!empty($_FILES['input_DXF'])) {
            $count = 0;

            if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                foreach ($_FILES['input_DXF']['name'] as $i => $name) {
                    if (strlen($_FILES['input_DXF']['name'][$i]) > 1) {
                        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                        if($fileType != "xlsx" && $fileType != "xls" ) {
                            echo "Sorry, only dxf files are allowed." . "<br />";
                            break;
                        }

                        if (move_uploaded_file($_FILES['input_DXF']['tmp_name'][$i], 'uploads/'. $moduleName . '/' . $moduleName . '_dxf/' . $name)) 
                            $count ++;
                    }
                }
            }

            if($count == 0)
                echo "Sorry, there was an error upload your DXF files" . "<br />";
            else
                echo "The DXF Folder has been uploaded" . "<br />";

            $runPythonCmd = 'python getDxfProperties.py ' 
                            . $excelFile . ' ' . $dxfFolderName . ' ' . $moduleName;

            $command = escapeshellcmd($runPythonCmd);
            $output = shell_exec($command);
            
            function pad($number, $min_digits){
                return strrev(
                    implode(",",str_split(str_pad(strrev($number), $min_digits, "0", STR_PAD_RIGHT),3))
                );
            }
            
            $con=mysqli_connect("localhost","root","","test");
            mysqli_set_charset($con, 'utf8');
            // Check connection
            if (mysqli_connect_errno())
            {
            echo "Failed to connect to MySQL: " . mysqli_connect_error() . "<br />";
            }

            $result = mysqli_query($con,"SELECT * FROM dxfutiliy");

            echo '
            <br />
            <br />
            <table id="dxfTable" class="table table-striped table-bordered" style="margin-left: auto; margin-right: auto; ">
            <thead>
                <tr>
                    <th></th>
                    <th>Riferimento</th>
                    <th>Materiale</th>
                    <th>Spessore</th>
                    <th>Quantità</th>
                    <th>Perimeter</th>
                    <th>Misure</th>
                </tr>
            </thead>'
            ;
            echo "<tbody>";
            while($row = mysqli_fetch_array($result)) {
                $width = $row['Width'];
                $height = $row['Height'];
                $perimeter = round($row['Perimeter']);

                
                echo "<tr>";
                    echo "<td>" . '<img src="svg/' . $moduleName . '/' . $row['Nome File'] . '.dxf.svg"' . " />" . "</td>";
                    echo "<td>" . $row['Nome File'] . "</td>";
                    echo "<td>" . $row['Materiale'] . "</td>";
                    echo "<td>" . $row['Spessore'] . "</td>";
                    echo "<td>" . $row['Quantità'] . "</td>";
                    echo "<td>" . number_format($width, 0) . ' x ' . number_format($height, 0) . ' mm' . "</td>";
                    echo "<td>" . pad($perimeter, 4) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";

            mysqli_close($con);
        }
    ?>
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#dxfTable').DataTable();
        } );
    </script>
</body>
</html>