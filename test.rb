#!/usr/bin/ruby
require 'date'
require 'json'
require 'open-uri'
#pretty print 
require 'pp'
#load my functions
require './myfunctions'



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
	
	#EXACT MATCH TO AVOID ERRORS!
	# Note that TITLE could be NIL, or "", or a bit different so the match is false!
	#add IMDBID to FILMSDATA matching the title
	if filmsdata.has_key?(title)
		filmsdata[title]['imdb_id'] = imdbid
	#else
	#	puts "filmdata title:" + filmsdata['title'].to_s + "; found title:" + title.to_s
	end
 end





#printJSONseenMovie(filmsdata)
#printJSONrateMovie(filmsdata)

backupFilmsOnFile("backup.csv", filmsdata, ";", true)

#printNotFoundIMDBIDs(filmsdata)
 



  
