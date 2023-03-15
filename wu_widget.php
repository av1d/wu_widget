<?php

/* USER SETTINGS
   ------------- */

/*
 Read the settings below and setup a free API key for OpenWeatherMap (https://openweathermap.org/).
 The script will deposit a file named "wu_widget.gif" in the directory specified under $wuPath named wu_widget.gif,
 this is the file you will access from any page you want the widget on.
 It will also save the forecast if specified for usage in a widget.
 You can also embed display_widget.php inside any PHP document to display a widget with the forecast. See below.
 
 This script assumes you are grabbing the temp in Fahrenheit from the API since WU widgets were for the USA,
 though it also calculates the temperature in Celsius.
 
 You should not call this script directly from a page, it will probably go over the API's rate limit and it's
 unnecessary. You're allowed 1000 API calls per day on OpenWeatherMap.
 
 Recommended usage is on a cronjob every 30 minutes or hour. 
 Example:
 * / 30 * * * * /usr/bin/php /my/folder/weather/wu_widget.php > /dev/null 2>&1
 (remove spaces between * / 30).
 
 Not required but it'd be appreciated if you linked back to my github by making
 the widget itself a link or by other means/mentions, and share how you've used it or any
 graphics, templates or modifications you've made. https://github.com/av1d/wu_widget
 
 This software is neither created nor endorsed by Weather Underground or OpenWeatherMap.
 Use at your own risk.
*/


// absolute path/location of this script, including trailing slash at the end ("/home/user/weather/")
$wuPath = "/home/user/weather/";

// see this for supported timezones: https://www.php.net/manual/en/timezones.php
date_default_timezone_set('America/New_York');

// obtain your coordinates from here. You only need to do this once.
// Make sure everything you enter is comma-separated. See this for example:
// https://geocoding.geo.census.gov/geocoder/geographies/onelineaddress?address=1%20main%20street%2C%20boston%2C%20ma%2002129&benchmark=4&vintage=420
$lattitude = "41.28447583040639";
$longitude = "-70.34742338067052";

// API key for OpenWeatherMap. It's free, get it here: https://openweathermap.org/appid
$apiKey = "paste_your_key_here";

// uncomment a skin to use it. only leave one uncommented at a time.
// see images/samples for what they look like.

$skin = "images/skins/classic/";
//$skin = "images/skins/Cosmic_Cutie/";
//$skin = "images/skins/cyberpunk/";
//$skin = "images/skins/cyberspace/";
//$skin = "images/skins/Frankly/";
//$skin = "images/skins/gadget/";
//$skin = "images/skins/geometry/";
//$skin = "images/skins/glass_bar/";
//$skin = "images/skins/Memphis_Design/";
//$skin = "images/skins/Memphis_orange/";
//$skin = "images/skins/OpenWeatherMap/";
//$skin = "images/skins/rainbow_matrix/";
//$skin = "images/skins/vaporwave/";
//$skin = "images/skins/Winamp/";
//$skin = "images/skins/your-skin-here/";
// see interface_templates/ for templates to make your own.


$save_forecast = true;

/*
  display_widget.php can be called from any PHP page to display a weather
  widget with the forecast. see images/samples/widget.gif to see what it looks like.
  set $save_forecast to true if using PHP widget. Otherwise set to false.
  Example, place:
  <?php include 'display_widget.php';?>
  in your PHP document to embed the widget. 
  You can/should edit the display_widget.php file as well to set paths and CSS styles.
*/


/* -----------------
   END USER SETTINGS */



define("VERSION", "1.0b");

function getTemperature() {
    global $lattitude, $longitude, $apiKey, $save_forecast, $forecast;
    $owmURL = sprintf("https://api.openweathermap.org/data/2.5/onecall?lat=%F&lon=%F&exclude=hourly,daily&units=imperial&appid=%s"
                      ,$lattitude
                      ,$longitude
                      ,$apiKey);
    $json = file_get_contents($owmURL);
    $data = json_decode($json,true);
    $currentTemp = round($data['current']['temp']);

    if ($save_forecast == true) {
        $forecast[] = $data['current']['wind_speed'];  // see the API output for more data types
        $forecast[] = $data['current']['humidity'];
        $forecast[] = $data['current']['feels_like'];
    }
    return $currentTemp;
}

function getTime() {
    $timeNow = date("h:i");
    $checkZero = ($timeNow[0] == "0") ? substr($timeNow, 1) : $timeNow;  // if first character is 0, replace it.
    return $checkZero;
}

function digitPlace($count,$length) { // define positions of digits on LED display in pixels.
    // shift digits to the right according to string length so the rightmost segment is always occupied.
    // longest possible string is from time which would be 5 (00:00), or high temp like "100 F" (counting the space).
    $length == 1 ? $count+=4 : $count;
    $length == 2 ? $count+=3 : $count;
    $length == 3 ? $count+=2 : $count;
    $length == 4 ? $count+=1 : $count;
    $digitPlaces = array("+13+11", "+23+11", "+33+11", "+43+11", "+53+11");
    $digitPlace = $digitPlaces[$count];
    return $digitPlace;
}
    
