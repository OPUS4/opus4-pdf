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
use Opus\Common\Config;
use Opus\Document;
use Opus\Pdf\MetadataGenerator\MetadataGeneratorFactory;
use Opus\Pdf\MetadataGenerator\MetadataGeneratorInterface;
use Pandoc\Pandoc;

use function array_push;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_writable;
use function json_encode;
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

    /** @var MetadataGeneratorInterface|null Metadata generator to create CSL JSON metadata */
    private $metadataGenerator;

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

        // force recreation of the metadata generator using the new temp directory
        $this->metadataGenerator = null;
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
     * Returns the path to the base directory containing the given template file.
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

        $this->metadataGenerator = $this->getMetadataGenerator();
        if ($this->metadataGenerator === null) {
            return null;
        }

        // 1. generate general metadata for the given document in JSON format
        $metadataFilePath = $this->generalMetadataFile($document, $tempFilename);

        // 2. generate citation data for the given document in CSL JSON format
        $cslFilePath = $this->metadataGenerator->generateFile($document, $tempFilename);

        // 3. use Pandoc to replace placeholders in the current template file with appropriate document metadata
        // equivalent shell command:
        //   pandoc {$templatePath} --wrap=preserve --metadata-file={$metadataFilePath} --bibliography={$cslFilePath} \
        //     --template={$templatePath} --variable=images-basepath:{$templateBaseDir} --output={$markdownFilePath}
        $parameters = [];

        // input file
        array_push($parameters, $templatePath);

        // options
        // --wrap is used to preserve the line wrapping from the input files
        array_push($parameters, '--wrap', 'preserve');

        // --metadata-file specifies the path to a JSON file containing the document's general metadata
        array_push($parameters, '--metadata-file', $metadataFilePath);

        // --bibliography specifies an external bibliography file
        array_push($parameters, '--bibliography', $cslFilePath);

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

        // 4. use Pandoc & XeTeX to convert the generated Markdown file to PDF
        // equivalent shell command:
        //   pandoc {$markdownFilePath} --resource-path={$templateBaseDir} --bibliography={$cslFilePath} \
        //     --citeproc --pdf-engine=xelatex --pdf-engine-opt=-output-driver="xdvipdfmx -V 3 -z 0" \
        //     --output={$pdfFilePath}
        $parameters2 = [];

        // input file
        array_push($parameters2, $markdownFilePath);

        // options
        // --resource-path specifies the base path of the `styles` directory which contains the used citation style
        array_push($parameters2, '--resource-path', $templateBaseDir);

        // --bibliography specifies an external bibliography file (see above)
        array_push($parameters2, '--bibliography', $cslFilePath);

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

    /**
     * Creates a JSON file with general metadata for the given document and returns the path to the
     * generated metadata file. Returns null in case of failure.
     *
     * @param Document $document The document for which metadata shall be generated.
     * @param string   $tempFilename The file name (without its file extension) to be used for the
     * generated metadata file. May be empty in which case a default name will be used.
     * @return string|null Path to generated metadata file.
     */
    protected function generalMetadataFile($document, $tempFilename = '')
    {
        if (empty($tempFilename)) {
            $tempFilename = $document->getId() . '-' . uniqid();
        }

        $tempDir = $this->getTempDir();
        if (! is_writable($tempDir)) {
            return null;
        }

        $metadataFilePath = $tempDir . $tempFilename . '-meta.json';

        $documentMetadata = [];

        $publishedDate = $document->getPublishedDate();
        if ($publishedDate !== null) {
            $dateString = $this->metadataGenerator->extendedDateString($publishedDate);
            if ($dateString !== null) {
                $documentMetadata['date-meta'] = $dateString;
            }
        }

        $persons = $document->getPersonAuthor();
        if (empty($persons)) {
            $persons = $document->getPersonEditor();
        }
        $personsString = $this->metadataGenerator->personsString($persons);
        if (! empty($personsString)) {
            $documentMetadata['author-meta'] = $personsString;
        }

        $mainTitle = $document->getMainTitle();
        if (! empty($mainTitle)) {
            $documentMetadata['title'] = $mainTitle->getValue();
        }

        $mainAbstract = $document->getMainAbstract();
        if (! empty($mainAbstract)) {
            $documentMetadata['abstract'] = $mainAbstract->getValue();
        }

        $language = $document->getLanguage();
        if (! empty($language)) {
            $documentMetadata['lang'] = $language;
        }

        $jsonString = json_encode($documentMetadata);

        $result = file_put_contents($metadataFilePath, $jsonString);
        if ($result === false) {
            return null;
        }

        return $metadataFilePath;
    }

    /**
     * Returns a metadata generator instance to create CSL JSON metadata for a document.
     *
     * @return MetadataGeneratorInterface|null
     */
    protected function getMetadataGenerator()
    {
        $generator = $this->metadataGenerator;
        if ($generator !== null) {
            return $generator;
        }

        $metadataFormat = MetadataGeneratorInterface::METADATA_FORMAT_CSL_JSON;
        $generator      = MetadataGeneratorFactory::create($metadataFormat);

        if ($generator === null) {
            return null;
        }

        $generator->setTempDir($this->getTempDir());

        $this->metadataGenerator = $generator;

        return $generator;
    }
}
