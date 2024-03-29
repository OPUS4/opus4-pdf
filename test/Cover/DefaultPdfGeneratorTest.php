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

namespace OpusTest\Pdf\Cover;

use DateTime;
use Opus\Common\Config;
use Opus\Common\Date;
use Opus\Common\Document;
use Opus\Common\Identifier;
use Opus\Common\Licence;
use Opus\Common\Person;
use Opus\Pdf\Cover\DefaultPdfGenerator;
use PHPUnit\Framework\TestCase;
use Zend_Config;

use function dirname;
use function file_exists;
use function file_get_contents;
use function strlen;
use function substr;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;

class DefaultPdfGeneratorTest extends TestCase
{
    /** @var array */
    private $tempFiles = [];

    /** @var DefaultPdfGenerator */
    protected $xetexPdfGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $this->xetexPdfGenerator = new DefaultPdfGenerator();
    }

    /**
     * Clean up any files that were registered by tests for deletion.
     */
    public function tearDown(): void
    {
        $this->deleteTempFiles();

        parent::tearDown();
    }

    public function testGenerateFile()
    {
        $templatePath = $this->getFixturePath('demo-cover.md');

        $this->assertFileExists($templatePath);

        $this->xetexPdfGenerator->setTemplatePath($templatePath);

        $this->xetexPdfGenerator->setTempDir(Config::getInstance()->getTempPath());

        $licenceLogosDir = dirname($templatePath) . DIRECTORY_SEPARATOR . 'images/';

        $this->xetexPdfGenerator->setLicenceLogosDir($licenceLogosDir);

        $document = $this->getSampleArticle();

        $pdfFilePath = $this->xetexPdfGenerator->generateFile($document, 'demo-cover');

        $this->assertNotNull($pdfFilePath);

        // mark output files for deletion
        $filePathWithoutExtension = substr($pdfFilePath, 0, strlen($pdfFilePath) - 4);
        $this->tempFiles[]        = $filePathWithoutExtension . '.md';
        $this->tempFiles[]        = $filePathWithoutExtension . '-csl.json';
        $this->tempFiles[]        = $filePathWithoutExtension . '-meta.json';
        $this->tempFiles[]        = $pdfFilePath;

        $this->assertFileExists($pdfFilePath);
    }

    public function testGenerateGeneralMetadataFile()
    {
        $this->xetexPdfGenerator->setConfig(new Zend_Config([
            'name' => 'OPUS 4 Test Repository',
            'url'  => 'https://www.opus-repository.org',
        ]));

        $document = $this->getSampleArticle();

        $metadataFilePath = $this->xetexPdfGenerator->generateGeneralMetadataFile($document);
        $metaJson         = trim(file_get_contents($metadataFilePath));

        $fixturePath     = $this->getFixturePath('Article-meta.json');
        $metaJsonFixture = trim(file_get_contents($fixturePath));

        // mark output files for deletion
        $this->tempFiles[] = $metadataFilePath;

        $this->assertEquals($metaJsonFixture, $metaJson);
    }

    public function testMetadataGenerationFromExistingConfigOptions()
    {
        $this->xetexPdfGenerator->setConfig(new Zend_Config([
            'name' => 'OPUS 4 Test Repository',
            'oai'  => [
                'repository' => [
                    'name' => 'OPUS 4 OAI Repository',
                ],
            ],
        ]));

        $expectedMetadata = [
            'config-name'                => 'OPUS 4 Test Repository',
            'config-oai-repository-name' => 'OPUS 4 OAI Repository',
        ];

        $optionKeys     = ['name', 'oai.repository.name'];
        $configMetadata = $this->getMetadataFromConfig($optionKeys);

        $this->assertEquals($expectedMetadata, $configMetadata);
    }

    public function testMetadataGenerationFromPartlyNonexistingConfigOptions()
    {
        $this->xetexPdfGenerator->setConfig(new Zend_Config([
            'name' => 'OPUS 4 Test Repository',
        ]));

        $expectedMetadata = [
            'config-name' => 'OPUS 4 Test Repository',
        ];

        $optionKeys     = ['name', 'nonexisting.config.option'];
        $configMetadata = $this->getMetadataFromConfig($optionKeys);

        $this->assertEquals($expectedMetadata, $configMetadata);
    }

    public function testMetadataGenerationFromNonexistingConfigOptions()
    {
        $this->xetexPdfGenerator->setConfig(new Zend_Config([
            'oai.baseurl' => '',
        ]));

        $expectedMetadata = [];

        $optionKeys     = ['oai.baseurl', 'nonexisting.config.option'];
        $configMetadata = $this->getMetadataFromConfig($optionKeys);

        $this->assertEquals($expectedMetadata, $configMetadata);
    }

    /**
     * Returns metadata created from Config.ini values for the given Config option keys.
     *
     * @param string[] $optionKeys List of Config option keys.
     * @return string[] List of metadata created from Config.ini values.
     */
    public function getMetadataFromConfig($optionKeys)
    {
        $this->xetexPdfGenerator->setConfigOptionKeys($optionKeys);

        $this->assertEquals($optionKeys, $this->xetexPdfGenerator->getConfigOptionKeys());

        return $this->xetexPdfGenerator->getMetadataFromConfig($optionKeys);
    }

    /**
     * Returns the full path to the specified template or fixture file.
     *
     * @param string $fileName The file name (or path relative to the 'test/_files' directory)
     * @return string Path to file.
     */
    private function getFixturePath($fileName)
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $fileName;
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
     * @return DocumentInterface Document object representing an article.
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

        $title = $doc->addTitleMain();
        $title->setValue('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit');
        $title->setLanguage('en');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.');
        $abstract->setLanguage('en');

        $doc->setPublishedDate(new Date(new DateTime('2008-08-14')));

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

        $licence = Licence::fetchByName('CC BY-NC-ND 4.0');
        if ($licence === null) {
            $licence = Licence::new();
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
