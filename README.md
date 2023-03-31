# opus4-pdf

This package provides PDF support in OPUS 4 for instance to generate cover sheets or validate
files.



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

PDF cover sheets will be generated via template files. Note that the included
`demo-cover_template.md` file requires the "Open Sans" (true type or open type) font to
be installed on the system. This font is available under the Apache License v.2.0 in the
[Google Fonts library](https://fonts.google.com/specimen/Open+Sans).


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

By default, OPUS 4 looks for PDF cover templates in the `/application/configs/covers` directory.
You can optionally specify a different directory path via this configuration option:

    pdf.covers.path = APPLICATION_PATH "/application/configs/covers"

OPUS 4 comes with a simple demo cover template which can be used as the base for any custom
template. To use this demo cover template, add this option:

    pdf.covers.default = 'demo-cover_template.md'

If you've created a custom PDF cover template, put this template into the covers directory that
you've specified for `pdf.covers.path`. Then replace the value for the `pdf.covers.default`
option with the template's file name (or path relative to the covers directory if your template
is located in its own sub-directory).

You may optionally specify different cover templates to be used for certain OPUS 4 collections.
To do so, you can map a collection-specific cover template to a certain collection ID:

    collection.12345.cover = 'my-cover_template.md'

Replace `12345` with the actual ID of your collection and `my-cover_template.md` with the
actual name (or path) of your collection-specific cover template (directory).


### Displaying licence logos

Currently, the PDF cover sheet generation process can only make use of image files that are
available locally.

To have OPUS 4 look for licence logos within the `/public/img/licences` directory, add this
configuration option:

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
