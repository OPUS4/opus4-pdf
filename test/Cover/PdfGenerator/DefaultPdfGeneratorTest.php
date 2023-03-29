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
use Opus\Config;
use Opus\Date;
use Opus\Document;
use Opus\Identifier;
use Opus\Licence;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorFactory;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorInterface;
use Opus\Person;
use PHPUnit\Framework\TestCase;

use function dirname;
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

        $licenceLogosDir = dirname($templatePath) . DIRECTORY_SEPARATOR . 'images/';

        $generator->setLicenceLogosDir($licenceLogosDir);

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
     * Returns a sample Document object with fictive metadata representing an academic journal article.
     *
     * @link   https://en.wikipedia.org/wiki/Josiah_S._Carberry#Publications_under_the_name_of_J._S._Carberry
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
        $author->setFirstName('Josiah Stinkney');
        $author->setLastName('Carberry');
        $author->setAcademicTitle('Ph.D.');
        $doc->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('Truman');
        $author->setLastName('Grayson');
        $doc->addPersonAuthor($author);

        $title = $doc->addTitleMain();
        $title->setValue('Toward a Unified Theory of High-Energy Metaphysics: Silly String Theory');
        $title->setLanguage('en');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('The characteristic theme of the works of Stone is the bridge between culture and society. Several narratives concerning the fatal !aw, and subsequent dialectic, of semioticist class may be found. Thus, Debord uses the term ‘the subtextual paradigm of consensus’ to denote a cultural paradox. The subject is interpolated into a neocultural discourse that includes sexuality as a totality. But Marx’s critique of prepatriarchialist nihilism states that consciousness is capable of significance. The main theme of Dietrich’s model of cultural discourse is not construction, but neoconstruction. Thus, any number of narratives concerning the textual paradigm of narrative exist. Pretextual cultural theory suggests that context must come from the collective unconscious.');
        $abstract->setLanguage('en');

        $doc->setPublishedDate(new Date(new DateTime('2008-08-14')));

        $parent = $doc->addTitleParent();
        $parent->setValue('Journal of Psychoceramics');
        $parent->setLanguage('en');

        $doc->setPublisherName('Society of Psychoceramics');
        $doc->setPublisherPlace('Providence, Rhode Island');

        $doc->setVolume('11');
        $doc->setIssue('5');
        $doc->setPageFirst(1);
        $doc->setPageLast(3);
        $doc->setPageNumber(4);

        $doi = new Identifier();
        $doi->setType('doi');
        $doi->setValue('10.5555/12345678');

        $url = new Identifier();
        $url->setType('url');
        $url->setValue('http://psychoceramics.labs.crossref.org/10.5555-12345678.html');

        $issn = new Identifier();
        $issn->setType('issn');
        $issn->setValue('0264-3561');

        $ids = [$doi, $url, $issn];
        $doc->setIdentifier($ids);

        $licence = Licence::fetchByName('CC BY-NC-ND 4.0');
        if ($licence === null) {
            $licence = new Licence();
            $licence->setName('CC BY-NC-ND 4.0');
            $licence->setNameLong('CC BY-NC-ND (Attribution – NonCommercial – NoDerivatives)');
            $licence->setLinkLicence('https://creativecommons.org/licenses/by-nc-nd/4.0');
            $licence->setLinkLogo('https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png');
            $licence->setLanguage('eng');
            $licence->store();
        }

        $doc->setLicence($licence);

        $doc->store();

        return $doc;
    }
}
