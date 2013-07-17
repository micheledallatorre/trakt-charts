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
   
$mymovies = $trakt->userLibraryMoviesAll($myuser);  
$myratings = $trakt->userRatingsMovies($myuser);
array_sort_by_column($mymovies, 'tmdb_id');
//echo (tableArray($mymovies));
//echo (show_php($mymovies));
 
array_sort_by_column($myratings, 'tmdb_id');
//echo (tableArray($myratings));  

//get all IMDB IDs of my seen movies in library
$allSeenMoviesIMDBIDs = array();
foreach ($mymovies as $elem) {
	// insert IMDB ID value into allMoviesIMDBIDs array
	if (isset($elem['imdb_id'])) {
		array_push($allSeenMoviesIMDBIDs, $elem['imdb_id']);
	}  
}


$result = array();
for ($i=0; $i<count($myratings); $i++) {

	if (isset($myratings[$i]['tmdb_id'])) {
		$my_tmdbID = $myratings[$i]['tmdb_id'];
	}
	foreach ($myratings[$i] as $k=>$v) {
		$result[$my_tmdbID][$k] = $v;
	}
	
}

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
  


//separator is comma
$sep = ",";
// limit for IMDB IDs per API call 
$url_limit = 100; 
$arraysize = count($allSeenMoviesIMDBIDs);

$decoded = array();
//call the SEEN activity API to get the last seen timestamp for the movie 
// and save it into result array as first seen timestamp and as first seen date
for($i=0;$i<$arraysize;$i++)	{ 
	$imdbid_csv_list = "";
	for ($j=0; $j<$url_limit && $url_limit < $arraysize; $j++) { 
			//get the IMDB ID of the current movie and increment counter
			$myimdbid = $allSeenMoviesIMDBIDs[$i++];
			$imdbid_csv_list = $imdbid_csv_list . $sep . $myimdbid; 
			$myUserActivity = $trakt->activityUserMovies($myuser, $imdbid_csv_list, "seen?min=1");		
			//if (isset($myUserActivity['activity'])) {
				array_push($decoded, $myUserActivity['activity']);
			//}
		}
}					
//echo(show_php($imdbid_csv_list));  
//echo(show_php($decoded));  

foreach ($decoded as $elem) {
	//get the ACTIVITY array, not the TIMESTAMP array
	// e. g.
	/*
	array (
  'timestamps' => 
  array (
    'start' => 1247954400,
    'end' => 1374046256,
    'current' => 1374046256,
  ), 
  'activity' => 
  array (
    0 => 
    array (
      'timestamp' => 1295132400,
      'type' => 'movie',
      'action' => 'seen',
      'user' => 
      array (
        'username' => 'mdt',
        'protected' => false,
      ),
      'movie' => 
      array (
        'title' => 'The Social Network',
        'year' => 2010,
        'imdb_id' => 'tt1285016',
        'tmdb_id' => 37799,
      ),
    ),
    1 => 
    array (
      'timestamp' => 1263510000,
      'type' => 'movie',
      'action' => 'seen',
      'user' => 
      array (
        'username' => 'mdt',
        'protected' => false,
      ),
      'movie' => 
      array (
        'title' => 'Up',
        'year' => 2009,
        'imdb_id' => 'tt1049413',
        'tmdb_id' => 14160,
      ),
    ),
	...
	*/
	//if (isset($elem['activity'])) {
		foreach ($elem['activity'] as $myactivity) {
			//get the timestamp 
			if (isset($myactivity['timestamp'])) {
				$firstseen_timestamp = $myactivity['timestamp'];
				$firstseen_date = date('Y-m-d H:i:s', $firstseen_timestamp);
				$mytmdb_id = $myactivity['tmdb_id'];
				//save it into result array both as timestamp
				$result[$mytmdb_id]['firstseen_timestamp'] = $firstseen_timestamp; 
				// and as date
				$result[$mytmdb_id]['firstseen_date'] = $firstseen_date; 		
			}
		}
	//}
}




//sort by FIRST SEEN timestamp so that all resulting arrays are ordered
// used in chart MOVIES SEEN PER MONTH (June 2010=>5, May 2011=>2, etc.)
array_sort_by_column($result, 'firstseen_timestamp');				
							
				

