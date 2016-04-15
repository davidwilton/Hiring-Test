<?php
# SSENSE Test
# David Wilton
# david.a.wilton@gmail.com
# April 15 2016
           
$servername = "localhost";
$username = "root";
$dbname = "ssense";
$CONN = mysqli_connect($servername, $username, "", $dbname);
if (!$CONN) {
    die("Connection failed: " . mysqli_connect_error());
}

class Product
{
	public $id;
	public $name;
	public $category;
	public $price;
	public $country;
	public $stock;

	function __construct($id, $name = NULL, $category = NULL, $price = NULL, $stock = NULL)
	{
		$this->id = $id;
		$this->name = $name;
		$this->category = $category;
		$this->price = $price;
		$this->stock = $stock;
	}
	
	function mkView()
	{
		$return = 
'<div class="browsing-product-item" >
<div class="browsing-product-thumb-container">
	<div class="browsing-product-thumb">
	<img class="product-thumbnail" src="https://res.cloudinary.com/ssenseweb/image/upload/b_white,c_lpad,g_south,h_1086,w_724/c_scale,h_560/v459/161251M236001_1.jpg">
</div>
</div>
<div class="browsing-product-description text-center vspace1" itemtype="http://schema.org/Offer" itemscope="" itemprop="offers">
<p class="bold">'.$this->category.'</p>
<p>'.$this->name.'</p>
<p class="price">
<span class="price">'.number_format($this->price, 2).'</span>
</p>
</div>';
		return $return;
	}
	
	static function getProductbyCurrency($currency)
	{
		global $CONN;
		
		$res = mysqli_query($CONN, 
		"SELECT products.id as id, products.name as name, categories.name as category, prices.price as price, stocks.quantity as stock FROM products JOIN prices ON products.id = prices.product_id JOIN stocks ON products.id = stocks.product_id JOIN categories on products.category_id = categories.id WHERE prices.country_id IN (SELECT id FROM countries WHERE countries.currency_id IN (SELECT id FROM currencies WHERE currencies.code='$currency'));
		");
		$ret = array();
		while($assoc = mysqli_fetch_assoc($res))
		{
			$ret[$assoc['id']] = new Self($assoc['id'], $assoc['name'], $assoc['category'], $assoc['price'], $assoc['stock']);
		}
		
		return $ret;
	}
	
	public static function displayProductbyCurrency($currency)
	{
		if(!$currency){
			return;
		}
    	$ret = '
<!doctype html>
<html class="no-js" lang="en">
  <head>
      <meta charset="utf-8" />
      <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    </head>
    <body>
        <div class="container">
            <div class="header clearfix">
                <nav>
                <ul class="nav nav-pills pull-right">
                <li role="presentation"><a href="/">Home</a></li>
                <li role="presentation" class="active"><a href="#">Canadian Products</a></li>
                <li role="presentation"><a href="#">Montreal Weather</a></li>
                </ul>
                </nav>
                <h3 class="text-muted">SSENSE - IT</h3>
             </div>        
        	<div class="container-fluid">
	        	<h3 class="text-muted">Canadian Products</h3>
				<div class="browsing-product-list" itemtype="http://schema.org/Product" itemscope="">';
			$products = self::getProductbyCurrency($currency);
			foreach($products as $product)
			{
				$ret .= $product->mkView();
			}
			$ret .= '
				</div>
             </div>
        </div>
   
        <footer class="footer">
          <p>&copy; 2016 SSENSE</p>
        </footer>
      </div>
   </body>';
   
   		return $ret;
	}
}

// include the application with all the configs and services
$app = require_once __DIR__ . '/../src/website/app.php';

$app->get('/canProducts/{id}', function (Silex\Application $app, $id) {
    if (!$id) {
        $app->abort(404);
    }
	echo Product::displayProductbyCurrency($id);
	
    return false;
});
$app->get('/weather/{id}', function (Silex\Application $app, $id) {
    if ($id != 'YUL') {
        $app->abort(404);
    }
	echo getYULweather();
	
    return false;
});

// Run the app !
$app->run();
echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$("nav li:nth-child(2)").find("a").attr("href","/canProducts/CAD");
	$("nav li:nth-child(3)").find("a").attr("href","/weather/YUL");
});
</script>';

