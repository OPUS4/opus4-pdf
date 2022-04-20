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
use Opus\Date;
use Opus\DnbInstitute;
use Opus\Document;
use Opus\Identifier;
use Opus\Pdf\MetadataGenerator\MetadataGeneratorFactory;
use Opus\Pdf\MetadataGenerator\MetadataGeneratorInterface;
use Opus\Person;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

use const DIRECTORY_SEPARATOR;

class CslMetadataGeneratorTest extends TestCase
{
    /** @var MetadataGeneratorInterface */
    protected $metadataGenerator;

    public function setUp()
    {
        parent::setUp();

        $this->metadataGenerator = $this->getMetadataGenerator();
    }

    public function testGenerateWithArticle()
    {
        $document = $this->getSampleArticle();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('Article-csl.json');
        $cslJsonFixture = file_get_contents($fixturePath);

        $this->assertEquals($cslJson, $cslJsonFixture);
    }

    public function testGenerateWithChapter()
    {
        $document = $this->getSampleChapter();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('Chapter-csl.json');
        $cslJsonFixture = file_get_contents($fixturePath);

        $this->assertEquals($cslJson, $cslJsonFixture);
    }

    public function testGenerateWithEditedBook()
    {
        $document = $this->getSampleEditedBook();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('EditedBook-csl.json');
        $cslJsonFixture = file_get_contents($fixturePath);

        $this->assertEquals($cslJson, $cslJsonFixture);
    }

    public function testGenerateWithDoctoralThesis()
    {
        $document = $this->getSampleDoctoralThesis();
        $cslJson  = $this->metadataGenerator->generate($document);

        $this->assertNotEmpty($cslJson);

        $fixturePath    = $this->getFixturePath('DoctoralThesis-csl.json');
        $cslJsonFixture = file_get_contents($fixturePath);

        $this->assertEquals($cslJson, $cslJsonFixture);
    }

    /**
     * Returns a metadata generator instance to create CSL JSON metadata for a document.
     *
     * @return MetadataGeneratorInterface
     */
    protected function getMetadataGenerator()
    {
        $metadataFormat = MetadataGeneratorInterface::METADATA_FORMAT_CSL_JSON;
        $generator      = MetadataGeneratorFactory::create($metadataFormat);

        $this->assertNotNull($generator);

        $generator->setTempDir(APPLICATION_PATH . '/test/workspace/tmp/');

        return $generator;
    }

