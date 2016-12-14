<?php
$mediaTemplate = "templates/media.html";
$mainTemplate = "templates/template.html";

$darkSkyURL = "https://api.darksky.net/forecast/bb4aca5ef7dbbdab6076b348c1fc0246/51.589306,4.775923?lang=nl";
$googleMapsURL = "http://maps.googleapis.com/maps/api/geocode/json?latlng=51.589306,4.775923&sensor=true";


$googleKey = "AIzaSyDJok-ittMxN-LPdwVl15Pc2AIvHKBYxN4";

function useTemplate($templateItem) {
    // open het template bestand
    $fhandle = fopen($templateItem, "r");

    // Lees alle data uit het template bestand en stop deze in een string
    // (via filesize($templatebestand) wordt er voor gezorgt dat het hele bestand wordt gelezen)
    $templateData = fread($fhandle, filesize($templateItem));

    // sluit het bestand (de inhoud is beschikbaar in de variabele $template
    fclose($fhandle);
    return $templateData;
}

function checkAPI($api) {
    // Initialiseer CURL
    $ch = curl_init();

    // Instellen van de CUTRL opties
    curl_setopt($ch, CURLOPT_URL, $api);  // De URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // onderdruk output naar het scherm (Je wilt de ruwe data zoals we die ontvangen van de API niet direct tonen bij de gebruiker op zijn scherm)

    // open de API url en sla de ontvangen data op in een variabele ($apiJSON) Meestal ontvang je de data in JSON formaat maar XML komt ook voor
    $apiJSON = curl_exec($ch);
    $statusinfo = curl_getinfo($ch);
    if($statusinfo['http_code'] != "200") {
        return false;
    }
    curl_close($ch);
    return $apiJSON;
}

function weatherData($jsonData, $mapsData, $template) {
    $currentWeather = $jsonData["currently"];
    $hourlyWeather = $jsonData["hourly"];
    $address = $mapsData["results"][0]["formatted_address"];

    $allData = standardInfo($jsonData, $address);
    $allData = $allData . currentlyInfo($currentWeather);
    $allData = $allData . hourlyInfo($hourlyWeather, $template);

    return $allData;
}

function standardInfo($jsonData, $address) {
    return "
        <h1>".$address."</h1>
        <p>Tijdzone: ".$jsonData["timezone"]."</p>
        <p>Lat: ".$jsonData["latitude"]." Long: ".$jsonData["longitude"]."</p>
    ";
}

function currentlyInfo($jsonData) {
    return "
        <p>Time: ".date('H:i:s, d-m-Y', $jsonData["time"])."</p>
        <p>Summary: ".$jsonData["summary"]."</p>
        <p>Icon: ".$jsonData["icon"]."</p>
        <p>precipIntensity: ".$jsonData["precipIntensity"]."</p>
        <p>precipProbability: ".$jsonData["precipProbability"]."</p>
        <p>temperature: ".round(($jsonData["temperature"]- 32) / 1.8, 1)." ℃</p>
        <p>apparentTemperature: ".round(($jsonData["apparentTemperature"]- 32) / 1.8, 1)." ℃</p>
        <p>dewPoint: ".$jsonData["dewPoint"]."</p>
        <p>humidity: ".$jsonData["humidity"]."</p>
        <p>windSpeed: ".$jsonData["windSpeed"]."</p>
        <p>windBearing: ".$jsonData["windBearing"]."</p>
        <p>cloudCover: ".$jsonData["cloudCover"]."</p>
        <p>pressure: ".$jsonData["pressure"]."</p>
        <p>ozone: ".$jsonData["ozone"]."</p>
        <hr />
    ";
}

function hourlyInfo($jsonData, $template) {
    $hourlyData = " 
        <p>Summary: ".$jsonData["summary"]."</p>
        <p>Icon: ".$jsonData["icon"]."</p><hr />";

    $weatherlist = $jsonData["data"];

    // doorloop de lijst met weersverwachtingen en toon die delen die je wilt tonen/gebruiken (vergelijkbaar met hoe je info uit je database toont)
    foreach ($weatherlist as $weatherinfo) {

        $content = $template;

        $timeWeather = "<p>Time: ".date('H:i:s, d-m-Y', $weatherinfo["time"])."</p>";
        $summary = "
            <p>Summary: ".$weatherinfo["summary"]."</p>
            <p>Icon: ".$weatherinfo["icon"]."</p>
            <p>precipIntensity: ".$weatherinfo["precipIntensity"]."</p>
            <p>precipProbability: ".$weatherinfo["precipProbability"]."</p>
            <p>temperature: ".round(($weatherinfo["temperature"]- 32) / 1.8, 1)." ℃</p>
            <p>apparentTemperature: ".round(($weatherinfo["apparentTemperature"]- 32) / 1.8, 1)." ℃</p>
            <p>dewPoint: ".$weatherinfo["dewPoint"]."</p>
            <p>humidity: ".$weatherinfo["humidity"]."</p>
            <p>windSpeed: ".$weatherinfo["windSpeed"]."</p>
            <p>windBearing: ".$weatherinfo["windBearing"]."</p>
            <p>cloudCover: ".$weatherinfo["cloudCover"]."</p>
            <p>pressure: ".$weatherinfo["pressure"]."</p>
            <p>ozone: ".$weatherinfo["ozone"]."</p>
            <hr />
        ";

        $content = str_replace("###TITEL###", $timeWeather, $content);
        $content = str_replace("###BESCHRIJVING###", $summary, $content);

        $hourlyData = $hourlyData . $content;

        // zoek in het template naar de gemarkeerde delen en vervang deze door de betreffende content
        // (zoek en vervang acties)
    }
    return $hourlyData;
}

// inlezen template (als het bestaat)
if (File_Exists($mediaTemplate)) {
    $template = useTemplate($mediaTemplate);

    if (File_Exists($mainTemplate)) {
        $template2 = useTemplate($mainTemplate);

        // open de API url en sla de ontvangen data op in een variabele ($result) Meestal ontvang je de data in JSON formaat maar XML komt ook voor
        $darkSkyData = checkAPI($darkSkyURL);
        $geoLocation = checkAPI($googleMapsURL);

        if ($darkSkyData != false) {
            if ($geoLocation != false) {
                $darkSkyJSON = json_decode($darkSkyData, true);
                $geoLocationJSON = json_decode($geoLocation, true);
                $verzameling = weatherData($darkSkyJSON, $geoLocationJSON, $template);
            }
            else {
                echo "<p>Geen data gevonden van de google maps api</p>";
            }
        }
        else {
            echo "<p>Geen data gevonden van de weer api</p>";
        }

        $paginaContent = str_replace("###CONTENT###", $verzameling, $template2);
        echo $paginaContent;
    }
    else {
        echo "<p>template overzicht niet gevonden.</p>\n";
    }

}
else {
    echo "<p>template media niet gevonden.</p>\n";
}
?>