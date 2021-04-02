<html>
<body>
<form action="Crawler.php" method="post">
    <p>Website: <input type="text" name="website" /></p>
    <input type="submit" name="link" value="Link crawlen" />
    <input type="submit" name="wort" value="Wörter crawlen"/>
</form><br><br>
</body>
</html>

<?php
class Crawler {
    protected $markup = '';
    public $base = '';
    public function __construct($uri) {
        $this->base = $uri;
        $this->markup =$this->getMarkup($uri);

    }
    public function getMarkup($uri) {
        return file_get_contents($uri);
    }
    public function get($type) {
        $method = "_get_{$type}";
        if (method_exists($this, $method)){
            return call_user_func(array($this, $method));
        }
    }
    protected function _get_images() {
        if (!empty($this->markup)){
            preg_match_all('/<img([^>]+)\/>/i', $this->markup, $images);
            return !empty($images[1]) ? $images[1] : FALSE;
        }
    }
    protected function _get_links() {
    if (!empty($this->markup)){
    //preg_match_all('/<a([^>]+)\>(.*?)\<\/a\>/i', $this->markup, $links);
        preg_match_all('/href=\"(.*?)\"/i', $this->markup, $links);
        return !empty($links[1]) ? $links[1] : FALSE;
    }

}
}

function crawlLink(){
    // Verbindung zur Datenbank herstellen
    $mysqli = mysqli_connect('127.0.0.1', 'root', '', 'webcrawler');

    if (!$mysqli){
        die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
    }
    $website = $_POST['website'];

    $sql = "SELECT * FROM links WHERE link = '$website'";
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0){
        echo "<br>Schon in der Datenbank";
    }
    else{
        $sql = "INSERT INTO links (link)
		VALUES ('$website')";
        // Eingegebener Link wird in der Tabelle links gespeichert
        if ($mysqli->query($sql) === FALSE) {
            echo "Fehler: " . $sql . "<br>" . $mysqli->error;
        }
    }
    $websiteStart = $website;

    Crawli($mysqli, $website, $websiteStart);
}

function Crawli($mysqli, $website, $websiteStart){

    $crawl = new Crawler($website);
    $images = $crawl->get('images');
    $links = $crawl->get('links');


    if ($links != null){
        foreach ($links as $l) {
            $firstChr = $l[0];
            if ($firstChr != "h")
                $l = substr($l, 1);

            if (substr($l, 0, 7) != 'http://')
                if ($firstChr != "h")
                    echo "<br>Link: $crawl->base/$l <br>";
                else
                    echo"<br>Link: $l <br>";

            // Alle gefunden Links werden in der Datenbank unterlinks gespeichert
            // Foreign Key zur Tabelle links wird erstellt

            $link = "$crawl->base/$l";
            $sql = "SELECT * FROM unterlinks WHERE unterlink = '$link'";
            $result = $mysqli->query($sql);
            if ($result->num_rows > 0){
                echo "<br>Schon in der Datenbank";
            }
            else{
                if ($firstChr != "h")
                    $sql = "INSERT INTO unterlinks (unterlink, link_id)
                        VALUES ('$link', (select id FROM links where link = '$websiteStart'))";
                else
                    $sql = "INSERT INTO unterlinks (unterlink, link_id)
                        VALUES ('$l', (select id FROM links where link = '$websiteStart'))";

                if ($mysqli->query($sql) === FALSE) {
                    echo "<br>Fehler: " . $sql . "<br>" . $mysqli->error;
                }
            }
        }
        // es werden nur unterlinks nach links untersucht, da sonst die Ausführungszeit massiv ansteigt
        foreach ($links as $l){
            if ($result->num_rows > 0){
                echo "<br>Schon in der Datenbank";
            }
            else{
                if ($firstChr != "h")
                    $sql = "INSERT INTO unterlinks (unterlink, link_id)
                        VALUES ('$link', (select id FROM links where link = '$websiteStart'))";
                else
                    $sql = "INSERT INTO unterlinks (unterlink, link_id)
                        VALUES ('$l', (select id FROM links where link = '$websiteStart'))";

                if ($mysqli->query($sql) === FALSE) {
                    echo "<br>Fehler: " . $sql . "<br>" . $mysqli->error;
                }
                if ($firstChr != "h")
                    Crawli($mysqli, $link, $websiteStart);

            }
        }
    }

}

