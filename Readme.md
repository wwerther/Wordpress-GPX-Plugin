=== Gpx2Chart ===
Contributors: wwerther
Donate link: http://wwerther.de/
Tags: gpx, tracks, charts
Requires at least: 3.1
Tested up to: 3.1.2
Stable tag: 0.1

A plugin that generates nice charts from GPX-files. It put's all information about heartrate, cadence, elevation and speed into one chart. 

== Description ==
This plugin generates charts from GPX-files. The file will be processed in real-time, no intermediate data is written to the database or the wp-content directory. Currently the plugin is tested with GPX-Files generated by a Garmin-Device.
The TRK-Section is parsed and information about your heartrate, your cadence and the current speed is calculated. Since GPX-files can contain a lot of data-points they can be reduced to a certain amount to guarantee, that the rendering time of the chart won't consume to much time in your browser.
You can hover with the mouse over the generated chart to get detailed information about the currently selected time-instance. You can also turn off graphs that where currently shown. Every graph get its own Y-axis and color.
You can also zoom into the graph to get more details.

The graphs are generated with the [Highcharts-API](http://www.highcharts.com/). Please respect their license and pricing (Free for Non-Commercial).

== Installation ==

1. Download the Plugin ZIP file
1. Unpack the Plugin-ZIP
1. Upload the gpx2chart folder to your wordpress plugin directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Where do I get the development-version? =

I use git for my own development. You can find the Trunk-version on [GitHub](https://github.com/wwerther/Wordpress-GPX-Plugin). It includes also the scripts to update the subversion-directory on wordpress.

= Are there known bugs? =

Yes, I'm sorry, there are some known bugs. I had no time to fix or trace them yet.

* The legend on the x-axis disappear sometimes when selecting/deselecting single-graphs

= Is there a Roadmap? =

Yes, there is kind of a roadmap. But the order depends on my time.

* Make the graphs more customizable
* Include CSS to change layout of the summary, that is currently displayed below the graph
* Include a Link to the OSM-module so the Lon/Lat-Information is shown on the map, when hovering over the chart.
* Include some more error-detection (e.g. File-Not-Found) and change the output then.

== Screenshots ==

1. A graph displaying the heartrate, the elevation and the speed of one track
2. The Tool-Tip when hovering over the chart shows even more information (time since start, distance since start)
3. By clicking on the legend you can deactivate one or graph

== Changelog ==

= 0.1 =
* Initial version of this plugin

== Upgrade Notice ==

= 0.1 =
Initial version
