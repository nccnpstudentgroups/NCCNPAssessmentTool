<?php
session_start();

$_SESSION['show'] = '';
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
        $errors="**Please upload a .csv survey file before clicking the upload button";
    }
}

//When the edit button is clicked go to the edit page
if (isset($_POST['recommend'])) {
    $_SESSION['nonprofit'] = $_POST['recommend'];
    header('Location: edit2.php');
}

if (isset($_POST['goals'])) {
    $_SESSION['nonprofit'] = $_POST['goals'];
    header('Location: goals2.php');
}

if (isset($_POST['actionSteps'])) {
    $_SESSION['nonprofit'] = $_POST['actionSteps'];
    header('Location: actionSteps2.php');
}

?>
<!-- first page of the app with the list of nonprofit responses -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles/index.css">
    <link href='https://fonts.googleapis.com/css?family=Lato' rel='stylesheet'>
    <script>
        function changeEventHandler(event){
            var name = event.target.value;
            var file = name.split('\\');
            var select = "";
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

        function helpText() {
            var popup = document.getElementById("myPopup");
            popup.classList.toggle("show");
        }
        function sortTable(n,type) {
          var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
          table = document.getElementById("table");
          switching = true;
          //Set the sorting direction to ascending:
          dir = "asc";
          /*Make a loop that will continue until
          no switching has been done:*/
          while (switching) {
            //start by saying: no switching is done:
            switching = false;
            rows = table.rows;
            /*Loop through all table rows (except the
            first, which contains table headers):*/
            for (i = 1; i < (rows.length - 1); i++) {
              //start by saying there should be no switching:
              shouldSwitch = false;
              /*Get the two elements you want to compare,
              one from current row and one from the next:*/
              x = rows[i].getElementsByTagName("TD")[n];
              y = rows[i + 1].getElementsByTagName("TD")[n];
              /*check if the two rows should switch place,
              based on the direction, asc or desc:*/
              if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                  //if so, mark as a switch and break the loop:
                  shouldSwitch= true;
                  break;
                }
              } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                  //if so, mark as a switch and break the loop:
                  shouldSwitch = true;
                  break;
                }
              }
            }
            if (shouldSwitch) {
              /*If a switch has been marked, make the switch
              and mark that a switch has been done:*/
              rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
              switching = true;
              //Each time a switch is done, increase this count by 1:
              switchcount ++;
            } else {
              /*If no switching has been done AND the direction is "asc",
              set the direction to "desc" and run the while loop again.*/
              if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
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
    <input type='file' onchange='changeEventHandler(event);' name='upload' id="upload" class="inputfile"/>
    <label  for='upload'>Choose Survey File</label>
    <input type='submit' onchange='changeEventHandler(event);' name='update' id ='update' class='uploadFormat'/>
    <label for='update'>Upload</label>
    <div class="popup">
        ?
        <span class="popuptext" id="myPopup">
            <h4>Need Help?</h4>
            <p><strong>Choose Survey File:</strong> Click to select a new .csv file.</p>
            <p><strong>Upload:</strong> Click to upload the selected .csv survey file and update the viewable list.</p>
        </span>
    </div>
    </form>
    <br>

    <?php echo "<h3 id='error'> $errors </h3>";?>
    <section id = "surveyList">
    <form method='post' action='index.php' name = 'editButtonForm' id= 'tableForm' enctype="multipart/form-data">
    <table id="table">
        <tr>
            <th onclick="sortTable(0)">Organization</th>
            <th onclick="sortTable(1)">Start Date</th>
            <th onclick="sortTable(2)">Last Edited</th>
            <th>Capacity Building Steps</th>
	        <th></th>
	        <th></th>
	        <th onclick="sortTable(6)">Status</th>
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

                    $edited ="N/A";
                    //Connect to the database
                    require_once 'mysqli_connect.php';

                    //SQL code for retrieving the most recent date
                    $sql = "SELECT * FROM Recommendations WHERE Nonprofit='$nonprofitName' ORDER BY Date DESC LIMIT 1";
                    $sqlDates = mysqli_query($conn, $sql);

                    //Fetch the results as an associative array
                    mysqli_fetch_all($sqlDates, MYSQLI_ASSOC);

                    foreach($sqlDates as $sqlDate){
                      if(isset($sqlDate['Date'])) {
                        $edited = $sqlDate['Date'];
                      }
                    }
                    
                    $started ="N/A";

                    //SQL code for retrieving the least recent date
                    $sql = "SELECT * FROM Recommendations WHERE Nonprofit='$nonprofitName' ORDER BY Date LIMIT 1";
                    $sqlDates = mysqli_query($conn, $sql);

                    //Fetch the results as an associative array
                    mysqli_fetch_all($sqlDates, MYSQLI_ASSOC);

                    foreach($sqlDates as $sqlDate){
                      if(isset($sqlDate['Date'])) {
                        $started = $sqlDate['Date'];
                      }
                    }
                    
                    $status = '-';
                    
                    //SQL code for retrieving the status
                    $sql = "SELECT * FROM Recommendations WHERE Nonprofit='$nonprofitName' AND Number=0";
                    $sqlDates = mysqli_query($conn, $sql);

                    //Fetch the results as an associative array
                    mysqli_fetch_all($sqlDates, MYSQLI_ASSOC);
                    
                    foreach($sqlDates as $sqlDate){
                        if(isset($sqlDate['Status'])) {
                            $status = $sqlDate['Status'];
                        }
                    }

                    echo "<td class= 'editedTd'> $started</td>";
                    echo "<td class= 'editedTd'> $edited</td>";
                    echo "<td class= 'editTd'><button type='submit' name='recommend' class='edit' value='$nonprofitName'>Recommendations</button></td>";
                    echo "<td class= 'editTd'><button type='submit' name='goals' class='goals' value='$nonprofitName'>Goals</button></td>";
                    echo "<td class= 'editTd'><button type='submit' name='actionSteps' class='actionSteps' value='$nonprofitName'>Action Steps</button></td>";
                    echo "<td class= '$status'> $status</td></tr>";
                }
            }
        ?>
    </table>
    </form>
    </section>
    <p><span class='NS'>NS</span> - Not Started | <span class='IP'>IP</span> - In Progress | <span class='C'>C</span> - Complete</p>
</body>
</html>