### scrapeAds

#### It is my personal hobby project and it is just for fun. 

#### What is it?
Build a web service to periodically scrape advertisements from some job searching websites, like craigslist. Process the data. Store it. Analyze it. Show it.

#### Why will it be helpful?
We can do analytics after collecting enough job posts data over time. for example,   
*   I want to find out the trend of PHP/Python development positions(so we know the job market better with our first-handed data); 
*   Send alert to me when positions with certain key words are open;
*   I want to find out repeated ads; 

Or possibly create a anonymous job review service based on the jobs posted. 

#### Tech stacks:
I choose the following stacks because I want to practice and be good at them.
*   web server: DigitalOcean VPS
*   backend: PHP YII2 framework. composer, mysql, memcached
*   scrape library: [Goutte](https://github.com/FriendsOfPHP/Goutte) 
*   how to measure string similarity? `similar_text()` or `levenshtein()` or Cosine similarity or more efficient algorithm?
*   frontend: bootstrap, jquery, maybe backbone.js

##### Current Status of 11/29/15:
*	console app is deployed to DigitalOcean droplet, It will crawl every hour for the latest ads. 
