#!/usr/bin/ruby
# script by Alessio Guerrieri
require 'nokogiri'
require 'open-uri'

# Put here your userID to parse your RottenTomatoes rating page, e.g.
# http://www.rottentomatoes.com/user/YOURUSERID/ratings/?search=&sort=title-asc&page=1
myuserID = 000000 #your user ID from the Rotten Tomatoes web page

1.upto(8) do |i|
  doc = Nokogiri::HTML(open("http://www.rottentomatoes.com/user/#{myuserID}/ratings/?search=&sort=title-asc&page=#{i}"))

  titles=doc.css("a[class=movie_title]").map{|i| i.text}
  ratings=doc.css("[class=tMeterScore]").map{|i| i.text}
  dates=doc.css("[class=date]/p").map{|i| i.text}
  
  titles.each_with_index do |el, id|
    puts "#{el},#{ratings[id]},#{dates[id]}" 
  end
  sleep 1
end
