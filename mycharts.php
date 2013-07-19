 	<!-- including https://code.google.com/p/php-class-for-google-chart-tools/ -->
		<?php		




			include('Chart.php');
			session_start();                    
			$result = $_SESSION['mydata'] ;
			//var_dump($result);
			unset($_SESSION['value']);
			loadAndDraw($result);

			//function to reorder array
			function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
				$sort_col = array();
				foreach ($arr as $key=> $row) {
					$sort_col[$key] = $row[$col];
				}

				array_multisort($sort_col, $dir, $arr);
			}


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
			
			function loadAndDraw($result) {
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
			

			
			//reorder by avg rating ASC
			array_sort_by_column($avg_rating_per_genre, 1);
			array_sort_by_column($movies_per_genre, 1);
			
   
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
			
			// function to print Chart
			function printChart($chart_type, $label1, $label2, $mydata, $options, $css_div_id) {
				$chart = new Chart($chart_type);			
				$data = createGraphData($label1,$label2, $mydata);
				$chart->load($data, 'array');
				echo $chart->draw($css_div_id, $options);		
			}
			
			/********* CHART Rating distribution ******************/
			$options = array(
				'title' => 'Rating distribution', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Ratings', 'maxValue' => 10),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 800, 
				'height' => 400,
				'colors' => array('CC0000')
				);
			printChart("ColumnChart", "ratings", "# of movies", $movies_per_rating, $options, "chart_movies_per_rating");
	
			
			
			/********* CHART Year of production for movies******************/
			$options = array(
				'title' => 'Year of production for movies', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Year', 'format' => '####'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 300,
				'colors' => array('660099')
				);
			printChart("ColumnChart", "year", "# of movies", $movies_years, $options, "chart_movies_years");
			
			
			
			/********* CHART Movies seen per month ******************/
			$options = array(
				'title' => 'Movies seen per month', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Month'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 400,
				'colors' => array('485C5A')
				);
			printChart("ColumnChart", "month", "# of movies", $movies_per_month, $options, "chart_movies_per_month");			
			

			/********* CHART Movies seen per hour ******************/
			$options = array(
				'title' => 'Movies seen per hour', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Hour'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 400, 
				'height' => 400,
				'colors' => array('#6599FF')
				);
			printChart("ColumnChart", "hour", "# of movies", $movies_per_hour, $options, "chart_movies_per_hour");
			
			/********* CHART Movies seen per day ******************/
			$options = array(
				'title' => 'Movies seen per day', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Day'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 400, 
				'height' => 400,
				'colors' => array('#3465a4')
				);
			printChart("ColumnChart", "day", "# of movies", $movies_per_day, $options, "chart_movies_per_day");
			
			
			/********* CHART Movies seen per year *****************/		
			$options = array(
				'title' => 'Movies seen per year', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				// use 4 digits to represents years, e.g. "2012", NOT "2,012"
				'hAxis' => array('title' => 'Year', 'format' => '####'),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 400, 
				'height' => 400,
				'colors' => array('#204a87')
				);
			printChart("ColumnChart", "year", "# of movies", $movies_per_seen_year, $options, "chart_movies_per_seen_year");
			
			
			/********* CHART Movies per genre ******************/
			$options = array(
				'title' => 'Movies per genre', 
				'vAxis' => array('title' => '# of movies', 'minValue' => 0),
				'hAxis' => array('title' => 'Genre', 'slantedTextAngle' => 40),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('#8C489F')
				);
			printChart("ColumnChart", "genre", "# of movies", $movies_per_genre, $options, "chart_movies_per_genre");
			
			
			/********* CHART Average rating per genre ******************/
			$options = array(
				'title' => 'Average rating per genre', 
				'vAxis' => array('title' => 'Average rating', 'minValue' => 0),
				'hAxis' => array('title' => 'Genre', 'slantedTextAngle' => 40),
				'legend' => 'none',														
				'is3D' => true, 
				'width' => 1000, 
				'height' => 500,
				'colors' => array('097054')
				);
			printChart("ColumnChart", "genre", "avg rating", $avg_rating_per_genre, $options, "chart_avg_rating_per_genre");
			}
			//}
			
			
		?>
		
		
		<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<style type="text/css">
		
			#content {
			}
				
			#chart_movies_per_rating {				
				margin:auto; 
				width:800px;
				display:block;  
				text-align:center;
			}
			
			#chart_movies_years {
				margin:auto; 
				display:block;
				width:1000px;
			}
			
			#chart_movies_per_month{
				margin:auto; 
				display:block;
				width:1000px;
			}
			
			/*same row*/
			#chart_movies_per_hour {
				display:inline-block;
			}

			#chart_movies_per_seen_year {
				display:inline-block;
			}

			#chart_movies_per_day{
				display:inline-block;
			}
			/*end same row*/
			
			#chart_movies_per_genre{
				margin:auto; 
				display:block;
				width:1000px;
			}
			
			#chart_avg_rating_per_genre{
				margin:auto; 
				display:block;
				width:1000px;
			}			
			.singlerow {
			}
	</style>
	</head>
	<div id="content">

		<div id="chart_movies_per_rating"></div>	
		
		<div class="singlerow">
			<div id="chart_movies_per_hour"></div>	
			<div id="chart_movies_per_day"></div>	
			<div id="chart_movies_per_seen_year"></div>
		</div>
		
		<div id="chart_movies_per_month"></div>	
		
		<div id="chart_movies_per_genre"></div>	
		<div id="chart_avg_rating_per_genre"></div>	
		
		<div id="chart_movies_years"></div>	
		
		</div>
</html>
