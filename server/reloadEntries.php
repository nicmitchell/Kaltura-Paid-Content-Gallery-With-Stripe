<?php
set_time_limit(0);
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('client/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
global $USER_ID;
$ks = $client->session->start(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

//Filters the entries so that they are ordered by descending creation order
//In other words, the newer videos show up on the front page
$filter = new KalturaMediaEntryFilter();
$filter->orderBy = "-createdAt";
$pager = new KalturaFilterPager();
//Displays 12 entries per page
$pageSize = 12;
$page = 1;
//Retrieves the correct page number
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
//If a search has been made, display only the entries that match the search terms
$search = trim($_REQUEST['search']);
function escapeChar($input)
{
	$input = '\\'.$input[0];
	return $input;
}
$search = preg_replace_callback('|[#-+]|','escapeChar',$search);
$search = preg_replace_callback('|[--/]|','escapeChar',$search);
$search = preg_replace_callback('|!|','escapeChar',$search);
$search = preg_replace_callback('|"|','escapeChar',$search);
$search = preg_replace_callback('|-|','escapeChar',$search);
$search = preg_replace_callback('|\\/|','escapeChar',$search);
$filter->freeText = $search;
//If a channel has been selected, only display entries in said category
$filter->categoriesIdsMatchAnd = $_REQUEST['category'];
$pager->pageSize = $pageSize;
$pager->pageIndex = $page;
$results = $client->media->listAction($filter, $pager);
$count = $results->totalCount;
	
//This function creates a set of links to other entry pages
function create_gallery_pager  ($pageNumber, $current_page, $pageSize, $count, $js_callback_paging_clicked) {
	$search = trim($_REQUEST['search']);
	$category = $_REQUEST['category'];
	$pageNumber = (int)$pageNumber;
	$b = (($pageNumber+1) * $pageSize) ;
	$b = min ( $b , $count ); // don't let the page-end be bigger than the total count
	$a = min($pageNumber * $pageSize + 1,$count - ($count % $pageSize) + 1);
	$veryLastPage = (int)($count / $pageSize);
	$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
	if($pageNumber == $veryLastPage) {
		$pageToGoTo = $pageNumber;
		$pageToGoTo += (($pageNumber + 1) * $pageSize > $count) ? 0 : 1;
	}
	else
		$pageToGoTo = $pageNumber + 1;
	if ($pageToGoTo == $current_page)
		$str = "[<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\", $category)'>{$a}-{$b}</a>] ";
	else
		$str =  "<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\", \"$category\")'>{$a}-{$b}</a> ";
	return $str;
}
//The server may pull entries up to the hard limit. This number should not exceed 10000.
$hardLimit = 2000;
$pagerString = "";
$startPage = max(1, $page - 5);
$veryLastPage = (int)($count / $pageSize);
$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
$veryLastPage = min((int)($hardLimit / $pageSize), $veryLastPage);
$endPage = min($veryLastPage, $startPage + 10);
//Iterates to create several page links
for ($pageNumber = $startPage; $pageNumber < $endPage; ++$pageNumber) {
	$pagerString .= create_gallery_pager ($pageNumber , $page  , $pageSize , $count , "pagerClicked");
}

$beforePageString = "";
$afterPageString = "";
$prevPage = $page - 1;
if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:pagerClicked ($prevPage, \"$search\")'>Previous</a> ";
// add page 0 if not in list
if($startPage == 1 && $count > 0) $beforePageString .= create_gallery_pager(0, $page, $pageSize, $count, "pagerClicked");
$nextPage = $page + 1;
if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:pagerClicked ($nextPage, \"$search\")'>Next</a> ";
$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;

echo '<div class="pagerDiv">'.$pagerString.'</div>';
echo '<div class="entriesDiv" style="height: 312px;">';
//Uses a counter to keep track of each entry on the page
//Many elements such as id's and name's rely on this counter
$count = 0;
//Loops through every entry on your current page
foreach ($results->objects as $result) {

	//Creates a thumbnail that can be clicked to view the content
	$name = $result->name;
	$type = $result->mediaType;
	$id = $result->id;
	$filter = new KalturaMetadataFilter();
	$filter->objectIdEqual = $id;
	$pager = new KalturaFilterPager();
	$pager->pageSize = 50;
	$pager->pageIndex = 1;
	$meta = $client->metadata->listAction($filter, $pager);
	$metaResults = $client->metadata->listAction($filter, $pager)->objects;
	foreach($metaResults as $meta){
		// var_dump($meta);
		if($meta->metadataProfileId == PAYPAL_METADATA_PROFILE_ID){
			$xml = simplexml_load_string($meta->xml);
			$price = (float) $xml->Price;
		}
	}
	$categoryNames = explode(',', $result->categories);
	$title = $name."\n"."Belongs to channel(s): ";
	foreach($categoryNames as $categoryName)
		$title .= $categoryName.', ';
	$title = substr($title, 0, -2);
	$display = "";
	//If the entry is paid, display an icon over the thumbnail to indicate this
	$filter = new KalturaMediaEntryFilter();
	$filter->idEqual = $result->id;
	$pager = new KalturaFilterPager();
	$pager->pageSize = 1;
	$pager->pageIndex = 1;
	$filterAdvancedSearch = new KalturaMetadataSearchItem();
	$filterAdvancedSearch->type = KalturaSearchOperatorType::SEARCH_AND;
	$filterAdvancedSearch->metadataProfileId = PAYPAL_METADATA_PROFILE_ID;

	$filterAdvancedSearchItems = array();
	$filterAdvancedSearchItems0 = new KalturaSearchCondition();
	$filterAdvancedSearchItems0->field = "/*[local-name()='metadata']/*[local-name()='Paid']";
	$filterAdvancedSearchItems0->value = 'true';
	$filterAdvancedSearchItems[0] = $filterAdvancedSearchItems0;
	$filterAdvancedSearch->items = $filterAdvancedSearchItems;
	$filter->advancedSearch = $filterAdvancedSearch;
	$results = $client->media->listAction($filter, $pager)->objects;
	// $xml = simplexml_load_string($result->xml);
	// $price = (float) $xml->Price;
	if(count($results) > 0)
		$display =  $result->thumbnailUrl ? '<img width="120" height="68" id="thumb'.$count.'" style="background:url('.$result->thumbnailUrl.')" src="client/premiumentry.png" title="'.$title.'" >' : '<div>'.$id.' '.$name.'</div>';
	//If the entry is instead part of a paid channel, display an icon over the thumbnail to indicate this
	if($display == "") {
		$categories = explode(',', $result->categoriesIds);
		$filter = new KalturaCategoryFilter();
		$filter->idIn = $result->categoriesIds;
		$pager = new KalturaFilterPager();
		$pager->pageSize = 1;
		$pager->pageIndex = 1;
		$filterAdvancedSearch = new KalturaMetadataSearchItem();
		$filterAdvancedSearch->type = KalturaSearchOperatorType::SEARCH_AND;
		$filterAdvancedSearch->metadataProfileId = PAYPAL_CATEGORY_METADATA_PROFILE_ID;
		$filterAdvancedSearchItems = array();
		$filterAdvancedSearchItems0 = new KalturaSearchCondition();
		$filterAdvancedSearchItems0->field = "/*[local-name()='metadata']/*[local-name()='Paid']";
		$filterAdvancedSearchItems0->value = 'true';
		$filterAdvancedSearchItems[0] = $filterAdvancedSearchItems0;
		$filterAdvancedSearch->items = $filterAdvancedSearchItems;
		$filter->advancedSearch = $filterAdvancedSearch;
		$results = $client->category->listAction($filter, $pager)->objects;
		if(count($results) > 0)
			$display =  $result->thumbnailUrl ? '<img width="120" height="68" id="thumb'.$count.'" style="background:url('.$result->thumbnailUrl.')" src="client/premiumentry.png" title="'.$title.'" >' : '<div>'.$id.' '.$name.'</div>';
	}
	if($display == "")
		$display =  $result->thumbnailUrl ? '<img width="120" height="68" id="thumb'.$count.'" src="'.$result->thumbnailUrl.'" title="'.$title.'" >' : '<div>'.$id.' '.$name.'</div>';
	$display .= '<img src="client/play.png" id="play">';
	$cats = $result->categoriesIds;
	if(!isset($price)) { $price = 0; }
	// $price = ($price || 0);
	$thumbnail = '<a class="thumblink" data-price="'. $price  .'" rel="'.$result->id.'" cats="'.$cats.'" title="'.$title.'" >'.$display.'</a>';
	echo '<div class="float1">';
		echo $thumbnail.'   ';
	echo '</div>';
	echo '<div class="space"></div>';
	//Only show 3 entry thumbnails per row
    if($count > 0 && ($count + 1) % 3 == 0)
    	echo '<div class="clear"></div>';
	++$count;
}
echo '</div>';