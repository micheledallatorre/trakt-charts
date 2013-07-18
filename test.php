<?php
// include Trakt PHP library
include 'trakt.php';


/* MDT added */
function show_php($var,$indent='&nbsp;&nbsp;',$niv='0')
{
    $str='';
    if(is_array($var))    {
        $str.= "<b>[array][".count($var)."]</b><br />";
        foreach($var as $k=>$v)        {
            for($i=0;$i<$niv;$i++) $str.= $indent;
            $str.= "$indent<em>\"{$k}\"=></em>";
            $str.=show_php($v,$indent,$niv+1);
        }
    }
    else if(is_object($var)) {

        $str.= "<b>[objet]-class=[".get_class($var)."]-method=[";
        $arr = get_class_methods($var);
           foreach ($arr as $method) {
               $str .= "[function $method()]";
           }
        $str.="]-";
        $str.="</b>";
        $str.=show_php(get_object_vars($var),$indent,$niv+1);
    }
    else {
        $str.= "<em>[".gettype($var)."]</em>=[{$var}]<br />";
    }
    return($str);
}


function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}



function tableArray($array) {
	$text = "<table id=\"myTable\" class=\"tablesorter\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n"; 
	for($i=0;$i<count($array);$i++)
	{
		$elem = $array[$i];
		// print th
		if ($i == 0) {
			$text = $text .  "<thead>\n<tr>\n";
			// print first colum
			$text = $text .  "<th>counter</th>\n";
			foreach ($elem as $key => $value) {
				$text = $text .  "<th>". trim($key) . "</th>\n";
			}
			$text = $text .  "</tr>\n</thead>\n<tbody>\n";
		}

		$text = $text .  "<tr>\n";

		$rownumber = $i+1;
		// print row number in first cell
		$text = $text .  "<td>". $rownumber ."</td>\n";
		//print_r($elem);
		foreach ($elem as $key => $value) {
			$text = $text .  "<td>";
			//if it is an array 
			// i.e. images or genres
			if (is_array($value)) {
				$mykey = key($value);
				if (strcmp($key, "images") == 0) {
					$myimages = $value;
					foreach ($myimages as $k1 => $v1) {
						// if poster print image
						if (strcmp($k1, "poster") == 0) {		
							$text = $text .  "<a href=\"" . $v1 . "\">";
							$text = $text .  "<img src=\"" . $v1 . "\" width=\"100px\" height=\"150px\">";
							$text = $text .  "</a>";
						}
						// if fanart
						elseif (strcmp($k1, "fanart") == 0) {
							//do nothing
							/*
							$text = $text .  "<a href=\"" . $v1 . "\">";
							$text = $text .  "<img src=\"" . $v1 . "\" width=\"100px\" height=\"150px\">";
							$text = $text .  "</a>";
							*/
						}						
					}					
				}			
				// if genres
				elseif (strcmp($key, "genres") == 0) {
					$mygenres = $value;
					foreach ($mygenres as $k2 => $v2) {
						$text = $text .  "<a href=\"http://trakt.tv/movies/popular/" . $v2 .  "\">" . $v2 . "</a>";
						$text = $text .  "<br/>";
					}						
				}
			}
			else {
				// if timestamp 
				if (strcmp($key, "inserted") == 0) {					
					$text = $text . date("Y-m-d H:i:s", $value);
				}
				// if url
				elseif (strcmp($key, "url") == 0) {
					$text = $text .  "<a href=\"" . $value . "\">". $value . "</a>";
					}
				// if imdb ID
				elseif (strcmp($key, "imdb_id") == 0) {
					$text = $text .  "<a href=\"http://www.imdb.com/title/" . $value .  "\">" . $value . "</a>";
					}
				// if tmdb ID
				elseif (strcmp($key, "tmdb_id") == 0) {
					$text = $text .  "<a href=\"http://www.themoviedb.org/movie/" . $value .  "\">" . $value . "</a>";
				}
				else
					$text = $text .  $value;
			}
			$text = $text .  "</td>\n";
		}	
		$text = $text .  "</tr>\n";
	}
	$text = $text .  "</tbody>\n";
	$text = $text .  "</table>\n";
	
	return $text;
}

