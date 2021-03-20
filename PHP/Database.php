<?php
// Verbindung herstellen
$mysqli = mysqli_connect('127.0.0.1', 'root', '');

if (!$mysqli){
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
}

// Datenbank erstellen
$sql = 'CREATE DATABASE webcrawler';
if (mysqli_query($mysqli, $sql)){
    echo "Datenbank webcrawler erfolgreich erzeugt<br />";
}
else {
    echo "Erzeugung der Datenbank fehlgeschlagen: " . mysqli_error($mysqli) . "<br />";
}

// Verbindung zur Datenbank herstellen
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'webcrawler');

if (!$mysqli){
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error()) . "<br />";
}

// Tabelle links erstellen
$sql = 'CREATE TABLE links(
        id int PRIMARY KEY NOT NULL AUTO_INCREMENT, 
        link varchar(65535))';

if (mysqli_query($mysqli, $sql)){
    echo "Tabelle links erfolgreich erzeugt <br />";
}
else {
    echo 'Fehler: ' . mysqli_error($mysqli) . "<br />";
}

// Tabelle unterlinks erstellen
$sql = 'CREATE TABLE unterlinks(
		id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
		unterlink varchar(65535),
		link_id int,
		FOREIGN KEY (link_id) REFERENCES  links(id)
		)';

if (mysqli_query($mysqli, $sql)){
    echo "Tabelle unterlinks erfolgreich erzeugt <br />";
}
else {
    echo 'Fehler: ' . mysqli_error($mysqli) . "<br />";
}

// Tabelle woerter erstellen
$sql = 'CREATE TABLE woerter(
		id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
		wort varchar(65535)
		)';

if (mysqli_query($mysqli, $sql)){
    echo "Tabelle woerter erfolgreich erzeugt <br />";
}
else {
    echo 'Fehler: ' . mysqli_error($mysqli) . "<br />";
}

// Tabelle zuordnung erstellen
$sql = 'CREATE TABLE zuordnung(
		id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
		unterlink_id int,
		wort_id int,
		FOREIGN KEY (unterlink_id) REFERENCES  unterlinks(id),
		FOREIGN KEY (wort_id) REFERENCES  woerter(id)
		)';

if (mysqli_query($mysqli, $sql)){
    echo "Tabelle zuordnung erfolgreich erzeugt <br />";
}
else {
    echo 'Fehler: ' . mysqli_error($mysqli) . "<br />";
}
