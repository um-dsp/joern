<?php
/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://sourceforge.net/projects/simpleauction
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

function rebuild_html_file($table)
{
        switch($table) {
            case "countries" :
			$output_filename = "../includes/countries.inc.php";
                        $field_name = "country";
                        $array_name = "countries";
                        break;
            default :
                        break;
        }

	$sqlqry = "SELECT " . $field_name . " FROM ".$DBPrefix."" . $table . " ORDER BY " . $field_name . ";";
	$result = mysql_query ($sqlqry);

	$output = "<?\n";
	$output.= "$" . $array_name . " = array(\"\", \n";

	if ($result)
		$num_rows = mysql_num_rows($result);
	else
		$num_rows = 0;

	$i = 0;
	while($i < $num_rows){
		$value = mysql_result($result,$i, $field_name);
                $output .= "\"" . $value . "\"";
		$i++;
                if ($i < $num_rows)
			$output .= ",\n";
                else
                        $output .= "\n";
	}

        $output .= ");\n?>\n";

	$handle = fopen ( $output_filename , "w" );
	fputs ( $handle, $output );
	fclose ($handle);
}
?>

