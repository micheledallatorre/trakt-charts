<?php
/**
 * A simple class for accessing the Trakt API.  You can use it like so:
 *
 *  $trakt = new Trakt("You API Key");
 *  $trakt->showSeasons("The Walking Dead", true);
 *
 * You can view the list of available API methods here: http://trakt.tv/api-docs
 * To call a method, such as "search/movies", the ``Trakt`` class will respond
 * to the corresponding method name "searchMovies".  So, in the above example, the
 * following would work:
 *
 *  $trakt->searchMovies("28 Days Later");
 *
 * To call any methods that require authentication, you must first set the
 * authentication data:
 *
 *    $trakt->setAuth("username", "password");
 *
 *
 * Now the following will work:
 *
 *    $trakt->activityFriends();
 *
 *
 * POST requests are also supported and behave in much the same way as GET requests,
 * except that they accept a single argument which should be an array that matches the
 * signature as described in the API docs.  For example, to test your login credentials,
 * you can do:
 *
 *    $trakt->accountTest(array("username"=>"myusername", "password" => "mypassword"));
 *
 */


/**
 * Generate and return a slug for a given ``$phrase``.
 */
function slugify($phrase)
{
    $result = strtolower($phrase);
    $result = preg_replace("/[^a-z0-9\s-]/", "", $result);
    $result = trim(preg_replace("/[\s-]+/", " ", $result));
    $result = preg_replace("/\s/", "-", $result);
    
    return $result;
}


class Trakt
{
    public  $errUrl = '';
    public  $errNum = 0;
    public  $errMsg = '';
    
    public  $trackHost = "https://api.trakt.tv";
    
