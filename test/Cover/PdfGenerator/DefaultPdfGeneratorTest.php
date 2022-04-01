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

use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorFactory;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorInterface;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function strlen;
use function substr;
use function unlink;

use const APPLICATION_PATH;
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

        $templatePath = $this->getTemplatePath('ifa' . DIRECTORY_SEPARATOR . 'ifa-cover_template.md');

        $this->assertFileExists($templatePath);

        $generator->setTemplatePath($templatePath);

        // TODO: fix issue if setTempDir() is NOT used: Opus\Exception: Workspace path not found in configuration
        $generator->setTempDir(APPLICATION_PATH . '/test/workspace/tmp/');

        // TODO: provide a proper Document containing metadata (like author, title, abstract, etc)
        $pdfFilePath = $generator->generateFile(null, 'ifa-cover');

        // mark output files for deletion
        $this->tempFiles[] = substr($pdfFilePath, 0, strlen($pdfFilePath) - 4) . '.md';
        $this->tempFiles[] = $pdfFilePath;

        $this->assertNotNull($pdfFilePath);
        $this->assertFileExists($pdfFilePath);
    }

    /**
     * Returns the full path to the given template.
     *
     * @param string $templateName The template name (or path relative to the 'covers' directory)
     * @return string Path to template
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
}
