#!/usr/bin/ruby
# script by Alessio Guerrieri
require 'nokogiri'
require 'open-uri'

1.upto(8) do |i|
  doc = Nokogiri::HTML(open("http://www.rottentomatoes.com/user/897477/ratings/?search=&sort=title-asc&page=#{i}"))

  titles=doc.css("a[class=movie_title]").map{|i| i.text}
  ratings=doc.css("[class=tMeterScore]").map{|i| i.text}
  dates=doc.css("[class=date]/p").map{|i| i.text}
  
  titles.each_with_index do |el, id|
    puts "#{el},#{ratings[id]},#{dates[id]}" 
  end
  sleep 1
end