    private $urls = array(
        /**
         * Account methods
         */
        "/account/create/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/account/test/" => array(
            array("name" => "json", "method" => "post")
        ),
    
        /**
         * Activity methods
         */
        "/activity/community.json/" => array(
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/episodes.json/" => array(
            array("name" => "titleOrId", "convert" => "slugify"),
            array("name" => "season"),
            array("name" => "episode"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/friends.json/" => array(
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/movies.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/seasons.json/" => array(
            array("name" => "titleOrId", "convert" => "slugify"),
            array("name" => "season"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/shows.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/user.json/" => array(
            array("name" => "username"),
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
		/* added by MDT, TO BE CHECKED */
        "/activity/user/movies.json/" => array(
            array("name" => "username"),
            array("name" => "imdbid",     "optional" => true),
            array("name" => "actions",   "optional" => true)
        ),        
        /**
         * Calendar methods
         */
        "/calendar/premieres.json/" => array(
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        "/calendar/shows.json/" => array(
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        
        /**
         * Friends methods
         */
        "/friends/add/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/friends/all/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/friends/approve/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/friends/delete/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/friends/deny/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/friends/requests/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Genres methods
         */
        "/genres/movies.json/" => null,
        "/genres/shows.json/"  => null,
        
        /**
         * Lists methods
         *    TODO: Add these
         */
        "/lists/add/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/lists/delete/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/lists/items/add/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/lists/items/delete/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/lists/update/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Movie methods
         */
        "/movie/cancelcheckin/"  => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/cancelwatching/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/checkin/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/scrobble/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/seen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/library/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/related.json/" => array(
            array("name" => "titleOrId",   "convert"  => "slugify"),
            array("name" => "hidewatched", "optional" => true)
        ),
        "/movie/shouts.json/" => array(
            array("name" => "titleOrId",   "convert"  => "slugify")
        ),
        "/movie/summary.json/" => array(
            array("name" => "titleOrId",   "convert"  => "slugify")
        ),
        "/movie/unlibrary/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/unseen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/unwatchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/watching/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/movie/watchingnow.json/" => array(
            array("name" => "titleOrId",   "convert"  => "slugify")
        ),
        "/movie/watchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Movies methods
         */
        "/movies/trending.json/" => null,
        
        /**
         * Rate methods
         */
        "/rate/episode/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/rate/movie/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/rate/show/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Recommendations methods
         */
        "/recommendations/movies/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/recommendations/movies/dismiss/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/recommendations/shows/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/recommendations/shows/dismiss/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Search methods
         */

        "/search/episodes.json/" => array(
            array("name"=>"query", "convert" => "urlencode")
        ),
        "/search/movies.json/" => array(
            array("name"=>"query", "convert" => "urlencode")
        ),
        "/search/people.json/" => array(
            array("name"=>"query", "convert" => "urlencode")
        ),
        "/search/shows.json/" => array(
            array("name"=>"query", "convert" => "urlencode")
        ),
        "/search/users.json/" => array(
            array("name"=>"query", "convert" => "urlencode")
        ),

        /**
         * Shout methods
         */
        "/shout/episode/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/shout/movie/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/shout/show/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Show methods
         */
        "/show/cancelcheckin/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/cancelwatching/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/checkin/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/library/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/seen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/shouts.json/" => array(
            array("name" => "titleOrId", "convert" => "slugify"),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/episode/summary.json/" => array(
            array("name" => "titleOrId", "convert" => "slugify"),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/episode/unlibrary/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/unseen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/unwatchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/episode/watchingnow.json/" => array(
            array("name" => "titleOrId", "convert" => "slugify"),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/episode/watchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/library/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/related.json/" => array(
            array("name" => "titleOrId",   "convert"  => "slugify"),
            array("name" => "hidewatched", "optional" => true)
        ),
        "/show/scrobble/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/season.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify"),
            array("name" => "season",    "convert"  => "slugify"),
        ),
        "/show/season/library/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/season/seen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/seasons.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify"),
        ),
        "/show/seen/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/shouts.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify")
        ),
        "/show/summary.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify"),
            array("name" => "extended",  "optional" => true)
        ),
        "/show/unlibrary/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/unwatchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/watching/" => array(
            array("name" => "json", "method" => "post")
        ),
        "/show/watchingnow.json/" => array(
            array("name" => "titleOrId", "convert"  => "slugify")
        ),
        "/show/watchlist/" => array(
            array("name" => "json", "method" => "post")
        ),
        
        /**
         * Shows methods
         */
        "/shows/trending.json/" => null,
        
        /**
         * User methods
         */
        "/user/calendar/shows.json/"     => array(
            array("name" => "username"),
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        "/user/friends.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/all.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/collection.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/hated.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/loved.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/all.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/collection.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/hated.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/loved.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/watched.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/list.json/" => array(
            array("name" => "username"),
            array("name" => "slug", "convert"  => "slugify")
        ),
        "/user/lists.json/" => array(
            array("name" => "username")
        ),
        "/user/profile.json/" => array(
            array("name" => "username")
        ),
        "/user/watching.json/" => array(
            array("name" => "username")
        ),
        "/user/watchlist/episodes.json/" => array(
            array("name" => "username")
        ),
        "/user/watchlist/movies.json/"   => array(
            array("name" => "username")
        ),
		/* MDT added */
		"/user/ratings/movies.json/"   => array(
            array("name" => "username")
        ),
		/* MDT added */
		"/user/ratings/shows.json/"   => array(
            array("name" => "username")
        ),
        "/user/watchlist/shows.json/"    => array(
            array("name" => "username")
        )
    );
    
    function Trakt($apiKey, $debug=false)
    {
        $this->apiKey = $apiKey;
        $this->debug = $debug;
        $this->clearAuth();
    }
    
    public function __call($method, $arguments)
    {
        $methodUrl = $this->getMethodUrl($method);
        if (!array_key_exists($methodUrl, $this->urls)) {
            // Try post instead
            $methodUrl = $this->getMethodUrl($method, "");
        }
        
        if (array_key_exists($methodUrl, $this->urls)) {
            $url = $this->buildUrl($methodUrl);
            $post = null;
            
            foreach($arguments as $index => $arg) {            
                if (array_key_exists($index, $this->urls[$methodUrl])) {
                    $opts = $this->urls[$methodUrl][$index];
                    
                    if (array_key_exists("method", $opts) && $opts["method"] == "post") {
                        $post = $arg;
                        break;
                    }
                    
                    // Determine how to represent this field
                    $data = $arg;
                    if (array_key_exists("convert", $opts)) {
                        $data = $opts["convert"]($arg);
                    } else if (array_key_exists("optional", $opts) && $arg === true) {
                        $data = $opts["name"];
                    }
                    
                    $url .= $data."/";
                }
            }
            $url = rtrim($url, "/");
            
            if ($this->debug) {
                printf("URL: %s\n", $url);
            }
            
            return $this->getUrl($url, $post);
        }
        return false;
    }
    
    public function clearAuth()
    {
        $this->username = null;
        $this->password = null;
    }
    
    /**
     * Sets authentication for all subsequent API calls.  If ``$isHash``
     * is ``true``, then the ``$password`` is expected to be a valid
     * sha1 hash of the real password.
     */
    public function setAuth($username, $password, $isHash=false)
    {
        $this->username = $username;
        $this->password = $password;
        
        if (!$isHash) {
            $this->password = sha1($password);
        }
    }
    
    /**
     * Given a string like "showSeason", returns "/show/season.json/"
     */
    private function getMethodUrl($method, $format=".json") {
        $method[0] = strtolower($method[0]);
        $func = create_function('$c', 'return "/" . strtolower($c[1]);');
        return "/".preg_replace_callback('/([A-Z])/', $func, $method).$format."/";
    }
    
    /**
     * Builds and returns the URL for the given ``$method``.  This method
     * basically just adds in the API Key.
     */
    private function buildUrl($methodUrl)
    {
        return $this->trackHost.$methodUrl.$this->apiKey."/";
    }
    
    /**
     * Query the ``$url`` and convert the JSON into an associative array.
     * If error are encountered, ``false`` is returned instead.
     */
    private function getUrl($url, $post=null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_FAILONERROR, false); //trakt sends a 401 with 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if ($this->username && $this->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        }
        
        if ($post) {
            $data = json_encode($post);
            if ($this->debug) {
                var_dump($data);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $buffer = curl_exec($ch);
        
        $this->errUrl = $url;
        $this->errNum = curl_errno($ch);
        $this->errMsg = curl_error($ch);
        
        curl_close($ch);
        
        //check for errors connecting to site
        if ($this->errNum && $this->errNum != 0)
        {
            return false;
        }
        else
        {
            //check for errors is the returned data
            $decoded = json_decode($buffer, true);
            if (is_object($decoded) && $decoded->status == 'failure')
            {
                $this->errMsg = $decoded->error;
                return false;
            }
            elseif (!is_array($decoded))
            {
                $this->errMsg = 'Nothing returned';
                return false;
            }
            return $decoded;
        }
    }
}

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


