#!/usr/bin/ruby
require 'date'
require 'json'
require 'open-uri'

myfile = File.read('lista.csv')	
myfilename = 'lista.csv'
films = {}
mylist = {}
counter = 0

#print  e.g. 
# 127 Hours;2010;60%;19/3/2011;1300489200
def printfile(mtitle, myear, rating, seendate, mytimestamp)
	puts "#{mtitle};#{myear};#{rating};#{seendate};#{mytimestamp}"
end

 File.foreach(myfilename) do |line|
    name,rating,date = line.split(';')
    films[name] = rating, date
  end
  
films.each_pair{ |key,value| 
	title = key.length
	#get movie title without year
	movietitle = key[/(.*[^([0-9]{4})])/].strip! 
	#get movie year
	movieyear = key[/(.*\(([0-9]{4})\))/,2]
	g1 = key[/(.+)\s+\(([0-9]{4})\)/,1]
	g2 = key[/(.+)\s+\(([0-9]{4})\)/,2]	
	# transform rating from e.g 80% to 8
	myrating = value[0][0, value[0].length - 2]

	
	# format: 3/19/11
	seendate = value[1]
	#get month, day and year  by splitting the string on "/"
	# convert them to INTEGER
	seenmonth = seendate[/(\d+)\/(\d+)\/(\d+)/,1].to_i
	seenday = seendate[/(\d+)\/(\d+)\/(\d+)/,2].to_i
	#year: add "20" to get from e.g. "11" to "2011" (o.w. it's "0011"!!!)
	seenyear = ("20" + seendate[/(\d+)\/(\d+)\/(\d+)/,3]).to_i
	
	# convert into a Date object
	mydate = Date.new(seenyear,seenmonth,seenday)
	# convert into a Time object
	mytime = Time.new(seenyear, seenmonth, seenday, 0, 0, 0,)
	# convert into a timestamp
	mytimestamp = mytime.to_i

	myformattedseendate = seenday.to_s + "/" + seenmonth.to_s + "/" + seenyear.to_s
	#printfile(g1, g2, myrating, myformattedseendate, mytimestamp)
	
	mytmplist = {}
	mytmplist['title'] = movietitle
	mytmplist['year'] = movieyear	
	mytmplist['plays'] = 1
	mytmplist['last_played'] = mytimestamp
	
	mytmpRatings = {}
	mytmpRatings['imdb_id'] = ""
	mytmpRatings['title'] = movietitle
	mytmpRatings['year'] = movieyear	
	mytmpRatings['rating'] = myrating

	#CHOOSE which list to use!
	mylist[counter] = mytmpRatings
	counter = counter+1
}



print mylist.to_json
 
open("http://www.omdbapi.com/?t=127%20Hours&y=2010") {|f|
    f.each_line {|line| p line}
  }


=begin
TO POST SEEN MOVIE TO TRAKT:
{
    "username": "username",
    "password": "sha1hash",
    "movies": [
        {
            "imdb_id": "tt0114746",
            "title": "Twelve Monkeys",
            "year": 1995,
            "plays": 1,
            "last_played": 1255960578
        }
    ]
}

TO POST MOVIE RATINGS TO TRAKT
http://trakt.tv/api-docs/rate-movies
{
    "username": "username",
    "password": "sha1hash",
    "movies": [
        {
            "imdb_id": "tt0114746",
            "title": "Twelve Monkeys",
            "year": 1995,
            "rating": 9
        },
        {
            "imdb_id": "tt0082971",
            "title": "Indiana Jones and the Raiders of the Lost Ark",
            "year": 1981,
            "rating": 10
        }
    ]
}
=end
  
#print films
#print "Found:" + films.length.to_s
