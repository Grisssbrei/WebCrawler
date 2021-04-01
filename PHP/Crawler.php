<html>
<body>
<form action="Crawler.php" method="post">
    <p>Website: <input type="text" name="website" /></p>
    <p><button type="submit">Abschicken</button></p>
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

?>
<html>
<body>
<h2>Webcrawler</h2>
<?php
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
?>
</body>
</html>