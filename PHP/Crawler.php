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
        $this->markup = $this->getMarkup($uri);
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
$crawl = new Crawler($_POST['website']);
$images = $crawl->get('images');
$links = $crawl->get('links');
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

// Eingegebener Link wird in der Tabelle links gespeichert
$website = $_POST['website'];
$sql = "INSERT INTO links (link)
		VALUES ('$website')";
if ($mysqli->query($sql) === FALSE) {
    echo "Fehler: " . $sql . "<br>" . $mysqli->error;
}

foreach($links as $l) {
    if (substr($l,0,7)!='http://')
        echo "<br>Link: $crawl->base/$l";
        // Alle gefunden Links werden in der Datenbank unterlinks gespeichert
        // Foreign Key zur Tabelle links wird erstellt
        $link = "$crawl->base/$l";
        $sql = "INSERT INTO unterlinks (unterlink, link_id)
                VALUES ('$link', (select id FROM links where link = '$website'))";
        if ($mysqli->query($sql) === FALSE) {
            echo "<br>Fehler: " . $sql . "<br>" . $mysqli->error;
        }
}
?>
</body>
</html>