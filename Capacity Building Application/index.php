<?php
//page 1
session_start();
$_SESSION['collapse'] = FALSE;
$_SESSION['collapse2'] = FALSE;
$errors="";
$fileSelected="Selected: ";
//When upload is clicked add the file to the surveys folder
if (isset($_POST['update'])) {
    if(isset($_FILES['upload'])){
        $filename= $_FILES['upload']['name'];
        $fileSelected="Selected: $filename";
        if(file_exists("surveys/$filename")) {
            $errors="**That file has already been uploaded, please scroll down the list to find it";
        }
        else {
            $filename= $_FILES['upload']['name'];
            if(move_uploaded_file($_FILES['upload']['tmp_name'], "surveys/$filename")){
                $errors="Survey Successfully Added!";
            }
        }
    }
    else {
        $errors="**Please upload a .csv survey file before clicking the update button";
    }
}

//when the edit button is clicked go to the edit page
if (isset($_POST['recommend'])) {
    $_SESSION['nonprofit'] = $_POST['recommend'];
    header('Location: edit.php');
}

if (isset($_POST['goals'])) {
    $_SESSION['nonprofit'] = $_POST['goals'];
    header('Location: goals.php');
}

if (isset($_POST['actionSteps'])) {
    $_SESSION['nonprofit'] = $_POST['actionSteps'];
    header('Location: actionSteps.php');
}

?>
<!-- first page of the app with the list of nonprofit responses -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles/index.css">
    <script>
        function changeEventHandler(event){
            var name = event.target.value;
            var file = name.split('\\');
            var select = "Selected: ";
            document.getElementById("selected").innerHTML = select.concat(file[2]);
        }
        
        function searchBar(){
          // Declare variables
          var input, filter, table, tr, td, i, txtValue;
          input = document.getElementById("search");
          filter = input.value.toUpperCase();
          table = document.getElementById("table");
          tr = table.getElementsByTagName("tr");
        
          // Loop through all table rows, and hide those who don't match the search query
          for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0];
            if (td) {
              txtValue = td.textContent || td.innerText;
              if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
              } else {
                tr[i].style.display = "none";
              }
            }
          }
        }
    </script>
</head>
<body>
    <img src = "images/nccnpLogo.svg" alt = "NCCNP Logo" id = "nccnp"/>
    <form method='post' action='index.php' id='buttons' name = 'fileUploadForm' enctype="multipart/form-data">
    <input type= 'text' onkeyup='searchBar()' placeholder='Search...' id="search">
    <label id= "fileBtn" onchange='changeEventHandler(event);'>Select Survey<input type='file' name='upload' value='Upload Surveys' id ="upload"></label>
    <input type='submit' name='update' value='Update List' id ="update">
    </form>
    <br>
    <?php echo "<p id='selected'>Selected:</p>";?>
    <h4 id="instructions">Click Select Survey to choose a new .csv survey file, then click Update List to upload</h4>
        <?php echo "<h3 id='error'> $errors </h3>";?>
    <section id = "surveyList">
    <form method='post' action='index.php' name = 'editButtonForm' id= 'tableForm' enctype="multipart/form-data">
    <table id="table">
        <tr>
            <th>Nonprofit</th>
            <th>Last Edited</th>
            <th></th>
        </tr> 
        <?php  
            //Go through all the files in the surveys folder and populate the list
            $path = "surveys/";
            $lineNum = 1;
            if ($handle = opendir($path)) {
                while (false !== ($file = readdir($handle))) {
                    if ('.' === $file) continue;
                    if ('..' === $file) continue;
                    //do something with file
                    $nonprofitName = (explode(".", $file))[0];
                    echo "<tr><td class= 'npNameTd'>$nonprofitName </td>";
                    $fn = fopen("surveys/$file","r");

                    //Get the the most recent date from all save data files
                    $edited ="N/A";
                    if (file_exists("recommendationSaveData/$nonprofitName.txt")) {
                        $saveFn = fopen("recommendationSaveData/$nonprofitName.txt","r");
                        $dateLine = strtok(fgets($saveFn), ';');
                        $edited = substr($dateLine, strpos($dateLine, "=") + 1);
                    }
                    if(file_exists("goalsSaveData/$nonprofitName.txt")) {
                        $saveFn = fopen("goalsSaveData/$nonprofitName.txt","r");
                        $dateLine = strtok(fgets($saveFn), ';');
                        if($edited == "N/A") {
                            $edited = substr($dateLine, strpos($dateLine, "=") + 1);
                        }
                        else {
                            $prevDate = explode('/',$edited);
                            $curDate = explode('/',(substr($dateLine, strpos($dateLine, "=") + 1)));
                            //compare years
                            if (intval($curDate[2]) > intval($prevDate[2])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }
                            //compare years and months
                            elseif (intval($curDate[2]) == intval($prevDate[2]) && intval($curDate[0]) > intval($prevDate[0])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }
                            //compare years and months and days
                             elseif (intval($curDate[2]) == intval($prevDate[2]) && intval($curDate[0]) == intval($prevDate[0]) && intval($curDate[1]) > intval($prevDate[1])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }

                        }
                    }
                    if(file_exists("actionStepsSaveData/$nonprofitName.txt")) {
                        $saveFn = fopen("actionStepsSaveData/$nonprofitName.txt","r");
                        $dateLine = strtok(fgets($saveFn), ';');
                        if($edited == "N/A") {
                            $edited = substr($dateLine, strpos($dateLine, "=") + 1);
                        }
                        else {
                            $prevDate = explode('/',$edited);
                            $curDate = explode('/',(substr($dateLine, strpos($dateLine, "=") + 1)));
                            //compare years
                            if (intval($curDate[2]) > intval($prevDate[2])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }
                            //compare years and months
                            elseif (intval($curDate[2]) == intval($prevDate[2]) && intval($curDate[0]) > intval($prevDate[0])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }
                            //compare years and months and days
                             elseif (intval($curDate[2]) == intval($prevDate[2]) && intval($curDate[0]) == intval($prevDate[0]) && intval($curDate[1]) > intval($prevDate[1])) {
                                $edited = substr($dateLine, strpos($dateLine, "=") + 1);  
                            }
                        }
                    }
                    fclose($fn);
                    echo "<td class= 'editedTd'> $edited</td>";
                    echo "<td class= 'editTd'><button type='submit' name='recommend' class='edit' value='$nonprofitName'>Recommend</button></td>";
                    echo "<td class= 'editTd'><button type='submit' name='goals' class='goals' value='$nonprofitName'>Goals</button></td>";
                    echo "<td class= 'editTd'><button type='submit' name='actionSteps' class='actionSteps' value='$nonprofitName'>Action Steps</button></td> </tr>";
                }
            }
        ?>
    </table>
    </form>
    </section>
</body>
