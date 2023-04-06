# opus4-pdf

Dieses Paket stellt Unterstützung für bestimmte PDF-Funktionalitäten in OPUS 4 bereit, z.B. zum
Generieren von PDF-Deckblättern oder um PDF-Dateien zu validieren.

([English documentation](README.md))



## Voraussetzungen

### XeTeX und Pandoc

Das opus4-pdf Paket erfordert derzeit [XeTeX](https://xetex.sourceforge.net/) und
[Pandoc](https://pandoc.org/) zum Generieren von PDF-Deckblättern.

In Ubuntu/Debian-basierten Linux-Systemen können diese Tools mit den Kommandozeilenbefehlen
`apt` bzw. `apt-get` installiert werden:

    $ apt-get install texlive-xetex
    $ apt-get install pandoc

Im Falle von Pandoc stellen Sie bitte sicher, dass Sie mindestens Version 2.17 installieren bzw.
verwenden. Die jetzige Implementierung wurde nicht gegen ältere Pandoc-Versionen getestet.

Um zu überprüfen, welche Version von Pandoc im System installiert ist, können Sie folgenden
Kommandozeilenbefehl ausführen:

    $ pandoc -v


### Schriften

Die PDF-Deckblätter werden über Vorlagendateien generiert. Bitte beachten Sie, dass zur Verwendung
der Vorlagendatei `demo-cover_template.md` die Schriftart "Open Sans" (im True Type oder Open Type
Format) auf dem System installiert sein muss. Diese Schriftart ist unter der Apache-Lizenz v.2.0
in der [Google Fonts-Bibliothek](https://fonts.google.com/specimen/Open+Sans) verfügbar.


### Unit-Tests

Um die Unit-Tests ausführen zu können, muss das System zusätzlich die folgenden grundlegenden
Voraussetzungen erfüllen:

- PHP >= 7.1 mit PHP Unterstützung für cURL, DOM und MySQL
- MySQL > 5.1



## Abhängigkeiten

Weitere Abhängigkeiten sind in der `composer.json` Datei des Pakets deklariert und können
automatisch mit einem der folgenden Kommandozeilenbefehle heruntergeladen werden:

    composer install
    
oder 

    php composer.phar install
    
Dadurch werden die erforderlichen Pakete heruntergeladen und im `vendor` Verzeichnis installiert.

Das `bin/install-composer.sh` Skript kann verwendet werden, um die `composer.phar` Datei automatisch
herunterzuladen und somit deren neueste Version zu verwenden. [Composer](https://getcomposer.org)
ist auch in den meisten Linux-Distributionen verfügbar.


## Integration mit OPUS 4

Neben den oben genannten Voraussetzungen benötigt es noch die im Folgenden beschriebenen
Konfigurationen, um das Generieren von PDF-Deckblättern in Ihrer OPUS 4 Installation zu aktivieren.


### Konfigurationsoptionen einstellen

Standardmäßig ist die Erstellung von PDF-Deckblättern deaktiviert. Um die Generierung von
PDF-Deckblättern zu aktivieren, fügen Sie bitte die folgenden Konfigurationsoptionen in der
`config.ini`-Datei Ihrer OPUS 4 Anwendung hinzu:

    pdf.covers.generate = 1

Dadurch werden PDF-Deckblätter allen PDF-Dateien hinzugefügt, die über die OPUS 4 Oberfläche
heruntergeladen werden.

Standardmäßig sucht OPUS 4 nach PDF-Deckblattvorlagen im Verzeichnis `application/configs/covers`.
Über diese Konfigurationsoption können Sie optional einen anderen Verzeichnispfad angeben:

    pdf.covers.path = APPLICATION_PATH "/application/configs/covers"

OPUS 4 enthält zu Demonstrationszwecken eine simple PDF-Deckblattvorlage, die auch als Grundlage
für die Erstellung eigener Deckblattvorlagen verwendet werden kann. Um diese Demo-Deckblattvorlage
zu verwenden, fügen Sie bitte die folgende Option hinzu:

    pdf.covers.default = 'demo-cover_template.md'

Wenn Sie eine eigene PDF-Deckblattvorlage erstellt haben, legen Sie diese Vorlage bitte in das
Deckblatt-Verzeichnis, welches Sie unter `pdf.covers.path` angegeben haben. Ersetzen Sie dann den
Wert der `pdf.covers.default` Option mit dem Dateinamen Ihrer eigenen Vorlage (bzw., wenn Ihre
Vorlage sich in einem eigenen Unterverzeichnis befindet, mit dem Pfad relativ zum angegebenen
Deckblatt-Verzeichnis).

Optional erlaubt es OPUS 4 auch, für verschiedene OPUS 4-Sammlungen unterschiedliche
Deckblattvorlagen zu verwenden. Dazu können Sie der ID einer bestimmten Sammlung eine
sammlungsspezifische Deckblattvorlage zuordnen:

    collection.12345.cover = 'my-cover_template.md'

Ersetzen Sie `12345` durch die tatsächliche ID Ihrer Sammlung und `my-cover_template.md` durch den
tatsächlichen Dateinamen Ihrer sammlungsspezifischen Deckblattvorlage (bzw. durch den relativen Pfad
zu dem Unterverzeichnis innerhalb des Deckblatt-Verzeichnisses, in dem sich Ihre Deckblattvorlage
befindet).


### Anzeigen von Lizenzlogos

Falls Bilddateien in Deckblattvorlagen verwendet werden sollen, so müssen diese derzeit lokal im
System verfügbar sein.

OPUS 4 kann zum Beispiel zu einer mit einem Dokument verknüpften Lizenz ein entsprechendes
Lizenzlogo im PDF-Deckblatt anzeigen. Die folgende Option instruiert OPUS 4, im Verzeichnis
`public/img/licences` nach Lizenzlogo-Dateien zu suchen:

    licences.logos.path = APPLICATION_PATH "/public/img/licences"

Innerhalb des angegebenen Verzeichnisses erwartet OPUS 4 die Lizenzlogo-Dateien in einer
Verzeichnisstruktur, welche der Pfadstruktur der URL entspricht, die für die Lizenz in der
entsprechenden Datenbanktabelle angegeben ist. Angenommen die Spalte `link_logo` in der
OPUS-Datenbanktabelle `document_licences` enthält folgende Logo-URL:

    https://licensebuttons.net/l/by-sa/4.0/88x31.png

Dann erwartet OPUS 4 eine lokale Kopie dieses Lizenzlogos unter folgendem Verzeichnispfad:

    public/img/licences/l/by-sa/4.0/88x31.png



## Ausführen der Unit-Tests

Wenn [Vagrant](https://www.vagrantup.com/) und [VirtualBox](https://www.virtualbox.org/) installiert
sind, kann die `Vagrantfile` Datei verwendet werden, um alle Abhängigkeiten automatisch in einer
virtuelle Maschine zu installieren.

Die Unit-Tests können dann über folgende Kommandozeilenbefehle ausgeführt werden:

    $ cd opus4-pdf
    $ vagrant up
    $ vagrant ssh
    $ composer test