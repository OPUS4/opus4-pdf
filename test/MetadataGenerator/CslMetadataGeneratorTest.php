<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Pdf\MetadataGenerator;

use DateTime;
use Opus\Common\Config;
use Opus\Common\Date;
use Opus\Common\DnbInstitute;
use Opus\Common\Document;
use Opus\Common\DocumentInterface;
use Opus\Common\Identifier;
use Opus\Common\Person;
use Opus\Pdf\MetadataGenerator\CslMetadataGenerator;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function trim;

use const DIRECTORY_SEPARATOR;

class CslMetadataGeneratorTest extends TestCase
{
    /** @var CslMetadataGenerator */
    protected $metadataGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $this->metadataGenerator = new CslMetadataGenerator();
        $this->metadataGenerator->setTempDir(Config::getInstance()->getTempPath());
    }

    public function testGenerateWithArticle()
    {
        $document = $this->getSampleArticle();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('Article-csl.json');
        $cslJsonFixture = trim(file_get_contents($fixturePath));

        $this->assertEquals($cslJsonFixture, $cslJson);
    }

    public function testGenerateWithChapter()
    {
        $document = $this->getSampleChapter();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('Chapter-csl.json');
        $cslJsonFixture = trim(file_get_contents($fixturePath));

        $this->assertEquals($cslJsonFixture, $cslJson);
    }

    public function testGenerateWithEditedBook()
    {
        $document = $this->getSampleEditedBook();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('EditedBook-csl.json');
        $cslJsonFixture = trim(file_get_contents($fixturePath));

        $this->assertEquals($cslJsonFixture, $cslJson);
    }

    public function testGenerateWithDoctoralThesis()
    {
        $document = $this->getSampleDoctoralThesis();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('DoctoralThesis-csl.json');
        $cslJsonFixture = trim(file_get_contents($fixturePath));

        $this->assertEquals($cslJsonFixture, $cslJson);
    }

    /**
     * Returns the full path to the specified fixture file.
     *
     * @param string $fileName The file name (or path relative to the 'test/_files' directory)
     * @return string Path to file.
     */
    private function getFixturePath($fileName)
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Returns a sample Document object representing a fictive academic journal article.
     *
     * @return DocumentInterface Document object representing a fictive article.
     */
    private function getSampleArticle()
    {
        $doc = Document::new();
        $doc->store();

        $doc->setType("article");
        $doc->setLanguage("en");

        $author = Person::new();
        $author->setFirstName('John');
        $author->setLastName('Doe');
        $author->setAcademicTitle('Ph.D.');
        $doc->addPersonAuthor($author);

        $author = Person::new();
        $author->setFirstName('Jane');
        $author->setLastName('Roe');
        $doc->addPersonAuthor($author);

        $author = Person::new();
        $author->setFirstName('Rachel K');
        $author->setLastName('Moe');
        $doc->addPersonAuthor($author);

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('en');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.');
        $abstract->setLanguage('en');

        $doc->setPublishedDate(new Date(new DateTime('2008-01-01')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Journal of Latin Psychoeconomics');
        $parent->setLanguage('en');

        $doc->setPublisherName('Italian Society of Psychoeconomics');
        $doc->setPublisherPlace('Rome');

        $doc->setVolume('11');
        $doc->setIssue('2');
        $doc->setPageFirst(3);
        $doc->setPageLast(4);
        $doc->setPageNumber(2);

        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue('10.5555/12345678');

        $url = Identifier::new();
        $url->setType('url');
        $url->setValue('http://psychoceramics.labs.crossref.org/10.5555-12345678.html');

        $issn = Identifier::new();
        $issn->setType('issn');
        $issn->setValue('5555-1234');

        $ids = [$doi, $url, $issn];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing a fictive chapter within a book that's part of a series.
     *
     * @return DocumentInterface Document object representing a fictive book chapter.
     */
    private function getSampleChapter()
    {
        // TODO how to add the series title aka CSL collection-title ("CRREL Monograph") for this book chapter?

        $doc = Document::new();
        $doc->store();

        $doc->setType("bookpart");
        $doc->setLanguage("en");

        $author = Person::new();
        $author->setFirstName('John');
        $author->setLastName('Doe');
        $doc->addPersonAuthor($author);

        $author = Person::new();
        $author->setFirstName('J A');
        $author->setLastName('Roe');
        $doc->addPersonAuthor($author);

        $author = Person::new();
        $author->setFirstName('Rachel K.');
        $author->setLastName('Moe');
        $doc->addPersonAuthor($author);

        $editor = Person::new();
        $editor->setFirstName('Minnie F');
        $editor->setLastName('Hoe');
        $doc->addPersonEditor($editor);

        $editor = Person::new();
        $editor->setFirstName('Winfried');
        $editor->setLastName('Doe');
        $doc->addPersonEditor($editor);

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y-m', '1990-02')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Itaque earum rerum hic tenetur a sapiente delectus – Proceedings of the Latin Psychoeconomics Symposium');
        $parent->setLanguage('en');

        $doc->setPublisherName('Italian Society of Psychoeconomics');
        $doc->setPublisherPlace('Rome');

        $doc->setVolume('22');
        $doc->setIssue('3');
        $doc->setPageFirst(44);
        $doc->setPageLast(49);
        $doc->setPageNumber(6);

        $url = Identifier::new();
        $url->setType('url');
        $url->setValue('http://psychoceramics.labs.crossref.org/10.5555-12345678.html');

        $ids = [$url];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing a fictive edited book that's part of a series.
     *
     * @return DocumentInterface Document object representing a fictive edited book.
     */
    private function getSampleEditedBook()
    {
        $doc = Document::new();
        $doc->store();

        $doc->setType("book");
        $doc->setLanguage("en");

        $editor = Person::new();
        $editor->setFirstName('M. F.');
        $editor->setLastName('Hoe');
        $doc->addPersonEditor($editor);

        $editor = Person::new();
        $editor->setFirstName('Winfried');
        $editor->setLastName('Doe');
        $doc->addPersonEditor($editor);

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y', '1994')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Itaque earum rerum hic tenetur a sapiente delectus');
        $parent->setLanguage('en');

        $doc->setPublisherName('Italian Society of Psychoeconomics');
        $doc->setPublisherPlace('Rome');

        $doc->setVolume('33');

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing a fictive doctoral thesis.
     *
     * @return DocumentInterface Document object representing a fictive doctoral thesis.
     */
    private function getSampleDoctoralThesis()
    {
        $doc = Document::new();
        $doc->store();

        $doc->setType("doctoralthesis");
        $doc->setLanguage("de");

        $author = Person::new();
        $author->setFirstName('J.');
        $author->setLastName('Doe');
        $doc->addPersonAuthor($author);

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('de');

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y', '1996')));
        $doc->setPublishedYear(1996);

        $parent = $doc->addTitleParent();
        $parent->setValue('Berichte zur Psychoökonomie');
        $parent->setLanguage('de');

        $parent = $doc->addTitleParent();
        $parent->setValue('Reports on Psychoeconomics');
        $parent->setLanguage('en');

        $doc->setPublisherName('Deutsche Gesellschaft für Psychoökonomie');
        $doc->setPublisherPlace('Hintertupfing');

        // TODO better way to only create a certain DnbInstitute if it doesn't exist
        $institutes = DnbInstitute::getAll();
        if (! empty($institutes)) {
            $institute = $institutes[0];
        } else {
            $institute = DnbInstitute::new();
            $institute->setName('Universität Hintertupfing');
            $institute->setCity('Hintertupfing');
            $institute->setDepartment('Fachbereich 1 – Psychoökonomie');
            $institute->setAddress('Hintertupfinger Allee 1, 112233 Hintertupfing');
            $institute->setIsGrantor(true);
            $institute->setIsPublisher(true);
        }

        $doc->addThesisGrantor($institute);
        $doc->addThesisPublisher($institute);
        $doc->setThesisYearAccepted(1996);

        $doc->setVolume('44');
        $doc->setPageNumber(202);

        $issn = Identifier::new();
        $issn->setType('issn');
        $issn->setValue('5555-1234');

        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue('10.5555/12345678');

        $url = Identifier::new();
        $url->setType('url');
        $url->setValue('http://psychoceramics.labs.crossref.org/10.5555-12345678.html');

        $ids = [$issn, $doi, $url];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }
}
