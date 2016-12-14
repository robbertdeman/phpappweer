<?php
// In deze file zitten de PHP statements voor het opbouwen van een verbinding met de database
include "db_connection-json.php";

$itemtemplatebestand = "templates/media.html";
$itemtemplatebestand2 = "templates/template.html";

$url="http://api.openweathermap.org/data/2.5/forecast?q=Breda,NL&units=metric&APPID=416237530e321d840c522053903a829f";
$weerURL = "https://api.darksky.net/forecast/bb4aca5ef7dbbdab6076b348c1fc0246/37.8267,-122.4233?exclude=minutly";


function debug_to_console( $data ) {

    if ( is_array( $data ) )
        $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
    else
        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";

    echo $output;
}

// inlezen template (als het bestaat)
if (File_Exists($itemtemplatebestand)) {
    // open het template bestand
    $fhandle = fopen($itemtemplatebestand, "r");

    // Lees alle data uit het template bestand en stop deze in een string
    // (via filesize($templatebestand) wordt er voor gezorgt dat het hele bestand wordt gelezen)
    $template = fread($fhandle, filesize($itemtemplatebestand));

    // sluit het bestand (de inhoud is beschikbaar in de variabele $template
    fclose($fhandle);

    if (File_Exists($itemtemplatebestand2)) {
        // open het template bestand
        $fhandle = fopen($itemtemplatebestand2, "r");

        // Lees alle data uit het template bestand en stop deze in een string
        // (via filesize($templatebestand) wordt er voor gezorgt dat het hele bestand wordt gelezen)
        $template2 = fread($fhandle, filesize($itemtemplatebestand2));

        // sluit het bestand (de inhoud is beschikbaar in de variabele $template
        fclose($fhandle);

        // Initialiseer CURL
        $ch = curl_init();

        // Instellen van de CUTRL opties
        curl_setopt($ch, CURLOPT_URL, $url);  // De URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // onderdruk output naar het scherm (Je wilt de ruwe data zoals we die ontvangen van de API niet direct tonen bij de gebruiker op zijn scherm)

        // open de API url en sla de ontvangen data op in een variabele ($result) Meestal ontvang je de data in JSON formaat maar XML komt ook voor
        $result = curl_exec($ch);

        // Initialiseer CURL
        $ch2 = curl_init();

        // Instellen van de CUTRL opties
        curl_setopt($ch2, CURLOPT_URL, $weerURL);  // De URL
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);  // onderdruk output naar het scherm (Je wilt de ruwe data zoals we die ontvangen van de API niet direct tonen bij de gebruiker op zijn scherm)

        // open de API url en sla de ontvangen data op in een variabele ($result) Meestal ontvang je de data in JSON formaat maar XML komt ook voor
        $result2 = curl_exec($ch2);

        $result_array2 =json_decode($result2,true);


        var_dump($result_array2["minutely"]["data"][0]);
        var_dump($result_array2["minutely"]["data"][1]);
        var_dump($result_array2["minutely"]["data"][2]);
//        var_dump($result_array2["minutely"]["data"]);
//        var_dump($result_array2["minutely"]);
//        var_dump($result_array2);

        // Haal de status header van de CURL aanroep op (Hierin komen de HTTP status codes te staan zoals 200 voor okay en 404 als de URL niet gevonden werd
        $statusinfo = curl_getinfo($ch);

        // Zijn de gegevens opgehaald? HTTP status code 200 geeft aan dat het goed is gegaan.
        if($statusinfo['http_code'] == "200")
        {
            // Converteer de JSON data naar een PHP array (dat werkt fijner om informatie op te vragen)
            $result_array=json_decode($result,true);

            $verzameling = "
                <h1>Het weer in: ".$result_array["city"]["name"]." (".$result_array["city"]["country"].")</h1>
                <p>Locatie gegevens: Latitude: ".$result_array["city"]["coord"]["lat"]." longitude: ".$result_array["city"]["coord"]["lon"]."</p><hr/>
            ";

            // Het weer voor de komende uren (lijst met weersverachtingen per 3 uur) zit per uur in het "list" deel van het $result_array ( $result_array["list"])

            // Sla de lijst met weersverachtingen op in een variabele
            $weatherlist=$result_array["list"];
            // doorloop de lijst met weersverwachtingen en toon die delen die je wilt tonen/gebruiken (vergelijkbaar met hoe je info uit je database toont)
            foreach($weatherlist as $weatherinfo) {

                $content = $template;

                $datum_tijd=$weatherinfo["dt"];
                $tijdVanWeer = "Tijd: ".Date("d-m-Y H:i",$datum_tijd);
                $weerInfo = "
                    Minimum temperatuur: ".$weatherinfo["main"]["temp_min"]."<br/>
                    Maximum temperatuur: ".$weatherinfo["main"]["temp_max"]."<br/>".
                    $weatherinfo["weather"][0]["main"]." - ".$weatherinfo["weather"][0]["description"]."<br/>
                    Luchtvochtigheid: ".$weatherinfo["main"]["humidity"]."<hr/>
                ";

                $content = str_replace("###TITEL###", $tijdVanWeer, $content);
                $content = str_replace("###BESCHRIJVING###", $weerInfo, $content);

                $verzameling = $verzameling.$content;

                // zoek in het template naar de gemarkeerde delen en vervang deze door de betreffende content
                // (zoek en vervang acties)
            }
        } else {
            echo "er is een fout opgetreden";
            // Toon eventuele aanvullende informatie over de fout
            print_r($statusinfo);
        }
        curl_close($ch);

//        // content ophalen
//        try {
//            // SQL voor het ophalen van alle media items. Ik sorteer ze op ID
//            $query = $db->prepare("select id, titel, omschrijving, url, datum from media order by datum");
//            $query->execute();
//            $result = $query->fetchAll(PDO::FETCH_ASSOC);
//            $resultcount = 0;
//
//            // doorloop het resultaat persoon voor persoon
//            foreach ($result as $media) {
//                // Maak een kopie van het template zodat het orgineel in tact blijft
//                $content = $template;
//
//                // zoek in het template naar de gemarkeerde delen en vervang deze door de betreffende content
//                // (zoek en vervang acties)
//                $content = str_replace("###ID###", $media["id"], $content);
//                $content = str_replace("###TITEL###", $media["titel"], $content);
//                $content = str_replace("###BESCHRIJVING###", $media["omschrijving"], $content);
//
//                $resultcount++;
//                // toon het template met de daarin geplaatste content voor deze database rij
//                $verzameling = $verzameling.$content;
//            }
//        } catch (PDOException $e) {
//            echo "Database error: " . $e->getMessage();
//        }

        $paginaContent = str_replace("###CONTENT###", $verzameling, $template2);
        echo $paginaContent;
    } else {
        echo "<p>template overzicht niet gevonden.</p>\n";
    }

} else {
    echo "<p>template media niet gevonden.</p>\n";
}

?>