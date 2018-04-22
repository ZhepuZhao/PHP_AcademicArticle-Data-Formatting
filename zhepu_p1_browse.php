<?php
    try {
        // connect database
        $h = "pearl.ils.unc.edu";
        $u = "webdb_zhepu";
        $d = "webdb_zhepu";
        $p = "ZZP2017@unc";
        $dbh = new PDO("mysql:host=$h;dbname=$d",$u,$p);
        $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $dbh->setAttribute( PDO::ATTR_EMULATE_PREPARES, false);
    
        /*
         * Two helper methods below
         * (1) reformatName(): Reformat the author's name for the second advanced feature
         * (2) printOutFormattedAuthors(): Concatenate the authors and print them out
         *
         */
        function reformatName($sub_author, $i, $author, $and_position) {
            if (substr_count($author, ' and ') > 0) {
                if ($and_position == null) {
                    $sub_author[$i] = substr($author, 0);
                } else {
                    $sub_author[$i] = substr($author, 0, $and_position);
                }
            } else {
                $sub_author[$i] = $author;
            }
        
            $first_dot_position = stripos($sub_author[$i], '.', 0);
            $last_dot_position = strripos($sub_author[$i], '.', 0);
            $space_position = stripos($sub_author[$i], ' ', 0);
        
            if ($first_dot_position != false) { // Dot exists in the record
                if ($first_dot_position == 1) {// Start with the initial letter of the first name
                    $sub_author[$i] = substr($sub_author[$i], $last_dot_position+2).', '.
                        substr($sub_author[$i], 0, $last_dot_position + 1);
                    return  $sub_author[$i];
                } else { // Start with the full first name
                    $sub_author[$i] = substr($sub_author[$i], $last_dot_position+2).', '.
                        substr($sub_author[$i],0,1).'.'.
                        substr($sub_author[$i],$last_dot_position - 1, 2);
                    return $sub_author[$i];
                }
            } else { // Dot doesn't exist in the record
                $sub_author[$i] = substr($sub_author[$i], $space_position+1).', '.
                    substr($sub_author[$i],0,1).'.';
                return $sub_author[$i];
            }
        
        }
    
        function printOutFormattedAuthors($sub_author, $url, $title, $publication, $year,$type) {
            $formatted_author = '';
            for ($i = (count($sub_author)-1); $i >=0; $i--){ // start from the last author and concat them
                if (count($sub_author) == 1){
                    $formatted_author = $sub_author[0];
                } else {
                    if ($i == count($sub_author)-1) {
                        $formatted_author = $formatted_author.' and '.$sub_author[$i];
                    } elseif ($i == count($sub_author)-2) {
                        $formatted_author = $sub_author[$i].''.$formatted_author;
                    } else {
                        $formatted_author = $sub_author[$i].', '.$formatted_author;
                    }
                }
            }
            echo "
                <tr>
                    <td>$formatted_author</td>
                    <td><a href='$url'>$title</a></td>
                    <td>$publication</td>
                    <td>$year</td>
                    <td>$type</td>
                </tr>
            ";
        }
        
        // check if 'order' has a value
        if (isset($_GET['order'])){
            $order = $_GET['order'];
        } else{
            $order = 'itemnum';
        }
        
        // Check if the page number has a value
        if (isset($_GET['page_number'])){
            $item_number = ($_GET['page_number'] - 1) * 25;
        } else{
            $item_number = 0;
        }
        
        // Get the total page numbers
        $sql_count = "select * from p1records";
        $count = $dbh->query($sql_count);
        $page_num = ceil(($count->rowCount()) / 25);
        
        // Select the last name of the first author and insert them in the 'firstauthor' field
        $first_author_query = "update p1records set firstauthor = ".
            "(SELECT reverse(substring_index(reverse(SUBSTRING_INDEX(p1records.authors, ' and', 1)), ' ', 1)));";
        $first_author = $dbh->query($first_author_query);
        
        // Select the records from the database
        $sql = "select * from p1records ORDER BY $order LIMIT $item_number, 25";
        $result = $dbh->query($sql);

        // Start a HTML body
        echo "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <title>Zhepu_p1</title>
            <meta charset=\"utf-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
            <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css\">
            <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>
            <script src=\"https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js\"></script>
            <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js\"></script>
        </head>
        <div class=\"container-fluid\">
             <h2>Article Information</h2>
        ";
        
        // Start a table
        echo "
            <table  class=\"table table-striped\">
                <thead>
                    <tr>
                        <th scope=\"col\"><a href='?order=firstauthor'>AUTHOR</a></th>
                        <th scope=\"col\"><a href='?order=title'>TITLE</a></th>
                        <th scope=\"col\"><a href='?order=publication'>PUBLICATION</a></th>
                        <th scope=\"col\"><a href='?order=year'>YEAR</a></th>
                        <th scope=\"col\"><a href='?order=type'>TYPE</a></th>
                    </tr>
                </thead>
      
        ";
        
        // Fetch the records
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            
            // Six field extracted from database
            $author = $row['authors'];
            $title = $row['title'];
            $publication = $row['publication'];
            $year = $row['year'];
            $type = $row['type'];
            $url = $row['url'];
            
            // The number of 'and' in each author record
            $and_number = substr_count($author, ' and ');
    
            // An array to store multiple authors separated from a single author record
            $sub_author = array();
            
            // Reformat the author names
            if ($and_number > 0) { // Multiple authors
                for ($i = 0; $i < ($and_number + 1); $i ++){
                    // Should be declared here otherwise it cannot reformat the last author in a multi-author record
                    $and_position = stripos($author, ' and ', 0);
                    
                    if ($and_position != false){ // Any 'and' in $author still?
                        $sub_author[$i] = reformatName($sub_author, $i, $author, $and_position);
                        $author = substr($author, ($and_position + 5));
                    } else { // Only one author in the original author record left
                        $sub_author[$i] = reformatName($sub_author, $i, $author);
                    }
                }
            } else { // Only one author
                $sub_author[0] = reformatName($sub_author, 0, $author);
            }
    
            // Print out the formatted authors
            printOutFormattedAuthors($sub_author, $url, $title, $publication, $year,$type);
        }

        // End of table
        echo "   
            </table>
        ";
        
        // Pagination added
        echo "<ul class=\"pagination\">";
        for ($i = 0; $i < $page_num; $i++){
            $page = $i + 1;
            if (($item_number / 25 + 1) == $page){
                echo "<li class=\"page-item disabled\" ><a class=\"page-link\" href='#' tabindex=\"-1\">[ $page ]</a></li>";
            } else {
                echo "<li class=\"page-item\"><a class=\"page-link\" href='?order=$order&&page_number=$page'>$page</a></li>";
            }
        }
        echo "</ul>";
        
        // End the HTML body
        echo "
        </div>
        </body>
        </html>
        ";
        
    } catch (PDOException $e) {
        echo $e->getMessage();
    }