/***** READING CONFIG PARAMETERS ********/
$myconfig = include('config.php');
$myAPIkey = $myconfig['config']['APIkey'];
$myuser = $myconfig['config']['user'];
$mypass = $myconfig['config']['pass'];
/****************************************/

$content = "";
  
  
$trakt = new Trakt($myAPIkey);
// set TRUE if pass is already hash1
$trakt->setAuth($myuser, $mypass, true);

//$tmp = $trakt->showSeasons("The Walking Dead", true);
//$tmp = $trakt->userLastactivity("mdt");
   
// TODO or use userLibraryMoviesWatched???
$mymovies = $trakt->userLibraryMoviesAll($myuser);  
$myratings = $trakt->userRatingsMovies($myuser);
array_sort_by_column($mymovies, 'tmdb_id');
//echo (tableArray($mymovies));
//echo (show_php($mymovies));
 
array_sort_by_column($myratings, 'tmdb_id');
//echo (tableArray($myratings));  

//get all TMDB IDs of my seen movies in library
// IMPORTANT: use TMDB ID, NOT IMDB IDS, because some movies are missing it! (e.g. Field of Vision, 2011)
$allSeenMoviesTMDBIDs = array();
foreach ($mymovies as $elem) {
	// insert IMDB ID value into allMoviesIMDBIDs array
	if (isset($elem['tmdb_id'])) {
		array_push($allSeenMoviesTMDBIDs, $elem['tmdb_id']);
	}  
}

$result= array();
// copy all fields from input array into 
// $result[TMDB ID][inputkey]=>inputvalue PASSED BY REFERENCE NOT VALUE!
function copyIntoResultArray($inputarr, &$res) {
	for ($i=0; $i<count($inputarr); $i++) {
		if (isset($inputarr[$i]['tmdb_id'])) {
			$my_tmdbID = $inputarr[$i]['tmdb_id'];
		}
		foreach ($inputarr[$i] as $k=>$v) {
			$res[$my_tmdbID][$k] = $v;
		}
	}
}

//copy ratings array into result array
copyIntoResultArray($myratings, $result);



// for each rating 
for ($i=0; $i<count($myratings); $i++) {
	// read tmdb_id which is UNIQUE
	$myrating_tmdbID = $myratings[$i]['tmdb_id'];
	// for each seen movie
	for ($j=0; $j<count($mymovies); $j++) {
		// get tmdb_id for seen movies
		$mymovies_tmdbID = $mymovies[$j]['tmdb_id'];
		// if TMDB ID are the same, add fields to the merged array
		if ($myrating_tmdbID == $mymovies_tmdbID) {
			// get the $mymovies current array element (it's an array!) 
			$this_movie = $mymovies[$j];
							
			// add all its fields to my final merged array
			foreach ($this_movie as $k=>$v) {
				$result[$mymovies_tmdbID][$k] = $v; 
			}
		} 
	}	
}
  

//IF there are no ratings, copy MOVIES array into RESULT
// so that I can still show the table with the seen movies
if (count($myratings) == 0) {
	copyIntoResultArray($mymovies, $result);
}


//separator is comma
$sep = ",";
// limit for IMDB IDs per API call 
$url_limit = 100; 
$arraysize = count($allSeenMoviesTMDBIDs);
$tmdbid_csv_list = "";
$decoded = array();
$counter = 1;
// call the SEEN activity API to get the last seen timestamp for the movie 
// and save it into result array as first seen timestamp and as first seen date
for($i=0;$i<$arraysize && $counter<=$url_limit;$i++)	{ 
	//get the IMDB ID of the current movie
	$mytmdbid = $allSeenMoviesTMDBIDs[$i];
	$tmdbid_csv_list = $tmdbid_csv_list . $sep . $mytmdbid; 
	// if reached url limit, or if last iteration on the array 
	// (e.g. last N elements, where N < url_limit)
	if ($counter == $url_limit || ($i==($arraysize-1)) ) {
		// append "seen?min=1" to get minimal info and be FASTER!
		$myUserActivity = $trakt->activityUserMovies($myuser, $tmdbid_csv_list, "seen?min=1");	
		foreach ($myUserActivity['activity'] as $mymovieact) {
			array_push($decoded, $mymovieact);
		}
		//reset counter and imdb csv list
		$counter=1;
		$tmdbid_csv_list = "";
		//sleep a bit, to avoid missing responses from API call!
		//sleep(5);
	}
	//else increment counter
	else $counter++;
}					

