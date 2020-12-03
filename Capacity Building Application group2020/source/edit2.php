<?php
session_start();

//Connect to the database
require_once 'mysqli_connect.php';

//Initialize variables for populating the page with responses
$npName = $_SESSION['nonprofit'];
$surveyFile = $npName . ".csv";
$lineNum = 1;

//Get survey responses
if ($handle = opendir('surveys/')) {
    while (false !== ($file = readdir($handle))) {
        if ('.' === $file) continue;
        if ('..' === $file) continue;
        //Do something with file
        $fn = fopen("surveys/$file","r");
        //Iterate through every line
        while(! feof($fn))  {
          $line = str_replace('"', "", fgets($fn));
          $list = preg_split ("/\,/", $line);
          if ($lineNum == 3) {
            $emailAdd = $list[11];
            $fname = $list[9];
            $lname = $list[10];
            //Parse through responses
            $responseNum = 1;
            for ($i =13; $i<count($list); $i++) {
                $wordsInLine = explode(' ', $list[$i]);
                $lastWord = array_pop($wordsInLine);
                $secondToLastWord = array_pop($wordsInLine);
                //Add a space after Build, Thrive, and Sustain
                $lineChars = str_split($list[$i]);
                $word ="";
                for ($x=0; $x<7; $x++) {
                    $word = $word.$lineChars[$x];
                    if ($word == "Build") {
                        array_splice($lineChars, $x+1, 0, array("— "));
                        $list[$i] = implode($lineChars);
                    }
                    elseif ($word == "Thrive") {
                        array_splice($lineChars, $x+1, 0, array("— "));
                        $list[$i] = implode($lineChars);
                    }
                    elseif ($word == "Sustain") {
                        array_splice($lineChars, $x+1, 0, array("— "));
                        $list[$i] = implode($lineChars);
                    }
                }
                //Concatinate responses with commas in them
                if ($secondToLastWord.$lastWord == "majorgiving") {
                    ${"response" . $responseNum} = $list[$i].$list[$i+1];
                    $i = $i+1;
                }
                elseif ($secondToLastWord.$lastWord == "offiling") {
                    ${"response" . $responseNum} = $list[$i].$list[$i+1].$list[$i+2];
                    $i=$i+2;
                }
                elseif ($lastWord == "sharing" || $lastWord == "time" || $lastWord == "policies" || $lastWord == "equity" || $lastWord == "bookkeeping" || $lastWord == "identify" || $lastWord == "giving" || $lastWord == "plan" || $lastWord == "compliance" || $lastWord == "strategies" || $lastWord == "members") {
                    ${"response" . $responseNum} = $list[$i].$list[$i+1].$list[$i+2];
                    $i=$i+2;
                }
                elseif($lastWord == "remain") {
                    ${"response" . $responseNum} = $list[$i].$list[$i+1];
                    $i = $i+1;
                }
                elseif($lastWord == "active" || $lastWord == "reports" || $lastWord == "filing") {
                    ${"response" . $responseNum} = $list[$i].$list[$i+1].$list[$i+2]. $list[$i+3]. $list[$i+4];
                    $i = $i+4;
                }
                else {
                    ${"response" . $responseNum} = $list[$i];
                }
                $responseNum++;
            }
          }
          $lineNum = $lineNum+1;
        }
        fclose($fn);
    }
}

//Initialize variable for populating the page with recommendations
$rGroup = "";

//Iterate 12 times, retrieving recommendations for each principle
for ($i=1; $i<=12; $i++) {
  //SQL code for retrieving all of this principle's recs
  $sql = "SELECT * FROM Recommendations WHERE Nonprofit='$npName' AND Number=$i";
  $allRecs = mysqli_query($conn, $sql);

  //Fetch the results as an associative array
  mysqli_fetch_all($allRecs, MYSQLI_ASSOC);

  //Iterate through the array, adding recs to rGroup
  foreach($allRecs as $rec){
    if($rec['Recs'] !== 'N/A'){
      $rGroup = $rGroup."<strong>". $rec['User']. ": </strong>" . $rec['Recs'] . "<span class='date'> (" . $rec['Date'] . ")</span><br>";
    }
  }
  ${"rec".$i} = $rGroup;
  $rGroup = '';
}

