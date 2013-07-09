#!/usr/bin/ruby
require 'date'
require 'json'
require 'open-uri'
#pretty print 
require 'pp'


importfile = 'lista.csv'
myfile = File.open(importfile)	
films = {}
mylist = {}
counter = 0
imdblist = File.open('imdblist.csv')	

# print  e.g. 
# 127 Hours;2010;60%;19/3/2011;1300489200
def printFile(mtitle, myear, rating, seendate, mytimestamp)
	puts "#{mtitle};#{myear};#{rating};#{seendate};#{mytimestamp}"
end

# outputs all data taken from the http://www.omdbapi.com/ website 
# e.g. http://www.omdbapi.com/?t=127%20Hours&y=2010
def findIMDBIDviaURI(myurllist)
	myurllist.each do |title,url|
		#puts title + "-------------" + url
		myuri = URI.parse(URI.encode(url.strip))
		#p myuri

		open(myuri) { |f|
			f.each_line { |line| 
				p line
			}
		}

	end
end

#prints an array (use pp if you prefer)
def printArray(arr) 
	arr.each do |e|
		puts e
	end
end 

#read input file and store data into films hash
File.foreach(importfile) do |line|
	#split line on ";" into the 3 fields below
	#E.g. a line is as follows
	# 127 Hours (2010);60%;3/19/11
	name,rating,date = line.split(';')
	# create films hash 
	films[name] = rating, date
end
 
#for each movie into films hash 
films.each_pair{ |key,value| 
	#get movie title without year
	#e.g. from "127 Hours (2010)" to "127 Hours"
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
	#printFile(g1, g2, myrating, myformattedseendate, mytimestamp)
	
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

myurlarray = []
moviestitles = []
myurllist = {}
myres = []
#print all titles-years with links
mylist.each { |key,value| 
	myurlarray.push "http://www.omdbapi.com/?t=#{value['title']}&y=#{value['year']}"
	
	x = [value['title'], value['year'], ""]
	moviestitles.push x
	
	myurllist[value['title']] = "http://www.omdbapi.com/?t=#{value['title']}&y=#{value['year']}"
}

#printArray(myurlarray)
 
# call function to get IMDBID via URL API
#findIMDBIDviaURI(myurllist)

#print mylist.to_json
 
 temp = []
 templist = {}
 index = 0
 #read file
 #"{\"Title\":\"127 Hours\",\"Year\":\
 File.foreach(imdblist) do |line|
	# get title name
	title = line[/Title.{5}([^\\]*).*/,1]
	
	# if title is null or empty (i.e. movie was not found)
	if title == "" or title == nil
		title = "NOTFOUND"
	end
	
	imdbid = line[/imdbID.{5}([^\\]*).*/,1]
	if imdbid == "" or imdbid == nil
		imdbid = "NOTFOUND"
	end
	
	moviestitles[index][2] = imdbid
	index += 1
	
	temp.push(title)
	temp.push(imdbid)
	templist[title]= imdbid
	
  end
  
#pp moviestitles

#print only NOT FOUND imdbid for movies
=begin
moviestitles.each do |a|
	if a[2] == "NOTFOUND"
		pp a
	end
end 
=end

#print movies JSON for import into trakt
puts "\"movies\": ["
moviestitles.each do |a|
	puts "\t{"
	
	if a[2] == "NOTFOUND"
		puts "\t}"
	else 
		puts "\t\t\"imdb_id\": \"" + a[2] + "\","
	end
	
	puts "\t\t\"title\": \"" + a[0] + "\","
	puts "\t\t\"year\": " + a[1] + ","	
	puts "\t}"
	 
end 
puts "]"
 
#printArray(temp)
#pp templist
 
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
