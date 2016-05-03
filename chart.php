<?php
/**
 * twitter-chart-demo : Just a demo of using twitter search api with php and Twitter-API-PHP
 * 
 * PHP version 5.5.32
 * 
 * @author   Konstantinos Togias <info@ktogias.gr>
 * @license  MIT License
 * @version  1.0.0
 * @link     https://github.com/ktogias/twitter-chart-demo
 */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Twitter Chart Demo</title>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

   <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

   <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <h1>Twiter chart demo!</h1>
    <div class="container">

<?php
require_once('TwitterAPIExchange.php');
$settings = [
	'oauth_access_token' => '',
	'oauth_access_token_secret' => '',
	'consumer_key' => "xxxxxxxxxx",
	'consumer_secret' => "xxxxxxxxxxxx"
];

$twitter = new TwitterAPIExchange($settings);

$url = "https://api.twitter.com/1.1/search/tweets.json";

$maxQueries = 100;

$search = [
	'vodafone',
	'eurobank'
];

$startDate = new DateTime(date('Y-m-d'));
$startDate->sub(new DateInterval('P7D'));


$dateRange = [
	'from' => $startDate,
	'to' => new DateTime(date('Y-m-d')),
];

$interval = new DateInterval('P1D');
$period = new DatePeriod($dateRange['from'], $interval, $dateRange['to']);

$days = [];

foreach ($period as $day){
	$dayData = [
		'since' => clone $day,
		'until' => $day->add($interval),
	];
	foreach ($search as $term){
		$dayData['data'][$term] = ['count' => 0];
		$q = '?q='.$term.' since:'.$dayData['since']->format('Y-m-d').' until:'.$dayData['until']->format('Y-m-d').'&include_entities=0';
		$res = $twitter->setGetfield($q)
			->buildOauth($url, 'GET')
			->performRequest();
		$resObj = json_decode($res);
		$qNum = 0;
		if(empty($resObj->errors)){
			$dayData['data'][$term]['count'] = count($resObj->statuses);
			while (!empty($resObj->search_metadata->next_results) && $qNum < $maxQueries){
				$q = urldecode($resObj->search_metadata->next_results);
				$res = $twitter->setGetfield($q)
                        		->buildOauth($url, 'GET')
                        		->performRequest();
				$resObj = json_decode($res);
				$qNum++;
				if(empty($resObj->errors)){
					$dayData['data'][$term]['count'] += count($resObj->statuses);
				}
				else {
					$dayData['data'][$term]['error'] = ['errors' => $resObj->errors, 'qNum' => $qNum, 'q' => $q];
				}
			}
		}
		else {
                	$dayData['data'][$term]['error'] = ['errors' => $resObj->errors, 'qNum' => $qNum, 'q' => $q];
                }
	}
	$days[] = $dayData;
}
?>
	<canvas id="chart"></canvas>
    </div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.0.2/Chart.bundle.min.js"></script>
	<script>
		var daysStr = '<?php echo json_encode($days)?>';
		var days = JSON.parse(daysStr);
		var labels = [];
		var data = {};
		var colors = [
			{
				bg: 'rgba(75,192,192,0.4)',
				border: 'rgba(75,192,192,1)'
			},
			{
				bg: 'rgba(255,205,86,0.4)',
				border: 'rgba(255,205,86,1)'
			}
		];
		var datasets = [];
		for (di in days){
			var day = days[di];
			var fullDateTime = day['since']['date'];
			//formatting date for labels	
			labels.push(fullDateTime.substring(0, fullDateTime.indexOf(' ')));
			for(dti in day['data']){
				if (!data[dti]){
					data[dti] = [];
				}
				data[dti].push(day['data'][dti]['count']);
			}
		}
		for (label in data){
			var color = colors.pop();
			datasets.push({
				label: label,
				fill: false,
				backgroundColor: color.bg,
				borderColor: color.border,
				pointBorderColor: color.border,
				pointBackgroundColor: "#fff",
				pointBorderWidth: 1,
            			pointHoverRadius: 5,
				pointHoverBackgroundColor: color.border,
				pointHoverBorderColor: color.border,
				pointHoverBorderWidth: 2,
				data: data[label]
			});
		}
		var ctx = document.getElementById("chart");
		var lineChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: datasets
			}
		});
	</script>
    </body>
</html>
