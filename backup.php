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

    return $zip->close();
}
 

 function EXPORT_DATABASE($host,$user,$pass,$name,$tables=false, $backup_name=false, $database_dir = "databases-backups")
{ 
    set_time_limit(3000); 

    if (!file_exists($database_dir)) {
        mkdir($database_dir, 0777, true);
    }

    $mysqli = new mysqli($host,$user,$pass,$name); 
    $mysqli->select_db($name); 
    $mysqli->query("SET NAMES 'utf8'");
    $queryTables = $mysqli->query('SHOW TABLES');

    while($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
    }

     if($tables !== false) { 
         $target_tables = array_intersect( $target_tables, $tables);
    } 

	$content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `".$name."`\r\n--\r\n\r\n\r\n";
	foreach($target_tables as $table){
		if (empty($table)){
             continue;
        } 

        $result	= $mysqli->query('SELECT * FROM `'.$table.'`');
        $fields_amount=$result->field_count;
        $rows_num=$mysqli->affected_rows;
        $res = $mysqli->query('SHOW CREATE TABLE '.$table);
        $TableMLine=$res->fetch_row(); 
        $content .= "\n\n".$TableMLine[1].";\n\n"; 
        $TableMLine[1]=str_ireplace('CREATE TABLE `','CREATE TABLE IF NOT EXISTS `',$TableMLine[1]);
		for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) {
			while($row = $result->fetch_row())	{ //when started (and every after 100 command cycle):
				if ($st_counter%100 == 0 || $st_counter == 0 )	{$content .= "\nINSERT INTO ".$table." VALUES";}
					$content .= "\n(";    for($j=0; $j<$fields_amount; $j++){ $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); if (isset($row[$j])){$content .= '"'.$row[$j].'"' ;}  else{$content .= '""';}	   if ($j<($fields_amount-1)){$content.= ',';}   }        $content .=")";
				//every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
				if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
                    $content .= ";";
                } else {
                    $content .= ",";
                }	
                $st_counter=$st_counter+1;
			}
		} $content .="\n\n\n";
	}
	$content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
    $backup_name = $backup_name ? $backup_name : $name.'___('.date('H-i-s').'_'.date('d-m-Y').').sql';
    
     $db_file = getcwd().DIRECTORY_SEPARATOR.$database_dir.DIRECTORY_SEPARATOR.$backup_name;

    //save file
    $handle = fopen($db_file,'w+');
    fwrite($handle,$content);
    fclose($handle);
    ob_get_clean(); 
 }

  
 function backupSystem(){

    # backupDatabaseTables($dbHost='localhost',$dbUsername='root',$dbPassword='',$dbName='plugins',$database_dir = "databases-backups", $tables = '*');
    #Zip(getcwd());
    EXPORT_DATABASE($host = 'db760412577.hosting-data.io',$user = 'dbo760412577',$pass = 'eOYeGfetXFXHJfGZqkTv',$name = 'db760412577',$tables=false, $backup_name=false);
    
    $msg = "Relax!\nYour website was successfully backed up";

    // use wordwrap() if lines are longer than 70 characters
    $msg = wordwrap($msg,70);

    // send email
   mail("nwachukwu16@gmail.com","Backup Notification",$msg);
 }

 backupSystem();
