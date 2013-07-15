Trakt Charts
============
A simple PHP/JavaScript project that uses [Trakt API](http://trakt.tv) &amp; [Google Charts](https://developers.google.com/chart/) to show your data

## Goal

The goal of this project is to have some webpages displaying graphs of your Trakt data, e.g. how many movies you watched per week.

Here are some example images.

![graph1](graph1.PNG)
![graph2](graph2.PNG)

## Changelog
v 0.1 beta: added https://code.google.com/p/php-class-for-google-chart-tools/ with an example of Google chart

v 0.1 added http://tablesorter.com/docs/ to sort the HTML table


### Usage examples

```Ruby
parseRottenTomatoes.rb > list.csv
```
Use this Ruby script (parseRottenTomatoes.rb) to create a list of ratings from your RottenTomatoes rating webpage, and outputs it into a file called list.csv with the following format.

```CSV
127 Hours (2010);60%;3/19/11
21 Jump Street (2012);80%;8/21/12
50 First Dates (2004);80%;10/09/11
```

NOTE: you need to specify your RottenTomatoes UserID into the parseRottenTomatoes.rb script before running it!

__TODO__


```PHP
// PHP code to be inserted
```

## Note
Under development!
Feel free to contact me.
