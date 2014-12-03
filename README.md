Kaltura PayPal Gallery
==================
Kaltura Sample Application showing how to setup your Kaltura account and use PayPal for creating a gallery of pay-to-watch videos.

This is very much a work in progress

How it works
------------
* The first step in the account wizard performs two simple actions for your account. It will ask you to name an Access Control Profile and optionally
give that profile a preview time. This Access Control Profile is what will allow your videos to only be viewed in their entirety if they are
purchased by your customer. When they load a video, they will either be given a free preview first or immediately be informed that in order to
watch the content they must pay for it. In addition to this, behind the scenes the wizard will also create two metadata profiles. These two profiles,
one for individual videos and the other for your channels (categories), will store the pricing information for your content. This includes their
price, the sales tax and the currency that you would like to use.
* The next step involves creating a player that will allow your customers to purchase the videos. You must already have a player created in your
Kaltura KMC account under the Studio tab (http://www.kaltura.com/index.php/kmc/kmc4#studio|playersList). This step will either let you clone an
existing player or simply overwrite the old one. What it does is create an extra button either on screen or in the player's control panel which,
when clicked, will display your video's pricing information to the customer. This new player will also be able to automaticlaly show the pricing
information as soon as the video's preview has finished. For more advanced users, you may even provide your own javascript handler to perform a
different purchase routine when these buttons are clicked.
* The third step is where you will actually be able to set the prices for your videos and your channels. Either select the 'Individual Entires' tab
or the 'Categories' tab and you will be shown either all the videos or categories in your account. Clicking on a thumbnail for a video will then
bring up a new window where the pricing information may be set. You will notice that there is a checkbox labeled 'Paid Content' that when checked,
will show you all the different fields you must fill out for your video to properly work with PayPal's Digital Goods checkout. Enter the price, sales
tax and the currency you would like to use. Finally, at this point you should have created an Access Control Profile that you would like to use and
you should choose the appropriate one. You may create multiple Access Control Profiles under the 'Setup Account' menu option and thus now have the
option to choose the one you want to use for that specific video or channel (You might some videos to have a 10 second preview while others a 5 minute
preview). After submitting the information the content will be ready to sell. You may also keep the 'Paid Content' option unchecked and submitting
will actually make that content free again in case you ever want to stop selling it (Doing this will simply set the metadata profile's 'Paid' option
to false so that the gallery knows to use your free content player. In addition to this, it will set the video's access control profile to whatever
profile you have deemed as the Default in your KMC). When you're done giving prices to your content, click done and you will return to the main menu.
* In this particular sample, the user ID is randomly generated when the user first visits the gallery based on their IP address and this username
is stored as a cookie. Therefore as long as the user does not clear their browser cookies they get to keep all their purchases. In a production
environment it is encouraged to implement an actual user registration system.

Files
-----

* index.php - The front page that the user interacts with
* client/pptransact.js - Paypal's Script for digital goods express checkouts
* client/style.css - The styling for the front page
* server/kalturaConfig.php - Stores all the constants such as the authorization information and player IDs
	(This file can be automatically generated using the Account Wizard)
* server/reloadEntries.php - Displays the current page of entries
* server/reloadCategories.php - Displays a list of available channels on the account

Folders
-------

* AccountWizard - Contains a setup wizard that allows the admin to completely set up an account to use the paid content gallery
* server - Contains the html5-dg library with other files listed above
	(https://github.com/paypalx/html5-dg)
* server/client - Contains the Kaltura PHP5 client library
	(http://www.kaltura.com/api_v3/testme/client-libs.php)
* server/cert - Contains the certification information to securely connect to PayPal
