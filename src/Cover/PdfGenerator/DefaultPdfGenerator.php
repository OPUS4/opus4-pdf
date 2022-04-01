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

namespace Opus\Pdf\Cover\PdfGenerator;

use Exception;
use Opus\Config;
use Opus\Document;
use Pandoc\Pandoc;

use function array_push;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_writable;
use function substr;
use function uniqid;

use const DIRECTORY_SEPARATOR;

/**
 * Generates a PDF for a document based on a template.
 *
 * This default implementation uses pandoc and XeTeX to generate the PDF based on a template file.
 *
 * For an existing instance of this class, the used template can be changed later on in order to achieve
 * a different PDF style.
 */
class DefaultPdfGenerator implements PdfGeneratorInterface
{
    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

    /** @var string Path to the template file to be used for PDF generation */
    private $templatePath = "";

    /** @var Pandoc|null Wrapper for the pandoc shell utility */
    private $pandoc;

    /**
     * Returns the path to a directory that stores temporary files.
     *
     * @return string
     */
    public function getTempDir()
    {
        $tempDir = $this->tempDir;

        if (empty($tempDir)) {
            $tempDir = Config::getInstance()->getTempPath();
        }

        if (substr($tempDir, -1) !== DIRECTORY_SEPARATOR) {
            $tempDir .= DIRECTORY_SEPARATOR;
        }

        return $tempDir;
    }

    /**
     * Sets the path to a directory that stores temporary files.
     *
     * @param string $tempDir
     */
    public function setTempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Returns the path to the template file that's used to generate the PDF.
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * Sets the path to the template file that's used to generate the PDF.
     *
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Returns the path to the base directory containg the given template file.
     *
     * @param string $templatePath The path of the template file whose base directory shall be returned.
     * @return string
     */
    protected function getTemplateBaseDir($templatePath)
    {
        $templateBaseDir = dirname($templatePath);

        if (substr($templateBaseDir, -1) !== DIRECTORY_SEPARATOR) {
            $templateBaseDir .= DIRECTORY_SEPARATOR;
        }

        return $templateBaseDir;
    }

    /**
     * Creates a PDF that's appropriate for the given document and returns the generated PDF data.
     * Returns null in case of failure.
     *
     * @param Document $document The document for which a PDF shall be generated.
     * @param string   $tempFilename The file name (without its file extension) to be used for any
     * temporary file(s) that may be generated during PDF generation. May be empty in which case
     * a default name will be used.
     * @return string|null Generated PDF data.
     */
    public function generate($document, $tempFilename = '')
    {
        $pdfFilePath = $this->generateFile($document, $tempFilename);

        if ($pdfFilePath === null) {
            return null;
        }

        return file_get_contents($pdfFilePath);
    }

    /**
     * Creates a PDF that's appropriate for the given document and returns the path to the generated
     * PDF file. Returns null in case of failure.
     *
     * @param Document $document The document for which a PDF shall be generated.
     * @param string   $tempFilename The file name (without its file extension) to be used for any
     * temporary file(s) that may be generated during PDF generation. May be empty in which case
     * a default name will be used.
     * @return string|null Path to generated PDF file.
     */
    public function generateFile($document, $tempFilename = '')
    {
        $templatePath = $this->getTemplatePath();
        if (! file_exists($templatePath)) {
            return null;
        }

        $templateBaseDir = $this->getTemplateBaseDir($templatePath);

        $tempDir = $this->getTempDir();
        if (! is_writable($tempDir)) {
            return null;
        }

        if (empty($tempFilename)) {
            $tempFilename = $document->getId() . '-' . uniqid();
        }

        $markdownFilePath = $tempDir . $tempFilename . '.md';
        $pdfFilePath      = $tempDir . $tempFilename . '.pdf';

        if (! $this->pandoc) {
            $this->pandoc = new Pandoc();
        }

        // 1. use Pandoc to replace placeholders in the given template file with appropriate document metadata
        $parameters = [];

        // TODO: generate a metadata.yaml file that's appropriate for $document
        $metadataFilePath = '/vagrant/test/Cover/PdfGenerator/_files/covers/ifa/metadata.yaml'; // DEBUG

        // input files
        array_push($parameters, $templatePath, $metadataFilePath);

        // options
        // --wrap is used to preserve the line wrapping from the input files
        array_push($parameters, '--wrap', 'preserve');

        // --bibliography specifies an external bibliography file (note that we include the citation
        //   data directly in the `references` field of the document’s YAML metadata at $metadataFilePath)
        array_push($parameters, '--bibliography', $metadataFilePath);

        // --template specifies a custom template for conversion (the file at $templatePath serves as both,
        //   input file and template file)
        array_push($parameters, '--template', $templatePath);

        // --variable is used to populate the `$images-basepath$` placeholder in the template with the base
        //   path of the `images` directory containing any images/logos
        array_push($parameters, '--variable', 'images-basepath:' . $templateBaseDir);

        // --output specifies that generated output will be written to the given file path
        array_push($parameters, '--output', $markdownFilePath);

        try {
            $output = $this->pandoc->execute($parameters);
        } catch (Exception $e) {
            // TODO: log exception
            return null;
        }

        if ($output !== true) {
            return null;
        }

        // 2. use Pandoc & XeTeX to convert the generated Markdown file to PDF
        $parameters2 = [];

        // input file
        array_push($parameters2, $markdownFilePath);

        // options
        // --resource-path specifies the base path of the `styles` directory which contains the used citation style
        array_push($parameters2, '--resource-path', $templateBaseDir);

        // --bibliography specifies an external bibliography file (see above)
        array_push($parameters2, '--bibliography', $metadataFilePath);

        // --citeproc causes a formatted citation to be generated from the bibliographic metadata
        array_push($parameters2, '--citeproc');

        // --pdf-engine specifies that XeTeX will be used to generate the PDF (allowing the template to make use of
        //   Unicode characters as well as system fonts)
        array_push($parameters2, '--pdf-engine', 'xelatex');

        // --output specifies that generated output will be written to the given file path
        array_push($parameters2, '--output', $pdfFilePath);

        try {
            $output2 = $this->pandoc->execute($parameters2);
        } catch (Exception $e) {
            // TODO: log exception
            return null;
        }

        if ($output2 !== true) {
            return null;
        }

        return $pdfFilePath;
    }
}