//When save is clicked
if(isset($_POST['save'])) {
  //Display error if a name has not been entered
  if (empty($_POST['name'])) {
    $_SESSION['show'] ="**Please type your name in the box to save your recommendations";
  }
  //If a name has been entered
  else {
    $_SESSION['show'] = '';
    $name =$_POST['name'];

    for ($i = 1; $i <= 12; $i++) {
      $select = 'r'.$i;

      //Initialize rec variable if nothing was entered
      if (!isset(${"rec".$i})) {
        ${"rec".$i} = '';
      }

      //initialize rec variables if something was entered
      if (!empty($_POST[$select] && $_POST[$select] !== ' ')) {
        $date = date("Y-m-d");
        ${"rec".$i} = ${"rec".$i} . "<strong> $name: </strong>". $_POST[$select] . "<span class='date'> (" . $date . ")</span><br>";
      }
    }

    //Save new recommendations to the database
    if(isset($name)) {
      for ($i = 1; $i <= 12; $i++){
        $select = 'r'.$i;
        if(!empty($_POST[$select]) && $_POST[$select] !== ' ') {
          $sql = "INSERT INTO `Recommendations`(`Nonprofit`, `User`, `Number`, `Recs`) VALUES (?,?,?,?)";
          $statement = mysqli_prepare($conn,$sql);
          //bind parameters: i - integer, d - double, s - string, b - blob
          //one letter for each ? in the query string
          mysqli_stmt_bind_param($statement,'ssis',$npName,$name,$i,$_POST[$select]);
          mysqli_stmt_execute($statement);
          mysqli_stmt_store_result($statement);
          mysqli_stmt_fetch($statement);
        }
      }
      if(isset($_POST['status'])){
        $status = $_POST['status'];
        $sql = "SELECT `Status` FROM `Recommendations` WHERE Nonprofit='$npName' AND Number = 0";
        $statement = mysqli_prepare($conn,$sql);
        mysqli_stmt_execute($statement);
        mysqli_stmt_store_result($statement);
        mysqli_stmt_fetch($statement);

        if(mysqli_stmt_num_rows($statement)!==0) {//It found the status in the DB
          $sql = "UPDATE `Recommendations` SET Status='$status' WHERE Nonprofit='$npName' AND Number = 0";
          $statement = mysqli_prepare($conn,$sql);
          mysqli_stmt_execute($statement);
          mysqli_stmt_store_result($statement);
          mysqli_stmt_fetch($statement);
    	  }
        else {
          $i = 0;
          $name = 'admin';
          $sql = "INSERT INTO `Recommendations`(`Nonprofit`, `User`, `Number`, `Status`) VALUES (?,?,?,?)";
          $statement = mysqli_prepare($conn,$sql);
          //bind parameters: i - integer, d - double, s - string, b - blob
          //one letter for each ? in the query string
          mysqli_stmt_bind_param($statement,'ssis',$npName,$name,$i,$status);
          mysqli_stmt_execute($statement);
          mysqli_stmt_store_result($statement);
          mysqli_stmt_fetch($statement);
        }
      }
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles/edit2.css">
    <link href='https://fonts.googleapis.com/css?family=Lato' rel='stylesheet'>
</head>
<body>
    <img src="images/nccnpLogo.svg" alt="NCCNP Logo" id="nccnp"/>
    <div id="buttons">
    <form method='post' action='edit2.php' enctype="multipart/form-data">
    <input type="text" placeholder="*NCCNP Employee's Name" id="name" name = "name">
    <select id="status" name="status">
      <option value="" disabled selected>Select this survey's status</option>
      <option value="NS">Not Started</option>
      <option value="IP">In Progress</option>
      <option value="C">Complete</option>
    <input type='submit' onclick='save();' value='Save' name='save' id='save'>
    <div id="export" onClick="window.print()"><p id="exportText">Export</p></div>
    <a href="index.php" id="back">← Back to Surveys  </a>
    <a href="goals2.php" id="goalArrow">Continue to Goals →</a>
    </div> <!-- end buttons div -->
    <?php
        $show = $_SESSION['show'];
        echo "<h3 id = 'warn'>$show</h3> <br> <br>";
        echo "<h2 id='npName'>$npName </h2>";
        echo "<p id='npData'> Survey filled by: $fname $lname <br> $emailAdd </p>"
    ?>
    <section id="workspace">
        <table id="all">
            <tr>
                <td class="survey">
                    <h3>1. Advocacy and Civil Engagement</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"><?= "$response1"?> </p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response2"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class="recommendations">
                        <?php
                        if (isset($rec1)) {
                            echo "<p>$rec1 </p>";
                        }
                         ?>
                    </div>
                    <textarea name = 'r1' placeholder="Type your recommendation here..." class="type" autofocus> <?php if(isset($r1)) echo "$r1";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>2. Board Governance</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response3"?> </p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response4"?> </p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec2)) {
                            echo "<p>$rec2 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r2' placeholder="Type your recommendation here..." class="type"><?php if(isset($r2)) echo "$r2";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>3. Equity, Diversity, and Inclusion</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response5"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response6"?> </p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec3)) {
                        echo "<p>$rec3 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r3' placeholder="Type your recommendation here..." class="type"><?php if(isset($r3)) echo "$r3";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>4. Financial Management</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response7"?> </p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response8"?> </p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec4)) {
                        echo "<p>$rec4 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r4' placeholder="Type your recommendation here..." class="type"><?php if(isset($r4)) echo "$r4";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>5. Fundraising</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response9"?> </p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a">  <?= "$response10"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec5)) {
                            echo "<p>$rec5 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r5' placeholder="Type your recommendation here..." class="type"><?php if(isset($r5)) echo "$r5";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>6. Human Resources</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response11"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response12"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec6)) {
                            echo "<p>$rec6 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r6' placeholder="Type your recommendation here..." class="type"><?php if(isset($r6)) echo "$r6";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>7. Information and Technology</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response13"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response14"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec7)) {
                            echo "<p>$rec7 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r7' placeholder="Type your recommendation here..." class="type"><?php if(isset($r7)) echo "$r7";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>8. Legal Compliance and Transparency</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response15"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response16"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec8)) {
                            echo "<p>$rec8 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r8' placeholder="Type your recommendation here..." class="type"><?php if(isset($r8)) echo "$r8";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>9. Partnerships and Collaboration</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response17"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response18"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec9)) {
                            echo "<p>$rec9 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r9' placeholder="Type your recommendation here..." class="type"><?php if(isset($r9)) echo "$r9";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>10. Program Design, Management, and Evaluation</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response19"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response20"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec10)) {
                            echo "<p>$rec10 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r10' placeholder="Type your recommendation here..." class="type"><?php if(isset($r10)) echo "$r10";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>11. Strategic Communication</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response21"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response22"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec11)) {
                            echo "<p>$rec11 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r11' placeholder="Type your recommendation here..." class="type"><?php if(isset($r11)) echo "$r11";?></textarea>
                </td>
            </tr>
            <tr>
                <td class="survey">
                    <h3>12. Strategic Planning</h3>
                    <p class="q">What is the current condition of your nonprofit?</p>
                    <p class="a"> <?= "$response23"?></p>
                    <p class="q">In what condition would you like your nonprofit to be a year from now?</p>
                    <p class="a"> <?= "$response24"?></p>
                </td>
                <td class="response">
                    <h3>NCCNP Recommendation</h3>
                    <div class ="recommendations">
                        <?php
                        if (isset($rec12)) {
                            echo "<p>$rec12 </p>";
                        }
                        ?>
                    </div>
                    <textarea name = 'r12' placeholder="Type your recommendation here..." class="type"><?php if(isset($r12)) echo "$r12";?></textarea>
                </td>
            </tr>
        </table>
        </form>
    </section>
</body>
</html>