$result = $myratings;
// for each rating 
for ($i=0; $i<count($myratings); $i++) {
	// read tmdb_id which is UNIQUE
	$myrating_tmdbID = $myratings[$i]['tmdb_id'];
	// for each seen movie
	for ($j=0; $j<count($mymovies); $j++) {
		// get tmdb_id for seen movies
		$mymovies_tmdbID = $mymovies[$j]['tmdb_id'];
		// since arrays are ordered, check only if one ID is less than the other ID
		if ($myrating_tmdbID <= $mymovies_tmdbID) {
			// if they are the same, add fields to the merged array
			if ($myrating_tmdbID == $mymovies_tmdbID) {
				// get the $mymovies current array element (it's an array!) 
				$this_movie = $mymovies[$j];
				
				//get the IMDB ID of the current movie
				$this_movie_imdbid = $mymovies[$j]['imdb_id']; 
				//call the SEEN activity API to get the last seen timestamp for the movie
				$myUserActivity = $trakt->activityUserMovies($myuser, $this_movie_imdbid, "seen");
				// search for the START timestamp with RegExp (a number (\d+) after START)
				$pattern = '/timestamps\":{\"start\":(\d+),.*/';  
				preg_match($pattern, json_encode($myUserActivity), $matched_array);
				//get the timestamp
				$first_seen_timestamp = $matched_array[1];
				//save it into result array both as timestamp
				$result[$i]['firstseen_timestamp'] = $first_seen_timestamp; 
				// and as date
				$result[$i]['firstseen_date'] = date('Y-m-d H:i:s', $first_seen_timestamp); 
				
				// add all its fields to my final merged array
				foreach ($this_movie as $k=>$v) {
					$result[$i][$k] = $v; 
				}
			}
		}
	}
	
}
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
			
			for ($i=0; $i<count($result); $i++) {
				$myrating = $result[$i]['rating_advanced'];
				$myyear = $result[$i]['year']; 
				
				// COMMENTED!!!
				// timestamp of when the movie was rated, not seen!
				// $mytimestamp =$result[$i]['inserted'];
				
				// timestamp from the SEEN activity, i.e. first time the movie was seen
				$mytimestamp = $result[$i]['firstseen_timestamp'];
				$mymonth = date("M", $mytimestamp);
				$myday = date("D", $mytimestamp);
				$myhour = date("H", $mytimestamp);
				$myseenyear = date("Y", $mytimestamp);
				$mygenres = $result[$i]['genres'];
				
				foreach ($mygenres as $k => $v) {
					// discard empty genres!
					if ($v != "") {
						$movies_per_genre[$v] += 1;
						$sum_rating_per_genre[$v] += $myrating;
					}
				}				
				$movies_per_rating[$myrating] += 1;
				$movies_years[$myyear] += 1;
				$movies_per_month[$mymonth] += 1;
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
					$mygenrating = $v2/$v1;
					$avg_rating_per_genre[$k1] = $mygenrating;
					}
				}
			}

			 
  
			//echo (show_php($movies_per_genre));
			
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
				'width' => 500, 
				'height' => 400,
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
				'width' => 500, 
				'height' => 400,
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
				'width' => 500, 
				'height' => 400,
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
				'width' => 500, 
				'height' => 400,
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
		<h2>Movies seen but not rated:</h2>
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
			echo (show_php($notseen));
		?>
		</div>		
		<? echo $content; ?>	
	</body>
</html>

