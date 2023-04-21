# opus4-pdf

This package provides PDF support in OPUS 4 for instance to generate cover sheets or validate
files.

([Deutsche Dokumentation](LIESMICH.md))


## Requirements

### XeTeX and Pandoc

The opus4-pdf package currently requires [XeTeX](https://xetex.sourceforge.net/) and
[Pandoc](https://pandoc.org/) to generate PDF cover sheets.

In Ubuntu / Debian based Linux systems, these tools can be installed using
`apt` or `apt-get`:

    $ apt-get install texlive-xetex
    $ apt-get install pandoc

In case of Pandoc, please ensure that you install / use at least version 2.17. The current
implementation was not tested against older Pandoc versions.

To check the version of Pandoc that has been installed, run:

    $ pandoc -v


### Fonts

PDF cover sheets will be generated via template files. Note that the included `demo-cover.md`
file requires the "Open Sans" (true type or open type) font to be installed on the system. This
font is available under the Apache License v.2.0 in the
[Google Fonts library](https://fonts.google.com/specimen/Open+Sans). Alternatively, it can be
obtained under the SIL Open Font License 1.1 from
[bunny.net](https://fonts.bunny.net/family/open-sans).

The `Vagrantfile` file gives an example on how fonts can be installed on Ubuntu/Debian-based
Linux systems from the command line.


### Unit tests

In order to run the unit tests the system must also meet the following basic requirements:

- PHP >= 7.1 with PHP support for cURL, DOM and MySQL
- MySQL > 5.1



## Dependencies

Further dependencies are declared in `composer.json` and can be downloaded automatically using 

    composer install
    
or 

    php composer.phar install
    
This will cause the required packages to get downloaded and installed in the `vendor` directory.

The script `bin/install-composer.sh` can be used to automatically download `composer.phar`, so 
the most recent version can be used. [Composer](https://getcomposer.org) is also available in
most Linux distributions. 


## Integration with OPUS 4

Besides the above mentioned requirements, perform the following steps in order to enable PDF cover
sheet generation for your OPUS 4 installation.


### Setting configuration options

By default, PDF cover sheet generation is disabled. To enable PDF cover sheet generation, add the
following configuration options to your application's `config.ini` file:

    pdf.covers.generate = 1

This will cause PDF cover sheets to be added in front of PDF files downloaded via the OPUS 4
frontdoor.

By default, OPUS 4 looks for PDF cover templates in the `application/configs/covers` directory.
You can optionally specify a different directory path via this configuration option:

    pdf.covers.path = APPLICATION_PATH "/application/configs/covers"

This package contains a simple demo cover template in the `test/_files` directory which can be used
as the base for any custom template. To use this demo cover template, put this template into the
covers directory that you've specified for `pdf.covers.path`, and add this option:

    pdf.covers.default = 'demo-cover.md'

If you've created a custom PDF cover template replace the value for the `pdf.covers.default`
option with your template's file name (or path relative to the covers directory if your template
is located in its own subdirectory).

You may optionally specify different cover templates to be used for certain OPUS 4 collections.
To do so, you can map a collection-specific cover template to a certain collection ID:

    collection.12345.cover = 'my-cover.md'

Replace `12345` with the actual ID of your collection and `my-cover.md` with the actual name
of your collection-specific cover template (or its relative path if it's located within a
subdirectory).


### Displaying licence logos

Currently, the PDF cover sheet generation process can only make use of image files that are
available locally.

By default, the application looks for licence logos within a `public/img/licences` directory,
but you can also specify another directory via the `licences.logos.path` option:

    licences.logos.path = APPLICATION_PATH "/public/img/licences"

Inside the specified licences directory, OPUS 4 expects licence logos at a path that matches
the URL path given for the licence in its database table. For example, if the
`link_logo` column in the OPUS database table `document_licences` contains this logo URL:

    https://licensebuttons.net/l/by-sa/4.0/88x31.png

OPUS 4 would expect the local representation of that licence logo at:

    public/img/licences/l/by-sa/4.0/88x31.png



## Running the unit tests

With [Vagrant](https://www.vagrantup.com/) and [VirtualBox](https://www.virtualbox.org/) installed,
the included `Vagrantfile` can be used to install all requirements and Composer dependencies in a
virtual machine.

The unit tests can then be run via these commands:

    $ cd opus4-pdf
    $ vagrant up
    $ vagrant ssh
    $ composer test



## Creating custom PDF cover templates

In the application, downloaded files that include a cover sheet get cached in the
`workspace/filecache` directory (original files will not be modified). As long as a cached version
exists and the document of the file doesn't change, the cached version will be delivered on
subsequent download requests.

The `opus4` console tool includes a `cover:generate` command which will generate a PDF cover for a
given document ID. This command will always force a rebuild of the cover sheet which can be useful
when developing a custom cover template.

To display the command's help, execute this command on the console:

    bin/opus4 --help cover:generate

In order to generate a PDF cover for a document using the current default template, execute this
command:

    bin/opus4 cover:generate ID

Replace `ID` with the document's actual ID. The generated PDF cover will be written to the current
working directory using the document's ID as its file name. You can use the `--out` option to
specify a different file name, e.g. "cover.pdf":

    bin/opus4 cover:generate --out=cover.pdf ID

Finally, you can use the `--template` option to specify the path to a custom cover template, e.g.:

    bin/opus4 cover:generate --out=cover.pdf --template=./application/configs/covers/my-cover.md ID
