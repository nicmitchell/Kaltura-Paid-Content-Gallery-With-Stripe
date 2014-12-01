<!DOCTYPE HTML>
<?php
require_once('server/kalturaConfig.php');
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Kaltura Paid-Content Gallery Sample App</title>
	<!-- Style Includes -->
	<link href="client/style.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="client/loadmask/jquery.loadmask.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="client/colorbox/example4/colorbox.css" />
	<!-- Script Includes -->
	<script src="https://www.paypalobjects.com/js/external/dg.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="client/pptransact.js"></script>
	<!-- script src="http://html5.kaltura.org/js"></script-->
	<script src="http://cdnbakmi.kaltura.com/html5/html5lib/v1.6.12.40/mwEmbedLoader.php"></script>
	<!-- <script src="http://html5video.org/kgit/tags/v1.7.0.rc1/mwEmbedLoader.php"></script> -->
	<script type="text/javascript" src="client/loadmask/jquery.loadmask.min.js"></script>
	<script src="client/colorbox/colorbox/jquery.colorbox.js"></script>
	<script type="text/javascript" src="https://js.stripe.com/v2/"></script>

	<script type="text/javascript">
	  // This identifies your website in the createToken call below
	  // Stripe.setPublishableKey('pk_test_Eh5nOIzdutIJDFPVVQqUcJkJ');
	</script>
	<!-- Page Scripts -->
	<script>


		//Access Controled HLS playback is not yet available, check with your Kaltura Account Manager
		//this will make the playback in iOS use Progressive Download.
		mw.setConfig('Kaltura.UseAppleAdaptive', false);
		
		//are we loading the page or just calling ajax triggered by user interaction?
		var firstload = true;
		//Keeps track of the page being viewed
		var currentPage = 1;
		//Keeps track of the video being viewed
		var currentEntry = 0;
		//Used to track the entry link
		var entryId = 0;
		//Keeps track of the search terms
		var currentSearch = "";
		//Keeps track of the channel being viewed
		var currentCategory = "";
		//Used to track the category link
		var categoryId = 0;

		// var stripeResponseHandler = function(status, response) {
  //     var $form = $('#payment-form');

  //     if (response.error) {
  //       // Show the errors on the form
  //       $form.find('.payment-errors').text(response.error.message);
  //       $form.find('button').prop('disabled', false);
  //     } else {
  //       // token contains id, last4, and card type
  //       var token = response.id;
  //       // Insert the token into the form so it gets submitted to the server
  //       $form.append($('<input type="hidden" name="stripeToken" />').val(token));
  //       // and re-submit
  //       $form.get(0).submit();
  //     }
  //   };

		$(document).ready(function(e) {
			if(1 == <?php
						if(ADMIN_SECRET == 'xxx' || PARTNER_ID == 000)
							echo 1;
						else
							echo 0;
					?>) {
				$('#failConfig').show();
				$('#searchButton').attr('disabled', 'disabled');
				$('#showButton').attr('disabled', 'disabled');
				$('#searchBar').attr('disabled', 'disabled');
				$('#searchBar').blur();
				$('#channels').hide();
			}
			else {
				//When the pager loads, show a preview of the user's purchases
				//This feature has been removed for the demo but simply uncommenting the line below will renable it
				//showPurchases();
				//When the page loads, show the available channels
				showCategories(1);
				//When the page loads, show the entries
				showEntries(1, '');
				$('#searchBar').keyup(function(event) {
					if(event.which == 13)
						showEntries();
				});
			}
			// (function($) {
			//   $('#payment-form').submit(function(event) {
			//     var $form = $(this);

			//     // Disable the submit button to prevent repeated clicks
			//     $form.find('button').prop('disabled', true);

			//     Stripe.card.createToken($form, stripeResponseHandler);

			//     // Prevent the form from submitting with the default action
			//     return false;
			//   });
			// });
		});
		
		//INITIALIZE SESSION WITH APPROPRIATE LANGUAGE
		pptransact.init('php',false);

		//Initializes the PayPal express checkout billing system
		function bill(entryId) {
			pptransact.bill({
				userId:'<?php echo $USER_ID; ?>',
				itemId:entryId,
				itemQty:'1',
				successCallback: function(ret) {
					//bill success
					savePurchase(ret);
				},
				failCallback: function() {
					//bill canceled
				}
			});
		}

		function savePurchase(ret) {
			$.ajax({
				type: "POST",
				url: "server/savePurchase.php",
				data: {id: ret}
			}).done(function(msg) {
				$('#purchaseWindow').hide();
				checkAccess(currentEntry, ret);
			});
		}

		//Verifies whether or not a video has been paid for
		var verifySyncRes = null;
		function verify(entryId) {
			verifySyncRes = false;
			$.ajax({
				type: "POST",
				async: false,
				url: "server/verifyPurchase.php",
				data: {id: entryId}
			}).done(function(msg) {
				if(msg == 'true')
					verifySyncRes = true;
			});
			//This method verifies entries using HTML5 local storage
			/*
			pptransact.verify({
				userId:'<?php echo $USER_ID; ?>',
				itemId:entryId,
				successCallback: function() {
					//verify success
					verifySyncRes = true;
				},
				failCallback: function() {
					//verify cancelled
					verifySyncRes = false;
				}
			});
			*/
			return verifySyncRes;
		}
		
		// Loads the video is a Kaltura Dynamic Player
		function loadVideo(ks,uiConfId,entryId) {
		        if (window.kdp) {
		                kWidget.destroy(window.kdp);
		                delete(window.kdp);
		        }
		        var uniqid = +new Date();
		        var kdpId = 'kdptarget'+uniqid;
		        $('#playerDiv').html('<div id="'+kdpId+'" ></div>');
		        flashvars = {};
		        flashvars.externalInterfaceDisabled = false;
		        flashvars.autoplay = true;
		        flashvars.disableAlerts = true;
		        flashvars.entryId = entryId;
		        if(ks != "") flashvars.ks = ks;
		        kWidget.embed({
		                'targetId': kdpId,
		                'wid': '_<?php echo PARTNER_ID; ?>',
		                'uiconf_id' : 27216752, // To fix: get php constant rather than hard coding
		                'entry_id' : entryId,
		                'width': 400,
		                'height': 300,
		                'flashvars': flashvars,
		                'readyCallback': function( playerId ){
		                        window.kdp = $('#'+playerId).get(0);
		                        kdp.addJsListener("freePreviewEnd", 'freePreviewEndHandler');
		                }
		        });
		}

		//Responds to the page number index that is clicked
		function pagerClicked (pageNumber, search, category) {
			currentPage = pageNumber;
			showEntries(pageNumber, search, category);
		}

		//Show all the entries for a given page based on the channel and search terms or lack thereof
		function showEntries(page, terms, cat) {
			$('#purchaseWindow').hide();
			//If displaying all categories, nullify any effects that clicking a specific channel creates
			if(cat == "") {
				currentCategory = '';
				if(categoryId != 0)
					categoryId.css('borderColor', 'black');
				$('#searchText').text('Search all channels by name, description, or tags: ');
			}
			if(!cat)
				cat = currentCategory;
			if(terms == "")
				$('#searchBar').val('');
			currentSearch = $('#searchBar').val();
			$('body').mask("Loading...");
			$.ajax({
				type: "POST",
				url: "server/reloadEntries.php",
				data: {pagenum: page, search: $('#searchBar').val(), category: cat}
			}).done(function(msg) {
				$('#entryLoadBar').hide();
				$('body').unmask();
				$('#entryList').html(msg);
				//This is called whenever a video's thumbnail is clicked
				$(".thumblink").click(function () {
					$('#purchaseWindow').hide();
					$('#purchaseWindow').html('');
					if(entryId != 0)
						entryId.children('#play').hide();
					entryId = $(this);
					entryId.children('#play').css('display', 'block');
					currentEntry = $(this).attr('rel');
					checkAccess($(this).attr('rel'), $(this).attr('cats'));
					window.scrollTo(0,document.body.scrollHeight);
			    });
			    //Loads a video the first time the page loads
			    if(firstload) {
					entryId = $('#entryList').find('.thumblink:first');
					entryId.children('#play').css('display', 'block');
					currentEntry = entryId.attr('rel');
					checkAccess(entryId.attr('rel'), entryId.attr('cats'));
					firstload = false;
			    }
			});
		}

		//Shows a list of channels that may be clicked on
		function showCategories(page) {
			$.ajax({
				type: "POST",
				url: "server/reloadCategories.php",
				data: {page: page}
			}).done(function(msg) {
				$('#categoryList').unmask();
				$('#categoryList').html(msg);
				//When a channel is clicked, all the entries in that channel are shown
				//When viewing a channel, searching will search in that channel only
				$('.categoryLink').click(function() {
					$('#searchBar').val('');
					if(categoryId != 0)
						categoryId.children('.categoryName').css('background', 'white');
					categoryId = $(this).children();
					$(this).children().children('.categoryName').css('background', '#FFF500');
					currentCategory = $(this).attr('rel');
					$('#searchText').text('Search "' + $(this).children().attr('title') + '" by name, description, or tags: ');
					showEntries(1, currentSearch, currentCategory);
				});
				//Shows more channels to choose from
				$('.categoryPage').click(function() {
					$('#searchBar').val('');
					currentCategory = $(this).attr('rel');
					$('#categoryList').mask('Loading...');
					showCategories($(this).attr('rel'));
				});
			});
		}

		//Checks whether an entry is paid content or free
		//If it is in fact paid, determine if it has been bought either
		//individually or as part of a channel
		function checkAccess(id, cats) {
			var categories = cats.split(',');
			$('body').mask('Loading...');
			$.ajax({
				type: "POST",
				url: "server/inventory.php",
				data: {entryId: id}
			}).done(function(msg) {
				$('body').unmask();
				if(msg == 'false') {
					// This entry is free to watch
					$('#purchaseWindow').hide();
					loadVideo('', '<?php echo PLAYER_UICONF_ID; ?>', id);
				} else {
					var bool = false;
					for(var i = 0; i < categories.length; ++i) {
						if(categories[i] != "")
							bool = verify(categories[i]);
						if(bool) {
							$('#purchaseWindow').hide();
							$.ajax({
								type: "POST",
								url: "server/kaltura.php",
								data: {entryId: id}
							}).done(function(msg) {
								loadVideo(msg, '<?php echo PLAYER_UICONF_ID; ?>', id);
							});
							break;
						}
					}
					if(!bool) {
						bool = verify(id);
						$('#purchaseWindow').hide();
						if(bool) {
							$.ajax({
								type: "POST",
								url: "server/kaltura.php",
								data: {entryId: id}
							}).done(function(msg) {
								loadVideo(msg, '<?php echo PLAYER_UICONF_ID; ?>', id);
							});
						}
						else
							loadVideo('','<?php echo BUY_BUTTON_PLAYER_UICONF_ID; ?>', id);
					}
				}
			});
		}

		function showPurchases() {
			$.ajax({
				type: "POST",
				url: "server/reloadPurchases.php",
				data: {all: 'false'}
			}).done(function(msg) {
				if(msg != 0) {
					var response = JSON && JSON.parse(msg) || $.parseJSON(msg);
					$('#userVideos').html(response[0]);
					$('#userChannels').html(response[1]);
					//This is called whenever a video's thumbnail is clicked
					$(".thumblink").click(function () {
						$('#purchaseWindow').hide();
						$('#purchaseWindow').html('');
						if(entryId != 0)
							entryId.css('opacity', '1');
						entryId = $(this);
						$(this).css('opacity', '0.50');
						currentEntry = $(this).attr('rel');
						checkAccess($(this).attr('rel'), $(this).attr('cats'));
						window.scrollTo(0,document.body.scrollHeight);
				    });
				}
			});
		}

		function showAllPurchases() {
			$.colorbox({width:"50%", href:"server/userPurchases.php?all=true"});
		}

		//This is shown when a video's free preview ends and a purchase
		//is required to continue viewing the content
		function showPurchaseWindow(entryId) {
			kdp.sendNotification('doPause');
			$('#purchaseWindow').css('top', $('#playerDiv').offset().top);
			$('#purchaseWindow').css('left', $('#playerDiv').offset().left);
			$('#purchaseWindow').css('width', parseInt($('#playerDiv').css('width')) - 24);
			$('#purchaseWindow').css('height', parseInt($('#playerDiv').css('height')) - 24);
			$.ajax({
				type: "POST",
				url: "server/payment.php",
				data: {entryId: entryId}
			}).done(function(msg) {
				$('#purchaseWindow').show();
				$('#purchaseWindow').html(msg);
			});
		}

		//The default function that is called when the buy button is clicked
		//in the KDP
		function kalturaPayPalBuyHandler (entryId) {
			showPurchaseWindow(entryId);
		}

		//This is the KDP's end of preview event handler
		function freePreviewEndHandler() {
			showPurchaseWindow(kdp.evaluate('{configProxy.flashvars.entryId}'));
		}
		
		jQuery.fn.exists = function() { return (this.length > 0); };
	</script>
