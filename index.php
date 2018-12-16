<?php
 
 function Zip($source, $destination="website_backups", $database_dir = "databases-backups")
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

     if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }

    $zip_name = "website-bk-up-".date("Y-m-d-H-i-s").".zip"; 
    $new = getcwd().DIRECTORY_SEPARATOR.$destination.DIRECTORY_SEPARATOR.$zip_name;

    $zip = new ZipArchive();
    if (!$zip->open($new, ZIPARCHIVE::CREATE)) {
        return false;
    }

    #$source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        echo "Archiving files";
        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
        echo "Done archiving files";
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    #echo "Moving archived files";
    $old = getcwd().DIRECTORY_SEPARATOR.$zip_name;
    $new = getcwd().DIRECTORY_SEPARATOR.$destination.DIRECTORY_SEPARATOR.$zip_name;
   


    return $zip->close();
}




 /**
  * @function    backupDatabaseTables
  * @author      CodexWorld
  * @link        http://www.codexworld.com
  * @usage       Backup database tables and save in SQL file
  */
 function backupDatabaseTables($dbHost,$dbUsername,$dbPassword,$dbName, $database_dir , $tables = '*'){

    
    if (!file_exists($database_dir)) {
        mkdir($database_dir, 0777, true);

    }
     //connect & select the database
     $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName); 
 
     //get all of the tables
     if($tables == '*'){
         $tables = array();
         $result = $db->query("SHOW TABLES");
         while($row = $result->fetch_row()){
             $tables[] = $row[0];
         }
     }else{
         $tables = is_array($tables)?$tables:explode(',',$tables);
     }
     $return = "";
     //loop through the tables
     foreach($tables as $table){
         $result = $db->query("SELECT * FROM $table");
         $numColumns = $result->field_count;
 
         $return .= "DROP TABLE $table;";
 
         $result2 = $db->query("SHOW CREATE TABLE $table");
         $row2 = $result2->fetch_row();
 
         $return .= "\n\n".$row2[1].";\n\n";
 
         for($i = 0; $i < $numColumns; $i++){
             while($row = $result->fetch_row()){
                 $return .= "INSERT INTO $table VALUES(";
                 for($j=0; $j < $numColumns; $j++){
                     $row[$j] = addslashes($row[$j]);
                     @$row[$j] = preg_replace("\n","\\n",$row[$j]);
                     if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                     if ($j < ($numColumns-1)) { $return.= ','; }
                 }
                 $return .= ");\n";
             }
         }
 
         $return .= "\n\n\n";
     }
     
     $db_sql_name = 'db-backup-'.date("Y-m-d-H-i-s").'.sql';
     $db_file = getcwd().DIRECTORY_SEPARATOR.$database_dir.DIRECTORY_SEPARATOR.$db_sql_name;

     //save file
     $handle = fopen($db_file,'w+');
     fwrite($handle,$return);
     fclose($handle);
 }



 function backupSystem(){

        
    backupDatabaseTables($dbHost='localhost',$dbUsername='root',$dbPassword='',$dbName='plugin',$database_dir = "databases-backups", $tables = '*');
    Zip(getcwd());

    $msg = "Relax!\nYour website was successfully backed up";

    // use wordwrap() if lines are longer than 70 characters
    $msg = wordwrap($msg,70);

    // send email
    mail("nwachukwu16@gmail.com","Backup Notification",$msg);
 }

 backupSystem();