function getYULweather(){
	if(isset($_COOKIE['currently'])){
		$ret_currently = $_COOKIE['currently'];
		$ret_daily = $_COOKIE['daily'];
	}else{
		$address = 'Montreal, QC';
		$prepAddr = str_replace(' ','+',$address);
		$geocode=file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
		$output = json_decode($geocode);
		$lat = $output->results[0]->geometry->location->lat;
		$long = $output->results[0]->geometry->location->lng;
	
	
		$apikey = 'e60efe99b1bf9036ce9a154a5c1c10ee';
		$url = 'https://api.forecast.io/forecast/e60efe99b1bf9036ce9a154a5c1c10ee/';
		
		$time = '';
		$url = "https://api.forecast.io/forecast/$apikey/$lat,$long";
		$forcasts = file_get_contents($url);
		$output = json_decode($forcasts);
		
		$ret_currently = "<p>Currently: ".$output->currently->summary."</p>";
		$ret_currently .= "<p>Temperature: ".$output->currently->temperature."</p>";
		$ret_daily = $output->daily->summary."<br/><br/>";
		foreach( $output->daily->data as $data){
			$ret_daily .= '<b>'.date("l j", $data->time).'</b><br/>';
			$ret_daily .= $data->summary.'<br/>';
			$ret_daily .= "High: ".$data->temperatureMax."<br/>";
			$ret_daily .= "Low: ".$data->temperatureMin."<br/><br/>";
		}
		setcookie("currently", $ret_currently, time() + (60 * 60));	#expiry in 1 hour
		setcookie("daily", $ret_daily, time() + (60 * 60));	#expiry in 1 hour
	}
	$ret = '
	<!doctype html>
	<html class="no-js" lang="en">
	  <head>
		  <meta charset="utf-8" />
		  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
		  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		</head>
		<body>
			<div class="container">
				<div class="header clearfix">
					<nav>
					<ul class="nav nav-pills pull-right">
					<li role="presentation"><a href="/">Home</a></li>
					<li role="presentation"><a href="#">Canadian Products</a></li>
					<li role="presentation" class="active"><a href="#">Montreal Weather</a></li>
					</ul>
					</nav>
					<h3 class="text-muted">SSENSE - IT</h3>
				 </div>        
				<div class="container-fluid">
					<h3 class="text-muted">Montreal Weather</h3>
					<h4>Today</h4>
					<div>'.$ret_currently.'
					</div>
					<h4>Forcast</h4>
					<div>'.$ret_daily.'
					</div>
				</div>
				  </div>
	   
			<footer class="footer">
			  <p>&copy; 2016 SSENSE</p>
			</footer>
		  </div>
	   </body>';		
				
	echo $ret;
	
	return;
}
#Database scheme:
/*
CREATE DATABASE ssense;
CREATE TABLE categories (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(30) NOT NULL,
parent_id INT(6) UNSIGNED
);

CREATE TABLE currencies (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
code VARCHAR(30) NOT NULL,
format VARCHAR(30) NOT NULL
);

CREATE TABLE countries (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(30) NOT NULL,
currency_id INT(6) UNSIGNED NOT NULL,
code VARCHAR(30) NOT NULL,
FOREIGN KEY (currency_id) 
        REFERENCES currencies(id)
        ON DELETE CASCADE
);

CREATE TABLE products (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(30) NOT NULL,
category_id INT(6) UNSIGNED NOT NULL,
FOREIGN KEY (category_id) 
        REFERENCES categories(id)
        ON DELETE CASCADE
);

CREATE TABLE prices (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
country_id INT(6) UNSIGNED NOT NULL,
product_id INT(6) UNSIGNED NOT NULL,
price DECIMAL(10, 2),
FOREIGN KEY (country_id) 
        REFERENCES countries(id)
        ON DELETE CASCADE,
FOREIGN KEY (product_id) 
        REFERENCES products(id)
        ON DELETE CASCADE
);

CREATE TABLE stocks (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
product_id INT(6) UNSIGNED NOT NULL,
quantity INT(6) UNSIGNED,
FOREIGN KEY (product_id) 
        REFERENCES products(id)
        ON DELETE CASCADE
);*/