</head>
<body>
	<a target="_blank" href="https://github.com/kaltura/Kaltura-Paid-Content-Gallery-With-PayPal-Sample-App"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>
	<div id="wrapper">
		<div id="failConfig" class="notep" style="display: none">NOTE: Make sure to generate a configuration file using the PayPal Account Wizard.</div>
		<div><img src="client/loadBar.gif" style="display: none;" id="loadBar"></div>
		<h1>Kaltura Paid-Content Gallery Sample App</h1>
		<div id="userDiv">
			<div id="welcomeMessage">Welcome <span class="userid" title="This is a demo user, see note at the bottom of the page for more information."><?php echo $USER_ID; ?></span>,
				<ul style="margin: 0;">
					<li>
						<a href="javascript:showAllPurchases()">Click here to see your purchased videos and channels</a>
					</li>
				</ul>
			</div>
			<div id="userVideos" style="float: left;"></div>
			<div id="userChannels"></div>
			<div id="viewPurchases"></div>
		</div>
	</div>
	<div class="capsule">
		<img src="client/loadBar.gif" style="display: none;" id="entryLoadBar">
		<div id="channels">
			<h2 style="margin-top: 0px;">Channels</h2>
			<div id="categoryList"></div>
		</div>
		<div class="searchDiv">
			<span id="searchText">Search all channels by name, description, or tags: </span><input type="text" id="searchBar" autofocus="autofocus">
			<button id="searchButton" class="searchButtonClass" type="button" onclick="showEntries()">Search</button>
			<button id="showButton" type="button" onclick="showEntries(1, '', '')">Show All</button>
		</div>
		<div id="entryList"></div>
		<div id="playerDivContainer"><div id="playerDiv"></div></div>
		<div id="clearDiv" style="clear:both"></div>
		<div id="adminDiv">
			<button id="adminButton" type="button" onclick="location.href='AccountWizard'" style="margin-bottom: 11px; margin-left: -2px;">Admin Account Wizard</button>
		</div>
	</div>
	<div id="purchaseWindow">
		
	</div>
	<div id="entryHighlight"></div>
</body>
</html>