$content = tableArray($result);
//echo (show_php($result));
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
		
		<!-- including https://code.google.com/p/php-class-for-google-chart-tools/ -->
		<?php		
			include('Chart.php');

			// OUTPUT: 
			/*$data = array(
					'cols' => array(
							array('id' => 'myratings', 'label' => 'Rating', 'type' => 'string'),
							array('id' => 'number of movies', 'label' => '# of movies', 'type' => 'number')
					),
					'rows' => array(
							array('c' => array(array('v' => 'rating 4'), array('v' => 5))),
							array('c' => array(array('v' => 'rating 5'), array('v' => 7))),
							array('c' => array(array('v' => 'rating 7'), array('v' => 1))),
							array('c' => array(array('v' => 'rating 9'), array('v' => 3)))
					)
			); 
						$chart->load(json_encode($data));*/

			/* create data array for graph movies/ratings */
			$movies_per_rating = array();
			$movies_years = array();
			$movies_per_month = array();
			$movies_per_day = array();
			$movies_per_hour = array();
			$movies_per_genre = array();
			$avg_rating_per_genre = array();
			$movies_per_seen_year = array();
			
			foreach ($result as $k=>$v) {
				$myrating = $result[$k]['rating_advanced'];
				$myyear = $result[$k]['year']; 
				
				// COMMENTED!!!
				// timestamp of when the movie was rated, not seen!
				// $mytimestamp =$result[$k]['inserted'];
				
				// timestamp from the SEEN activity, i.e. first time the movie was seen
				$mytimestamp = $result[$k]['firstseen_timestamp'];
				$mymonth = date("M", $mytimestamp);
				$myday = date("D", $mytimestamp);
				$myhour = date("H", $mytimestamp);
				$myseenyear = date("Y", $mytimestamp);
				$mygenres = $result[$k]['genres'];
				
				foreach ($mygenres as $k => $v) {
					// discard empty genres!
					if ($v != "") {
						$movies_per_genre[$v] += 1;
						$sum_rating_per_genre[$v] += $myrating;
					}
				}				
				$movies_per_rating[$myrating] += 1;
				$movies_years[$myyear] += 1;
				$movies_per_month[$mymonth . ' ' . $myseenyear] += 1;
				$movies_per_day[$myday] += 1;
				$movies_per_hour[$myhour] += 1;
				$movies_per_seen_year[$myseenyear] += 1;
			}
			//normalize sum of rating per genre to get avg rating
			foreach ($movies_per_genre as $k1 => $v1) {
				foreach ($sum_rating_per_genre as $k2 => $v2) {
					//check the genre is the same
					if ($k1 == $k2) {
					// get avg rating per genre
					// round to 1 decimal only, e.g. 
					// avg rating = 6,7
					$mygenrating = round($v2/$v1, 1);
					$avg_rating_per_genre[$k1] = $mygenrating;
					}
				}
			} 
			
			
   
			$weekdays = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
			// sort like
			// Mon, Tue, Wed, ..., Sat, Sun
			uksort($movies_per_day, function($a, $b) use ($weekdays) {return array_search($a, $weekdays) - array_search($b, $weekdays);});

			
			
			/****************** FUNCTION createGraphData *************************/
			/* creates array for graph, such as
				$data = array(
					array('Rating', '# of movies'),
					array('Rate: 6', 8),
					array('Rate: 7', 12)
				);
			*/
			/* INPUT: data label xaxis, data label yaxis, array with data to be printed, e.g.
				[array]
					"6"=>[integer]=[4]
					"7"=>[integer]=[3]
					"8"=>[integer]=[3]
					"10"=>[integer]=[5]
			*/
			function createGraphData ($label1, $label2, $mydata) {
				// return array
				$res = array();
				// create array of labels
				$labels = array($label1, $label2);
				// add labels array as first row of res array
				$res[0] = $labels;
				$count = 1;
				// for each x-y value in my data array
				foreach ($mydata as $k => $v) {
					// create array
					$row = array();
					// add key and value as a couple of values to be printed
					$row[0] = $k;
					$row[1] = $v;
					// add values, as array, to the res array from row1 (row0 is labels array!)
					$res[$count++] = $row;
				}
				return $res;
			}		

			$chart = new Chart('ColumnChart');			
			$data = createGraphData('ratings','# of movies', $movies_per_rating);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Rating distribution', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Ratings'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 500, 
				'height' => 400,
				'colors' => array('red')
				);
			echo $chart->draw('chart_movies_per_rating', $options);
			
			
			
			/********* graph 2******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('year','# of movies', $movies_years);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Year of production for movies', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Year'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('blue')
				);
			echo $chart->draw('chart_movies_years', $options);
			
			
			/********* graph 3******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('month','# of movies', $movies_per_month);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Movies seen per month', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Month'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_movies_per_month', $options);		
			
			/********* graph 4******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('day','# of movies', $movies_per_day);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Movies seen per day', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Day'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 500, 
				'height' => 400,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_movies_per_day', $options);	
			
			/********* graph 5******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('hour','# of movies', $movies_per_hour);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Movies seen per hour', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Hour'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 500, 
				'height' => 400,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_movies_per_hour', $options);	
			
			/********* graph 8******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('hour','# of movies', $movies_per_seen_year);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Movies seen in year', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Year'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 500, 
				'height' => 400,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_movies_per_seen_year', $options);	
			
			
			/********* graph 6******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('hour','# of movies', $movies_per_genre);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Most rated genres', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Genre'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_movies_per_genre', $options);	
			
			
			/********* graph 7******************/
			$chart = new Chart('ColumnChart');			
			$data = createGraphData('genre','avg rating', $avg_rating_per_genre);
			$chart->load($data, 'array');
			$options = array(
				'title' => 'Average rating per genre', 
				'vAxis' => array('title' => 'Average rating', 'minValue' => 0),
				'hAxis' => array('title' => 'Genre'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('purple')
				);
			echo $chart->draw('chart_avg_rating_per_genre', $options);	
		?>
		
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
		<? echo $content; ?>	
	</body>
</html>