/************************
ARRAY $DECODED:
*************************
[array][196]
  "0"=>[array][5]
    "timestamp"=>[integer]=[1372370400]
    "type"=>[string]=[movie]
    "action"=>[string]=[seen]
    "user"=>[array][2]
      "username"=>[string]=[mdt]
      "protected"=>[boolean]=[]
    "movie"=>[array][4]
      "title"=>[string]=[The Break-Up]
      "year"=>[integer]=[2006]
      "imdb_id"=>[string]=[tt0452594]
      "tmdb_id"=>[integer]=[9767]
  "1"=>[array][5]
    "timestamp"=>[integer]=[1365285600]
	...
*/
foreach ($decoded as $elem) {
	//get the timestamp 
	if (isset($elem['timestamp'])) {
		$firstseen_timestamp = $elem['timestamp'];
		$firstseen_date = date('Y-m-d H:i:s', $firstseen_timestamp);
		$mytmdb_id = $elem['movie']['tmdb_id'];
		//save it into result array both as timestamp
		$result[$mytmdb_id]['firstseen_timestamp'] = $firstseen_timestamp; 
		// and as date
		$result[$mytmdb_id]['firstseen_date'] = $firstseen_date; 		
	}
	else 
		echo "TIMESTAMP MISSING FOR MOVIE WITH TMDBID: ".$elem['movie']['tmdb_id'];
}


//sort by FIRST SEEN timestamp so that all resulting arrays are ordered
// used in chart MOVIES SEEN PER MONTH (June 2010=>5, May 2011=>2, etc.)
array_sort_by_column($result, 'firstseen_timestamp');				
							
				
$content = tableArray($result);

?>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="jquery.tablesorter.js" type="text/javascript" charset="utf-8"></script>
		<link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
		
		<script type="text/javascript" charset="utf-8">
							$(document).ready(function() 
							{ 
								$("#myTable").tablesorter(); 
									} 
							); 
		</script>
		
	
		
	</head>
	<body id="index">	
		<div id="chart_movies_per_rating"></div>	
		<div id="chart_movies_years"></div>	
		<div id="chart_movies_per_month"></div>	
		<div id="chart_movies_per_day"></div>	
		<div id="chart_movies_per_hour"></div>	
		<div id="chart_movies_per_seen_year"></div>	
		<div id="chart_movies_per_genre"></div>	
		<div id="chart_avg_rating_per_genre"></div>	
		<div id="pagetitle">
		<? echo "<h1>Rated ". count($myratings) ."/". count($mymovies) ." seen movies</h1>"; ?>

		<? /**** PRINT MOVIES SEEN BUT NOT RATED ****/
			$seenmovies = array();
			for ($j=0;$j<count($mymovies);$j++) {
				$seenmovies[$j] = $mymovies[$j]['title'];
			}

			$movies_seen_rated = array();
			for ($i=0;$i<count($seenmovies);$i++) {
				$rated = false;
				// TODO: if there are 0 rated movies, it fails!
				for ($j=0;$j<count($myratings);$j++) {
					//skip loop, if seen movie has been already found rated
					//if ($rated) break;
					if (strcmp($myratings[$j]['title'], $seenmovies[$i]) == 0) {
						$rated=true;
						$t =  $seenmovies[$i];
						$movies_seen_rated[$t] = $rated;
					}
					elseif ($j == (count($myratings)-1) && $rated==false) {
						$t =  $seenmovies[$i];
						$movies_seen_rated[$t] = $rated;
					}
				}
			}
			$i=0;
			foreach ($movies_seen_rated as $k => $v) {
				if ($v == false)
				$notseen[$i++]= $k;
			}
			if ($notseen != null) {
				echo "<h2>Movies seen but not rated:</h2>";
				echo (show_php($notseen));
			}
		?>
		</div>		
		<? 
		
		session_start();
		$_SESSION['mydata'] = $result; 
		//print link
		echo "<a href=\"mycharts.php\">GRAFICI</\a>";
		
		//print table
		echo $content; 
		
		?>	
	</body>
</html>

