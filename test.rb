#!/usr/bin/ruby
require 'date'
require 'json'
require 'open-uri'
#pretty print 
require 'pp'
#load my functions stored in myfunctions.rb
require './myfunctions'


#read input file and store RAW data into films hash
def parseRottenTomatoesList(inputfile)
	mylist = {}
	File.foreach(inputfile) do |line|
		#split line on ";" into the 3 fields below
		#E.g. a line is as follows
		# 127 Hours (2010);60%;3/19/11
		name,rating,date = line.split(';')
		# create films hash 
		mylist[name] = rating, date
	end
	return mylist
end

#for each movie into inputfilms hash 
# parse and create data for FILMSDATA list
def createMovieDatabase(inputfilms, filmsdata)
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
end


 # Read file with data received from OMDBAPI calling, in the format below, and parse data into FILMSDATA
 #"{\"Title\":\"127 Hours\",\"Year\":\
def readFileFromOMDB(inputfile, filmsdata)
	File.foreach(inputfile) do |line|
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
end



######################### START #########################

# import file generated from RottenTomatoes parsing
importfile = 'lista.csv'

# FILMSDATA: contains all the data about films
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

=begin
# Hash {} generated from input importfile
# format:
# movietitle (movieyear) => rating, date
# E.g. "127 Hours (2010)"=>["60%", "3/19/11\n"],
inputfilms = parseRottenTomatoesList(importfile)
 
#for each movie into inputfilms hash 
# parse and create data for FILMSDATA list
createMovieDatabase(inputfilms, filmsdata)
 
#add to FILMSDATA the url for OMBDAPI to get info about the imdbID
filmsdata.each { |key,value| 
	filmsdata[key]['omdbapiurl'] = "http://www.omdbapi.com/?t=#{value['title']}&y=#{value['year']}"
}
 
# call function to get IMDBID via URL API
#findIMDBIDviaURI(filmsdata)


# file generetad via OMDBapi call
imdblist = File.open('imdblist.csv')	
# Read file with data received from OMDBAPI calling, in the format below, and parse data into FILMSDATA
#"{\"Title\":\"127 Hours\",\"Year\":\
readFileFromOMDB(imdblist, filmsdata)
#close file
imdblist.close


#create CSV file with all FILMSDATA information
#backupFilmsOnFile("backup.csv", filmsdata, ";", true)
=end

######################### END #########################
 
 
 ##########
 # read CSV file with all data
 # NOTE: first line MUST BE the header!
 # calculate timestamp from seen date
 # update FILMSDATA hash
 # write on file
 ##########

 counter = 0;
 header = []
 mydbmovies = File.open('myfilmsdatabase.csv')
 File.foreach(mydbmovies) do |line|
	#split line on separator, that is ";"
	myrowarray = line.split(";")
	# remove carriage return from last element, i.e. \r\n
	myrowarray[myrowarray.length-1] = myrowarray.last.strip!
	
	#skip header, that is first line
	if counter == 0 then
		counter += 1
		header = myrowarray
		next
	end
	
	seendate = myrowarray[4]
	seenday = seendate[/(\d+)\/(\d+)\/(\d+)/,1].to_i
	seenmonth = seendate[/(\d+)\/(\d+)\/(\d+)/,2].to_i
	seenyear = seendate[/(\d+)\/(\d+)\/(\d+)/,3].to_i
	# convert into a Date object
	#mydate = Date.new(seenyear,seenmonth,seenday)
	# convert into a Time object
	mytime = Time.new(seenyear, seenmonth, seenday, 0, 0, 0,)
	# convert into a timestamp
	mytimestamp = mytime.to_i	
	# temporary list to be added to FILMSDATA list
	
	mytmplist = {}
	mytmplist[header[0]] = myrowarray[0]
	mytmplist[header[1]] = myrowarray[1]	
	mytmplist[header[2]] = myrowarray[2]
	mytmplist[header[3]] = mytimestamp
	mytmplist[header[4]] = myrowarray[4]
	mytmplist[header[5]] = myrowarray[5]
	mytmplist[header[6]] = myrowarray[6]
	mytmplist[header[7]] = myrowarray[7]
	# add info to FILMSDATA list
	filmsdata[myrowarray[0]] = mytmplist
	counter += 1

end
mydbmovies.close
#backupFilmsOnFile("out.csv", filmsdata, ";", true)
#print list to import SEEN MOVIES into trakt
#printJSONseenMovie(filmsdata)

#print list to import RATINGS into trakt
#printJSONrateMovie(filmsdata)

##################OLD CODE##################
#printJSONseenMovie(filmsdata)
#printJSONrateMovie(filmsdata)
#printNotFoundIMDBIDs(filmsdata)
#print filmsdata.to_json
############################################
