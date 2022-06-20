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

namespace OpusTest\Pdf\Cover\PdfGenerator;

use DateTime;
use Opus\Common\Config;
use Opus\Date;
use Opus\Document;
use Opus\Identifier;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorFactory;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorInterface;
use Opus\Person;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function strlen;
use function substr;
use function unlink;

use const DIRECTORY_SEPARATOR;

class DefaultPdfGeneratorTest extends TestCase
{
    private $tempFiles = [];

    /**
     * Clean up any files that were registered by tests for deletion.
     */
    public function tearDown()
    {
        $this->deleteTempFiles();

        parent::tearDown();
    }

    public function testGenerateFile()
    {
        $templateFormat = PdfGeneratorInterface::TEMPLATE_FORMAT_MARKDOWN;
        $pdfEngine      = PdfGeneratorInterface::PDF_ENGINE_XELATEX;

        $generator = PdfGeneratorFactory::create($templateFormat, $pdfEngine);

        $this->assertNotNull($generator);

        $templatePath = $this->getTemplatePath('demo' . DIRECTORY_SEPARATOR . 'demo-cover_template.md');

        $this->assertFileExists($templatePath);

        $generator->setTemplatePath($templatePath);

        $generator->setTempDir(Config::getInstance()->getTempPath());

        $document = $this->getSampleArticle();

        $pdfFilePath = $generator->generateFile($document, 'demo-cover');

        // mark output files for deletion
        $filePathWithoutExtension = substr($pdfFilePath, 0, strlen($pdfFilePath) - 4);
        $this->tempFiles[]        = $filePathWithoutExtension . '.md';
        $this->tempFiles[]        = $filePathWithoutExtension . '-csl.json';
        $this->tempFiles[]        = $filePathWithoutExtension . '-meta.json';
        $this->tempFiles[]        = $pdfFilePath;

        $this->assertNotNull($pdfFilePath);
        $this->assertFileExists($pdfFilePath);
    }

    /**
     * Returns the full path to the given template.
     *
     * @param string $templateName The template name (or path relative to the 'covers' directory)
     * @return string Path to template.
     */
    private function getTemplatePath($templateName)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'covers' . DIRECTORY_SEPARATOR . $templateName;
    }

    /**
     * Deletes any files registered by tests for deletion.
     */
    private function deleteTempFiles()
    {
        if (empty($this->tempFiles)) {
            return;
        }

        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
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
        $author->setFirstName('Kunio');
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
}
