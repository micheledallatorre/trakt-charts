
#create file
def createFile(myfile) 
  file = File.open(myfile, "w+")
	return file
end

#print to file
def appendToFile(myfile, text)
	#"a"  Write-only, starts at end of file if file exists, otherwise creates a new file for writing.
	file = File.open(myfile, "a")
	file.write(text) 
	file.close
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

 
# create backup file with all my FILMSDATA in CSV format, choose which fields to print
def backupFilmsOnFileFixedColumn(filename, filmslist, separator, printheader)
	myoutfile = createFile(filename)
	sep = separator
	if printheader
		header = "title" + sep + "year" + sep + "plays" + sep + "last_played" + sep + "seen_date" + sep + "imdb_id" + sep + "rating" + sep + "OMDB API URL" + "\n"
		appendToFile(myoutfile, header)
	end
	filmslist.each do |k,v|
		# separator is comma in CSV format, but you can choose yours
		line = 	k + sep + v['year'] + sep + v['plays'].to_s + sep + v['last_played'].to_s + sep + v['seen_date'].to_s + sep + v['imdb_id'].to_s + sep+ v['rating'].to_s + sep + v['omdbapiurl'] + "\n";
		appendToFile(myoutfile, line)
	end
end

# create backup file with all my FILMSDATA in CSV format, no option for choosing which fields of FILMSDATA!
def backupFilmsOnFile(filename, filmslist, separator, printheader)
	myoutfile = createFile(filename)
	sep = separator
	if printheader
		#Note: filmslist.first returns an array! E.g.
		#["127 Hours",
		# {"title"=>"127 Hours",
		# "year"=>"2010",
		# "plays"=>1,
		# "last_played"=>1300489200,
		# "seen_date"=>"19/3/2011",
		# "imdb_id"=>"tt1542344",
		# "rating"=>"6",
		# "omdbapiurl"=>"http://www.omdbapi.com/?t=127 Hours&y=2010"}]
		# so I need to get the hash using [1] that is the second element of the array returned!
		nested_hash = filmslist.first[1]
		#get the keys in an array
		header = nested_hash.keys
		#print each key
		header.each do |v|
			#add separator only if not last element, else add \n
			if header.rindex(v) == (header.length - 1)
				appendToFile(myoutfile, v +"\n")
			else
				appendToFile(myoutfile, v + sep)
			end	
		end
	end
	filmslist.each do |k,movieinfo|
		#Returns a new array populated with the values from movieinfo, e.g.
		#["Zombieland",
		#	 "2009",
		#	 1,
		#	 1290207600,
		#	 "20/11/2010",
		#	 "tt1156398",
		#	 "7",
		#	 "http://www.omdbapi.com/?t=Zombieland&y=2009"]
		myrow = movieinfo.values
		#pp myrow
		myrow.each do |value|
			#add separator only if not last element, else add \n
			if myrow.rindex(value) == (myrow.length - 1)
				appendToFile(myoutfile, value.to_s + "\n")
			else
				appendToFile(myoutfile, value.to_s + sep)
			end				
		end
	end
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