    /**
     * Returns the full path to the specified fixture file.
     *
     * @param string $fileName The file name (or path relative to the '_files' directory)
     * @return string Path to file.
     */
    private function getFixturePath($fileName)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Returns a sample Document object representing an academic journal article.
     *
     * @return Document Document object representing an article.
     */
    private function getSampleArticle()
    {
        $doc = new Document();
        $doc->store();

        $doc->setType("article");
        $doc->setLanguage("en");

        $author = new Person();
        $author->setFirstName('Mats Anders');
        $author->setLastName('Granskog');
        $author->setAcademicTitle('Ph.D.');
        $doc->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('Hermanni');
        $author->setLastName('Kaartokallio');
        $doc->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('K');
        $author->setLastName('Shirasawa');
        $doc->addPersonAuthor($author);

        $title = $doc->addTitleMain();
        $title->setValue('Nutrient status of Baltic Sea ice: Evidence for control by snow-ice formation, ice permeability, and ice algae');
        $title->setLanguage('en');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('Samples of land-fast sea ice collected along the Finnish coast of the Baltic Sea, between latitudes 60.2°N and 65.7°N, in January to April 2000 were analyzed for physical, biological, and chemical parameters. Both spatial and temporal variability were investigated. Snow-ice contributed in average a third of the total ice thickness, while the snow fraction (by mass) of the ice was 20% on average. Snow-ice formation increased the nitrogen concentrations substantially, mainly in the upper parts of the ice cover. Phosphorus on the other hand was controlled by biological uptake, with distinct maxima in the bottommost parts of the ice cover. The chlorophyll-a concentrations were dependent on the physical properties of the ice to some extent. In more saline waters the chlorophyll-a concentrations in the ice were variable (1–17 μg l−1). However, in the less saline waters of the Bothnian Bay the concentrations were generally considerably lower (<1 μg l−1) than elsewhere. This is presumably caused by formation of ice of low salinity, due to the low ambient salinity in the area and the under-ice flow of river waters, and formation of ice that has no habitable space for ice algae. Atmospheric nutrients possibly enhance the magnitude of the ice algae bloom, through downward flushing of surface deposited nutrients during periods when the ice was permeable. We surmise that atmospheric supply of nutrients plays an important role in biological productivity within the Baltic Sea ice sheet and potentially also in under-ice waters.');
        $abstract->setLanguage('en');

        $doc->setPublishedDate(new Date(new DateTime('2003-08-09')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Journal of Geophysical Research');
        $parent->setLanguage('en');

        $doc->setPublisherName('Wiley');
        $doc->setPublisherPlace('New Jersey');

        $doc->setVolume('108');
        $doc->setIssue('C8');
        $doc->setPageFirst(3253);
        //$doc->setPageLast(3253);
        $doc->setPageNumber(9);

        $doi = new Identifier();
        $doi->setType('doi');
        $doi->setValue('10.1029/2002JC001386');

        $url = new Identifier();
        $url->setType('url');
        $url->setValue('https://agupubs.onlinelibrary.wiley.com/doi/full/10.1029/2002JC001386');

        $issn = new Identifier();
        $issn->setType('issn');
        $issn->setValue('0148-0227');

        $ids = [$doi, $url, $issn];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing a chapter within a book that's part of a series.
     *
     * @return Document Document object representing a book chapter.
     */
    private function getSampleChapter()
    {
        // TODO: how to add the series title aka CSL collection-title ("CRREL Monograph") for this book chapter?

        $doc = new Document();
        $doc->store();

        $doc->setType("bookpart");
        $doc->setLanguage("en");

        $author = new Person();
        $author->setFirstName('Stephen F.');
        $author->setLastName('Ackley');
        $doc->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('M A');
        $author->setLastName('Lange');
        $doc->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('Peter');
        $author->setLastName('Wadhams');
        $doc->addPersonAuthor($author);

        $editor = new Person();
        $editor->setFirstName('Stephen F.');
        $editor->setLastName('Ackley');
        $doc->addPersonEditor($editor);

        $editor = new Person();
        $editor->setFirstName('Wilford F');
        $editor->setLastName('Weeks');
        $doc->addPersonEditor($editor);

        $title = $doc->addTitleMain();
        $title->setValue('Snow cover effects on Antarctic sea ice thickness');
        $title->setLanguage('en');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('In model simulations of seasonal pack-ice growth, snow cover is treated as insulating layer that inhibits ice growth, but, during field work in 1986, it was found that several factors negate this predicted behaviour. Estimates that snow cover increases sea-ice thickness by 20 to 30% over model predictions by flooding and infiltration mechanisms.');
        $abstract->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y-m', '1990-02')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Sea ice properties and processes – Proceedings of the W.F. Weeks Sea Ice Symposium');
        $parent->setLanguage('en');

        $doc->setPublisherName('U.S. Army Corps of Engineers, Cold Regions Research & Engineering Laboratory');
        $doc->setPublisherPlace('Hanover');

        $doc->setVolume('90');
        $doc->setIssue('1');
        $doc->setPageFirst(16);
        $doc->setPageLast(21);
        $doc->setPageNumber(6);

        $url = new Identifier();
        $url->setType('url');
        $url->setValue('https://www.coldregions.org/vufind/Record/120367');

        $ids = [$url];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing an edited book that's part of a series.
     *
     * @return Document Document object representing an edited book.
     */
    private function getSampleEditedBook()
    {
        $doc = new Document();
        $doc->store();

        $doc->setType("book");
        $doc->setLanguage("en");

        $editor = new Person();
        $editor->setFirstName('A. M.');
        $editor->setLastName('D\'yakonov');
        $doc->addPersonEditor($editor);

        $editor = new Person();
        $editor->setFirstName('A A');
        $editor->setLastName('Strelkov');
        $doc->addPersonEditor($editor);

        $title = $doc->addTitleMain();
        $title->setValue('Ophiuroids of the USSR seas');
        $title->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y', '1954')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Keys to the Fauna of the USSR');
        $parent->setLanguage('en');

        $doc->setPublisherName('Zoological Institute of the Academy of Sciences of the USSR');
        $doc->setPublisherPlace('Moscow');

        $doc->setVolume('55');

        $doc->store();

        return $doc;
    }

    /**
     * Returns a sample Document object representing a doctoral thesis.
     *
     * @return Document Document object representing a doctoral thesis.
     */
    private function getSampleDoctoralThesis()
    {
        $doc = new Document();
        $doc->store();

        $doc->setType("doctoralthesis");
        $doc->setLanguage("de");

        $author = new Person();
        $author->setFirstName('A.');
        $author->setLastName('Bochert');
        $doc->addPersonAuthor($author);

        $title = $doc->addTitleMain();
        $title->setValue('Klassifikation von Radarsatellitendaten zur Meereiserkennung mit Hilfe von Line-Scanner-Messungen');
        $title->setLanguage('de');

        $title = $doc->addTitleMain();
        $title->setValue('Classification of radar satellite data for sea ice identification by means of line scanner measurements');
        $title->setLanguage('en');

        $doc->setPublishedDate(new Date(DateTime::createFromFormat('!Y', '1996')));
        $doc->setPublishedYear(1996);

        $parent = $doc->addTitleParent();
        $parent->setValue('Berichte zur Polarforschung');
        $parent->setLanguage('de');

        $parent = $doc->addTitleParent();
        $parent->setValue('Reports on Polar Research');
        $parent->setLanguage('en');

        $doc->setPublisherName('Alfred-Wegener Institut für Meeres- und Polarforschung');
        $doc->setPublisherPlace('Bremerhaven');

        $institute = new DnbInstitute(1);
        if ($institute === null) {
            $institute = new DnbInstitute();
            $institute->setName('Universität Bremen');
            $institute->setCity('Bremen');
            $institute->setDepartment('Fachbereich 1 – Physik/Elektrotechnik');
            $institute->setAddress('Otto-Hahn-Allee 1, 28359 Bremen');
            $institute->setIsGrantor(true);
            $institute->setIsPublisher(true);
        }

        $doc->addThesisGrantor($institute);
        $doc->addThesisPublisher($institute);
        $doc->setThesisYearAccepted(1996);

        $doc->setVolume('209');
        $doc->setPageNumber(202);

        $issn = new Identifier();
        $issn->setType('issn');
        $issn->setValue('0176-5027');

        $doi = new Identifier();
        $doi->setType('doi');
        $doi->setValue('10.2312/BzP_0209_1996');

        $url = new Identifier();
        $url->setType('url');
        $url->setValue('https://epic.awi.de/id/eprint/26387/');

        $ids = [$issn, $doi, $url];
        $doc->setIdentifier($ids);

        $doc->store();

        return $doc;
    }
}