function buildDigits($numString) {       // construct imagemagick command
    global $wuPath;
    global $skin;
    $magickArray = array();              // holds imagemagick arguments
    $numLength = strlen($numString);     // get length of string
    $numString = str_split($numString);  // break into array of characters
    $count = 0;
    foreach ($numString as $char) {      // build filenames depending on characters
        $char == ":" ? $char = "colon"    : $char = $char;
        $char == "-" ? $char = "negative" : $char = $char;
        $char == " " ? $char = "blank"    : $char = $char;
        $magickArray[] = $wuPath
                       . $skin
                       . $char
                       . ".png -geometry "
                       . digitPlace($count,$numLength)
                       . " -composite";
        ++$count;
    }
    return $magickArray;
}

function createComposite($dataArgs, $dataType) {  // create individual image layer
    global $wuPath;
    global $skin;
    
    if ($dataType == "tempF") {
        $dataType = $wuPath . "images/temp/" . "wu002.png";
    } elseif ($dataType == "tempC") {
        $dataType = $wuPath . "images/temp/" . "wu004.png";
    } elseif ($dataType == "time") {
        $dataType = $wuPath . "images/temp/" . "wu006.png";
    }
    // using this naming convention because the way imagemagick compiles sequential filenames and the output we're looking for is:
    // 1-background w/no segments, 2-temp F, 3-background w/segments, 4-temp C, 5-background w/segments, 6-time, 7-background w/segments

    $argString = "";
    foreach ($dataArgs as $dataArg) {
        $argString .= $dataArg . " ";  // append all imagemagick arguments to one string
    }
    $magickCommand = "convert "
                   . $wuPath . $skin . "dim_full.png"
                   . " "
                   . $argString
                   . " "
                   . $dataType;
    return $magickCommand;
}

function copyTempFiles() {
    global $wuPath;
    global $skin;
    $tmpIn = $wuPath.$skin;
    $tmpOut = $wuPath."images/temp/";
    copy($tmpIn."background.png",$tmpOut."wu001.png");
    copy($tmpIn."dim_full.png",$tmpOut."wu003.png");
    copy($tmpIn."dim_full.png",$tmpOut."wu005.png");
    copy($tmpIn."dim_full.png",$tmpOut."wu007.png");
}

function makeGIF() {
    global $wuPath;
    $tmpPath = $wuPath."images/temp/";
    $command = "convert -loop 0 "
             . "-delay 0 " . $tmpPath . "wu001.png "
             . "-delay 200 " . $tmpPath . "wu002.png "
             . "-delay 30 " . $tmpPath . "wu003.png "
             . "-delay 200 " . $tmpPath . "wu004.png "
             . "-delay 30 " . $tmpPath . "wu005.png "
             . "-delay 200 " . $tmpPath . "wu006.png "
             . "-delay 30 " . $tmpPath . "wu007.png "
             . "wu_widget.gif"; // timing is in 100ths of a second
    exec($command);
}

function cleanUp () {  // remove temporary files
    // we'll use absolute paths rather than wildcards just in case
    global $wuPath;
    $tmpPath = $wuPath . "images/temp/";
    $tmpArray = array("wu001.png","wu002.png","wu003.png","wu004.png","wu005.png","wu006.png","wu007.png");
    foreach ($tmpArray as $tmpFile) {
        unlink($tmpPath.$tmpFile);
    }
}

$forecast = array();

$theTime  = getTime();                       // get the time
$timeArgs = buildDigits($theTime);           // construct the imagemagick arguments
$timeComp = createComposite($timeArgs, "time");
exec($timeComp);                             // execute imagemagick

$theTemp  = getTemperature();                // API call. Script assumes this returns Fahrenheit
$celsius  = round(($theTemp - 32) * (5/9));  // convert to Celsius and round it for later
$tempArgs = buildDigits($theTemp . " F");
$tempComp = createComposite($tempArgs, "tempF");
exec($tempComp);
// you can use any API for temp, just make sure the temp is either a float or integer. 
// make $theTemp = your temperature data instead of the getTemperature() function.

$tempArgs = buildDigits($celsius . " C");    // now generate the Celsius image
$tempComp = createComposite($tempArgs, "tempC");
exec($tempComp);

copyTempFiles();
makeGIF();
cleanUp();

if ($save_forecast == true) {
    $file = 'forecast.txt';
    $data = "Temperature: ". $theTemp . "  "
          . "Humidity: "   . $forecast[1] . "  "
          . "Wind speed: " . $forecast[0] . "  "
          . "Feels like: " . $forecast[2] . "  ";
    file_put_contents($file, $data);
}
