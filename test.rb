#!/usr/bin/ruby
require 'date'
require 'json'

myfile = File.read('lista.csv')  
myfilename = 'lista.csv'
films = {}
mylist = {}
counter = 0

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
	#puts "#{key},#{value[0]},#{value[1]}"
	puts "#{g1};#{g2};#{value[0]};#{value[1]}"
	
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
	#puts "#{seenday}-#{seenmonth}-#{seenyear}"
	# convert into a timestamp
	mytimestamp = mytime.to_i
	puts "#{mytimestamp}"
	#puts "#{mytime}, #{mytimestamp}, \n#{Time.at(mytimestamp).utc.to_datetime}"
	
	mytmplist = {}
	mytmplist['title'] = movietitle
	mytmplist['year'] = movieyear	
	mytmplist['plays'] = 1
	mytmplist['last_played'] = mytimestamp
	mylist[counter] = mytmplist
	counter = counter+1
}

print mylist.to_json
  
#print films
#print "Found:" + films.length.to_s
