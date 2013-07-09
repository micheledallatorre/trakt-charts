#!/usr/bin/ruby
require 'date'
require 'json'
require 'open-uri'
#pretty print 
require 'pp'



#create file
def createFile(myfile) 
	file = File.open(myfile, "w+")
	return file
end

#print to file
def appendToFile(myfile, text)
	file = File.open(myfile, "a")
	file.write(text) 
end

# outputs all data taken from the http://www.omdbapi.com/ website 
# e.g. http://www.omdbapi.com/?t=127%20Hours&y=2010
def findIMDBIDviaURI(list)
	list.each do |key,elem|
		url = elem['omdbapiurl']
		myuri = URI.parse(URI.encode(url.strip))

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

#prints only NOT FOUND imdbid for movies
def printNotFoundIMDBIDs(list)
	counter = 0
	puts "List of movies whose IMDB ID was NOT found\n"
	list.each do |key,elem|
		imdbid = elem['imdb_id']
		if imdbid == "" or imdbid == nil
			puts "\t" + key 
			counter += 1;
		end
	end
	puts "\nIMDB ID not found for " + counter.to_s + " movies out of " + list.length.to_s + "!"
end 


#print movies JSON for import into trakt
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
=end
def printJSONseenMovie (list)
	length = list.length
	counter = 0
	puts "\"movies\": ["
	list.each do |k,elem|
		puts "\t{"
			puts "\t\t\"imdb_id\": \"" + elem['imdb_id']  + "\","
			puts "\t\t\"title\": \"" + k + "\","
			puts "\t\t\"year\": " + elem['year'] + ","	
			puts "\t\t\"plays\": " + elem['plays'].to_s + ","
			puts "\t\t\"last_played\": " + elem['last_played'].to_s
		counter += 1			
		if counter == length 
			puts "\t}"	 
		else
			puts "\t},"
		end
	end 
	puts "]"
end

#print movies JSON for import into trakt
=begin
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
def printJSONrateMovie (list)
	length = list.length
	counter = 0
	puts "\"movies\": ["
	list.each do |k,elem|
		puts "\t{"
			puts "\t\t\"imdb_id\": \"" + elem['imdb_id']  + "\","
			puts "\t\t\"title\": \"" + k + "\","
			puts "\t\t\"year\": " + elem['year'] + ","	
			puts "\t\t\"rating\": " + elem['rating'].to_s
		counter += 1			
		if counter == length 
			puts "\t}"	 
		else
			puts "\t},"
		end
	end 
	puts "]"
end


importfile = 'lista.csv'
myfile = File.open(importfile)	
# generated from input importfile
# format:
# movietitle (movieyear) => rating, date
# E.g. "127 Hours (2010)"=>["60%", "3/19/11\n"],
inputfilms = {}
# contains all the data about films
=begin
FORMAT:
{"127 Hours"=>
  {"title"=>"127 Hours",
   "year"=>"2010",
   "plays"=>1,
   "last_played"=>1300489200,
   "imdb_id"=>"",
   "rating"=>"6"},
   ...
 }
=end
filmsdata = {}
counter = 0
imdblist = File.open('imdblist.csv')	






#read input file and store RAW data into films hash
File.foreach(importfile) do |line|
	#split line on ";" into the 3 fields below
	#E.g. a line is as follows
	# 127 Hours (2010);60%;3/19/11
	name,rating,date = line.split(';')
	# create films hash 
	inputfilms[name] = rating, date
end
 
#for each movie into inputfilms hash 
# parse and create data for FILMSDATA list
inputfilms.each_pair{ |key,value| 
	#get movie title without year
	#e.g. from "127 Hours (2010)" to "127 Hours"
	movietitle = key[/(.*[^([0-9]{4})])/].strip! 
	#get movie year
	movieyear = key[/(.*\(([0-9]{4})\))/,2]
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
		
	# temporary list to be added to FILMSDATA list
	mytmplist = {}
	mytmplist['title'] = movietitle
	mytmplist['year'] = movieyear	
	mytmplist['plays'] = 1
	mytmplist['last_played'] = mytimestamp
	mytmplist['seen_date'] = myformattedseendate
	mytmplist['imdb_id'] = ""
	mytmplist['rating'] = myrating
	mytmplist['omdbapiurl'] = ""
	# add info to FILMSDATA list
	filmsdata[movietitle] = mytmplist

}



#add to FILMSDATA the url for OMBDAPI to get info about the imdbID
filmsdata.each { |key,value| 
	filmsdata[key]['omdbapiurl'] = "http://www.omdbapi.com/?t=#{value['title']}&y=#{value['year']}"
}
 
# call function to get IMDBID via URL API
#findIMDBIDviaURI(filmsdata)

#print filmsdata.to_json
 
 # Read file from OMDBAPI calling, in the format below, and parse data into FILMSDATA
 #"{\"Title\":\"127 Hours\",\"Year\":\
 File.foreach(imdblist) do |line|
	# get title name from file
	title = line[/Title.{5}([^\\]*).*/,1]
	#get IMDBID from file
	imdbid = line[/imdbID.{5}([^\\]*).*/,1]
	
=begin
	# if title is null or empty (i.e. movie was not found)
	if title == "" or title == nil
		title = "NOTFOUND"
	end
	

	if imdbid == "" or imdbid == nil
		imdbid = "NOTFOUND"
	end
=end
		
	#EXACT MATCH TO AVOID ERRORS!
	#add IMDBID to FILMSDATA matching the title
	if filmsdata.has_key?(title)
		filmsdata[title]['imdb_id'] = imdbid
	#else
	#	puts "filmdata title:" + filmsdata['title'].to_s + "; found title:" + title.to_s
	end
 end


=begin
# create backup file with all my FILMSDATA in CSV format
myoutfile = createFile("backup.txt");
filmsdata.each do |k,v|
	# separator is comma
	sep = ","
	line = 	k + sep 			+ v['year'] + sep 			+ v['plays'].to_s + sep 			+ v['last_played'].to_s + sep 			+ v['seen_date'].to_s + sep 			+ v['imdb_id'].to_s + sep			+ v['rating'].to_s + sep 			+ v['omdbapiurl'] + "\n";
	appendToFile(myoutfile, line)
end

#printJSONseenMovie(filmsdata)
#printJSONrateMovie(filmsdata)
=end

 



  