function crawlWort() {
    // Verbindung zur Datenbank herstellen
    $mysqli = mysqli_connect('127.0.0.1', 'root', '', 'webcrawler');

    if (!$mysqli){
        die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
    }

    $website = $_POST['website'];

    $sql = "SELECT * FROM unterlinks WHERE link_id = (select id FROM links where link = '$website')";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $quelltext = file_get_contents($row["unterlink"]);

            echo $row["unterlink"] . "-------------------------------------------<br>";

            // kürzt den Quelltext das nur der Body übrig bleibt
            $start = strpos($quelltext, '<body>');
            $laenge = strpos($quelltext, '</body>', $start) - $start;

            $teil = substr($quelltext, $start, $laenge);

            // HTML-Tags werden mithilfe eines Regex entfernt
            $pattern = "/<[^>]+>/i";
            $text = preg_replace($pattern, " ", $teil);

            // Leerzeichen am Anfang und Ende löschen
            $text = trim($text);

            // Einezelne Wörter werden in einen Array gespeichert.
            // Dafür wird bei einem Leerzeichen separiert.
            $woerter = explode(" ", $text);

            // Leere Array-Einträge werden gelöscht
            $woerter = array_merge( array_filter($woerter) );
            $anzahl = count ( $woerter );

            // Ausgabe des Arrays
            for ($x = 0; $x < $anzahl; $x++  )
            {
                $wort = trim($woerter[$x]);
                // Zeichen werden entfernt
                $zeichen = array(".", ",", ";", ":", "{", "}", "<", ">", "#", "\"", "&#173;", "&160", "&173");
                $wort = str_replace($zeichen, "", $wort);

                $wort = str_replace("&auml", "ä", $wort);
                $wort = str_replace("&ouml", "ö", $wort);
                $wort = str_replace("&uuml", "ü", $wort);
                $wort = str_replace("&Auml", "Ä", $wort);
                $wort = str_replace("&Ouml", "Ö", $wort);
                $wort = str_replace("&Uuml", "Ü", $wort);
                $wort = str_replace("&szlig", "ß", $wort);

                if (!empty($wort))
                {
                    $sql = "SELECT * FROM woerter WHERE wort = '$wort'";
                    $result = $mysqli->query($sql);
                    if ($result->num_rows > 0){
                    }
                    else{
                        $sql = "INSERT INTO woerter (wort)
		                VALUES ('$wort')";
                        // Eingegebener Link wird in der Tabelle links gespeichert
                        if ($mysqli->query($sql) === FALSE) {
                            echo "Fehler: " . $sql . "<br>" . $mysqli->error;
                        }
                    }

                    $unterlink = $row["unterlink"];
                    $id = $row["id"];
                    echo $id . "<br>";
                    $sql = "INSERT INTO zuordnung (unterlink_id, wort_id)
		                VALUES ((SELECT id FROM unterlinks WHERE id = '$id'), (select id FROM woerter where wort = '$wort'))";
                    if ($mysqli->query($sql) === FALSE) {
                        echo "Fehler: " . $sql . "<br>" . $mysqli->error;
                    }
                }

            }
        }
    }
    else {
        echo "Link nicht in der Datenbank";
    }
}


if(array_key_exists('link',$_POST)){
    crawlLink();
}
if(array_key_exists('wort',$_POST)){
    crawlWort();
}
?>
<html>
<body>
<h2>Webcrawler</h2>
<?php

?>
</body>
</html>