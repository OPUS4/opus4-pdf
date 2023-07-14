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

namespace Opus\Pdf\Cover;

use Exception;
use Opus\Common\Config;
use Opus\Common\ConfigTrait;
use Opus\Common\DocumentInterface;
use Opus\Common\LicenceInterface;
use Opus\Common\LoggingTrait;
use Opus\Pdf\MetadataGenerator\CslMetadataGenerator;
use Pandoc\Pandoc;

use function array_merge;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_writable;
use function json_encode;
use function ltrim;
use function parse_url;
use function str_replace;
use function substr;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const PHP_URL_PATH;

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
    use ConfigTrait;
    use LoggingTrait;

    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

    /** @var string Path to the template file to be used for PDF generation */
    private $templatePath = "";

    /** @var string Path to a directory that stores licence logo files */
    private $licenceLogosDir = "";

    /** @var string[] List of configuration option keys whose values will be made available as metadata */
    private $configOptionKeys = [];

    /** @var CslMetadataGenerator|null Metadata generator to create CSL JSON metadata */
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
     * @param string|null $tempDir
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
     * @return string|null
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * Sets the path to the template file that's used to generate the PDF.
     *
     * @param string|null $templatePath
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
     * Returns the path to a directory containing licence logo files to be used when generating the PDF.
     *
     * @return string|null
     */
    public function getLicenceLogosDir()
    {
        return $this->licenceLogosDir;
    }

    /**
     * Sets the path to a directory containing licence logo files to be used when generating the PDF.
     *
     * @param string|null $licenceLogosDir
     */
    public function setLicenceLogosDir($licenceLogosDir)
    {
        $this->licenceLogosDir = $licenceLogosDir;
    }

    /**
     * Returns the list of Config option keys whose values will be made available as metadata during PDF generation.
     *
     * @return string[] List of Config option keys
     */
    public function getConfigOptionKeys()
    {
        $configOptionKeys = $this->configOptionKeys;

        if (empty($configOptionKeys)) {
            $configOptionKeys = ['name', 'url'];
        }

        return $configOptionKeys;
    }

    /**
     * Sets the list of Config option keys whose values shall be made available as metadata during PDF generation.
     *
     * @param string[] $configOptionKeys
     */
    public function setConfigOptionKeys($configOptionKeys)
    {
        $this->configOptionKeys = $configOptionKeys;
    }

    /**
     * Returns the "main" licence of the given document, or null if the document has no license(s) assigned.
     * Note that, currently, this method simply treats the document's first licence as its "main" license.
     *
     * @param DocumentInterface $document
     * @return LicenceInterface|null
     */
    public function getMainLicence($document)
    {
        // TODO #24 better handling of cases where a document contains multiple licences?

        $docLicences = $document->getLicence();

        if (! empty($docLicences)) {
            $docLicence = $docLicences[0];
            return $docLicence->getModel();
        }

        return null;
    }

    /**
     * Returns the file name (or path relative to the licence logos directory) of a licence logo that
     * represents the given licence.
     *
     * @param LicenceInterface $licence
     * @return string|null Licence logo name or path relative to licence logos directory.
     */
    public function getLicenceLogoName($licence)
    {
        $licenceLogoUrl = $licence->getLinkLogo();
        if (empty($licenceLogoUrl)) {
            return null;
        }

        $urlPath = parse_url($licenceLogoUrl, PHP_URL_PATH);
        if (empty($urlPath)) {
            return null;
        }

        // remove any preceding path separator
        $urlPath = ltrim($urlPath, DIRECTORY_SEPARATOR);

        return $urlPath;
    }

    /**
     * Returns the absolute path to the licence logo file to be used for the given licence.
     *
     * @param LicenceInterface $licence
     * @return string|null Absolute path to licence logo file.
     */
    protected function getLicenceLogoPath($licence)
    {
        $licenceLogosDir = $this->getLicenceLogosDir();
        if (empty($licenceLogosDir)) {
            return null;
        }

        $licenceLogoName = $this->getLicenceLogoName($licence);
        if (empty($licenceLogoName)) {
            return null;
        }

        $licenceLogoPath = $licenceLogosDir . $licenceLogoName;

        if (! file_exists($licenceLogoPath)) {
            $this->getLogger()->err("Couldn't find logo for licence '" . $licence->getName() . "' at '$licenceLogoPath'");

            return null;
        }

        return $licenceLogoPath;
    }

    /**
     * Returns metadata created from Config.ini values for the given Config option keys.
     *
     * @param string[] $optionKeys List of Config option keys.
     * @return string[] List of metadata created from Config.ini values.
     */
    public function getMetadataFromConfig($optionKeys)
    {
        $configMetadata = [];

        foreach ($optionKeys as $key) {
            $value = Config::getValueFromConfig($this->getConfig(), $key);
            if (empty($value)) {
                $this->getLogger()->err(__FUNCTION__ . ": Config key '$key' not found or has no value");
            } else {
                $configMetadata['config-' . str_replace('.', '-', $key)] = $value;
            }
        }

        return $configMetadata;
    }

    /**
     * Returns metadata for the given document.
     *
     * @param DocumentInterface $document
     * @return string[] List of document metadata.
     */
    protected function getDocumentMetadata($document)
    {
        $documentMetadata = [];

        $publishedDate = $document->getPublishedDate();
        if ($publishedDate !== null) {
            $dateString = CslMetadataGenerator::extendedDateString($publishedDate);
            if ($dateString !== null) {
                $documentMetadata['date-meta'] = $dateString;
            }
        }

        $persons = $document->getPersonAuthor();
        if (empty($persons)) {
            $persons = $document->getPersonEditor();
        }
        $personsString = CslMetadataGenerator::personsString($persons);
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

        return $documentMetadata;
    }

    /**
     * Returns metadata for the given licence.
     *
     * @param LicenceInterface $licence
     * @return string[] List of licence metadata.
     */
    protected function getLicenceMetadata($licence)
    {
        $licenceMetadata = [];

        $licenceTitle = $licence->getName();
        if (! empty($licenceTitle)) {
            $licenceMetadata['licence-title'] = $licenceTitle;
        }

        $licenceText = $licence->getNameLong();
        if (! empty($licenceText)) {
            $licenceMetadata['licence-text'] = $licenceText;
        }

        $licenceUrl = $licence->getLinkLicence();
        if (! empty($licenceUrl)) {
            $licenceMetadata['licence-url'] = $licenceUrl;
        }

        $licenceLogoPath = $this->getLicenceLogoPath($licence);
        if ($licenceLogoPath !== null) {
            $licenceMetadata['licence-logo-name'] = $this->getLicenceLogoName($licence);
        }

        return $licenceMetadata;
    }

    /**
     * Creates a PDF that's appropriate for the given document and returns the generated PDF data.
     * Returns null in case of failure.
     *
     * @param DocumentInterface $document The document for which a PDF shall be generated.
     * @param string            $tempFilename The file name (without its file extension) to be used for any temporary
     * file(s) that may be generated during PDF generation. May be empty in which case  a default name will be used.
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
     * @param DocumentInterface $document The document for which a PDF shall be generated.
     * @param string            $tempFilename The file name (without its file extension) to be used for any temporary
     * file(s) that may be generated during PDF generation. May be empty in which case a default name will be used.
     * @return string|null Path to generated PDF file.
     */
    public function generateFile($document, $tempFilename = '')
    {
        $templatePath = $this->getTemplatePath();
        if (! file_exists($templatePath)) {
            $this->getLogger()->err("Couldn't generate PDF: missing template at '$templatePath'");

            return null;
        }

        $templateBaseDir = $this->getTemplateBaseDir($templatePath);

        $tempDir = $this->getTempDir();
        if (! is_writable($tempDir)) {
            $this->getLogger()->err("Couldn't generate PDF: temp directory ('$tempDir') is not writable");

            return null;
        }

        $licenceLogosDir = $this->getLicenceLogosDir();

        if (empty($tempFilename)) {
            $tempFilename = $document->getId() . '-' . uniqid();
        }

        $markdownFilePath = $tempDir . $tempFilename . '.md';
        $pdfFilePath      = $tempDir . $tempFilename . '.pdf';

        $this->metadataGenerator = $this->getMetadataGenerator();
        if ($this->metadataGenerator === null) {
            return null;
        }

        // 1. generate general metadata for the given document in JSON format
        $metadataFilePath = $this->generateGeneralMetadataFile($document, $tempFilename);

        // 2. generate citation data for the given document in CSL JSON format
        $cslFilePath = $this->metadataGenerator->generateFile($document, $tempFilename);

        // 3. use Pandoc to replace placeholders in the current template file with appropriate document metadata
        $markdownFilePath = $this->generateMarkdown(
            $templatePath,
            $markdownFilePath,
            $metadataFilePath,
            $cslFilePath,
            $templateBaseDir,
            $licenceLogosDir
        );

        if (empty($markdownFilePath)) {
            $this->getLogger()->err("Couldn't generate PDF: missing Markdown file path");

            return null;
        }

        // 4. use Pandoc & XeTeX to convert the generated Markdown file to PDF
        $pdfFilePath = $this->generatePdfFromMarkdown(
            $markdownFilePath,
            $pdfFilePath,
            $cslFilePath,
            $templateBaseDir
        );

        return $pdfFilePath;
    }

    /**
     * Returns a Pandoc instance (creating it if it doesn't exist yet).
     *
     * @return Pandoc
     */
    protected function getPandoc()
    {
        if (! $this->pandoc) {
            $this->pandoc = new Pandoc();
        }

        return $this->pandoc;
    }

    /**
     * Creates a Markdown file based on the given template and metadata. Returns the path to the generated Markdown file
     * or null in case of failure.
     *
     * @param string      $templatePath Path to a Markdown template file to be used during Markdown generation.
     * @param string      $outFilePath Path at which the generated Markdown output shall be stored.
     * @param string|null $metadataFilePath (Optional) Path to a JSON file containing the document's general metadata.
     * @param string|null $bibFilePath (Optional) Path to an external bibliography file (e.g. in CSL JSON format).
     * @param string|null $imagesDir (Optional) Path to a directory containing image or logo files.
     * @param string|null $licenceLogosDir (Optional) Path to a directory containing license logo files.
     * @return string|null Path to generated Markdown file.
     */
    protected function generateMarkdown($templatePath, $outFilePath, $metadataFilePath, $bibFilePath, $imagesDir, $licenceLogosDir)
    {
        if (! file_exists($templatePath)) {
            $this->getLogger()->err("Couldn't generate Markdown file: missing template at '$templatePath'");

            return null;
        }

        if (empty($outFilePath)) {
            $this->getLogger()->err("Couldn't generate Markdown file: missing output file path");

            return null;
        }

        // use Pandoc to replace placeholders in the current template file with appropriate document metadata
        // equivalent shell command:
        //   pandoc {$templatePath} --wrap=preserve --metadata-file={$metadataFilePath} --bibliography={$bibFilePath} \
        //     --template={$templatePath} --variable=images-basepath:{$imagesDir} \
        //     --variable=licence-logo-basepath:{$licenceLogosDir} --output={$outFilePath}
        $parameters = [];

        // input file
        $content = file_get_contents($templatePath);

        // options
        // --wrap is used to preserve the line wrapping from the input files
        $parameters['wrap'] = 'preserve';

        // --metadata-file specifies the path to a JSON file containing the document's general metadata
        if (! empty($metadataFilePath)) {
            $parameters['metadata-file'] = $metadataFilePath;
        }

        // --bibliography specifies an external bibliography file
        if (! empty($bibFilePath)) {
            $parameters['bibliography'] = $bibFilePath;
        }

        // --template specifies a custom template for conversion (the file at $templatePath serves as both,
        //   input file and template file)
        $parameters['template'] = $templatePath;

        // --variable is used to populate the `$images-basepath$` placeholder in the template with the base
        //   path of the directory containing any images/logos
        if (! empty($imagesDir)) {
            $parameters['variable'] = 'images-basepath:' . $imagesDir;
        }

        // --variable is used to populate the `$licence-logo-basepath$` placeholder in the template with the
        //   path to a directory containing license logos (arranged/named according to https://licensebuttons.net)
        // TODO how to add multiple occurrences of the same key (like `--variable=... --variable=...`)
//        if (! empty($licenceLogosDir)) {
//            $parameters['variable'] = 'licence-logo-basepath:' . $licenceLogosDir;
//        }

        // --output specifies that generated output will be written to the given file path
        $parameters['output'] = $outFilePath;

        $pandoc = $this->getPandoc();

        try {
            $output = $pandoc->runWith($content, $parameters);
        } catch (Exception $e) {
            $this->getLogger()->err("Couldn't generate Markdown file: '$e'");

            return null;
        }

        if ($output !== true) {
            $this->getLogger()->err("Couldn't generate Markdown file: no output from Pandoc");

            return null;
        }

        return $outFilePath;
    }

    /**
     * Creates a PDF from the Markdown file at the given file path and returns the path to the generated
     * PDF file. Returns null in case of failure.
     *
     * @param string      $markdownFilePath Path to a Markdown file that shall be converted to PDF.
     * @param string      $outFilePath Path at which the generated PDF output shall be stored.
     * @param string|null $bibFilePath (Optional) Path to an external bibliography file (e.g. in CSL JSON format).
     * @param string|null $resourceDir (Optional) Path to a resource directory containing the used CSL style.
     * @return string|null Path to generated PDF file.
     */
    protected function generatePdfFromMarkdown($markdownFilePath, $outFilePath, $bibFilePath, $resourceDir)
    {
        if (! file_exists($markdownFilePath)) {
            $this->getLogger()->err("Couldn't generate PDF: missing Markdown file path");

            return null;
        }

        if (empty($outFilePath)) {
            $this->getLogger()->err("Couldn't generate PDF: missing output file path");

            return null;
        }

        // use Pandoc & XeTeX to convert the generated Markdown file to PDF
        // equivalent shell command:
        //   pandoc {$markdownFilePath} --resource-path={$resourceDir} --bibliography={$bibFilePath} \
        //     --citeproc --pdf-engine=xelatex --pdf-engine-opt=-output-driver="xdvipdfmx -V 3 -z 0" \
        //     --output={$outFilePath}
        $parameters = [];

        // input file
        $content = file_get_contents($markdownFilePath);

        // options
        // --resource-path specifies the base path of the directory containing the used citation style
        if (! empty($resourceDir)) {
            $parameters['resource-path'] = $resourceDir;
        }

        // --bibliography specifies an external bibliography file
        if (! empty($bibFilePath)) {
            $parameters['bibliography'] = $bibFilePath;
        }

        // --citeproc causes a formatted citation to be generated from the bibliographic metadata
        if (! empty($bibFilePath)) {
            $parameters['citeproc'] = '';
        }

        // --pdf-engine specifies that XeTeX will be used to generate the PDF (allowing the template to make use of
        //   Unicode characters as well as system fonts)
        $parameters['pdf-engine'] = 'xelatex';

        // --pdf-engine-opt specifies to use PDF version 1.3 without compression
        // NOTE: since this option seems to cause a Pandoc exception when passed through PHP code, we use the header
        //       includes `\special{dvipdfmx:config V 3}\n\special{dvipdfmx:config z 0}` in the template file instead

        // --output specifies that generated output will be written to the given file path
        $parameters['output'] = $outFilePath;

        $pandoc = $this->getPandoc();

        try {
            $output = $pandoc->runWith($content, $parameters);
        } catch (Exception $e) {
            $this->getLogger()->err("Couldn't generate PDF: '$e'");

            return null;
        }

        if ($output !== true) {
            $this->getLogger()->err("Couldn't generate PDF: no output from Pandoc/XeLaTeX");

            return null;
        }

        return $outFilePath;
    }

    /**
     * Creates a JSON file with general metadata for the given document and returns the path to the
     * generated metadata file. Returns null in case of failure.
     *
     * @param DocumentInterface $document The document for which metadata shall be generated.
     * @param string            $tempFilename The file name (without its file extension) to be used
     * for the generated metadata file. May be empty in which case a default name will be used.
     * @return string|null Path to generated metadata file.
     */
    public function generateGeneralMetadataFile($document, $tempFilename = '')
    {
        if (empty($tempFilename)) {
            $tempFilename = $document->getId() . '-' . uniqid();
        }

        $tempDir = $this->getTempDir();
        if (! is_writable($tempDir)) {
            $this->getLogger()->err("Couldn't create JSON metadata: temp directory ('$tempDir') is not writable");

            return null;
        }

        $metadataFilePath = $tempDir . $tempFilename . '-meta.json';

        $metadata = [];

        $configMetadata = $this->getMetadataFromConfig($this->getConfigOptionKeys());
        if (! empty($configMetadata)) {
            $metadata = array_merge($metadata, $configMetadata);
        }

        $documentMetadata = $this->getDocumentMetadata($document);
        if (! empty($documentMetadata)) {
            $metadata = array_merge($metadata, $documentMetadata);
        }

        $licence = $this->getMainLicence($document);
        if ($licence !== null) {
            $licenceMetadata = $this->getLicenceMetadata($licence);
            if (! empty($licenceMetadata)) {
                $metadata = array_merge($metadata, $licenceMetadata);
            }
        }

        $jsonString = json_encode($metadata);

        $result = file_put_contents($metadataFilePath, $jsonString);
        if ($result === false) {
            $this->getLogger()->err("Couldn't write JSON metadata");

            return null;
        }

        return $metadataFilePath;
    }

    /**
     * Returns a metadata generator instance to create CSL JSON metadata for a document.
     *
     * @return CslMetadataGenerator|null
     */
    protected function getMetadataGenerator()
    {
        if ($this->metadataGenerator === null) {
            $generator = new CslMetadataGenerator();
            $generator->setTempDir($this->getTempDir());

            $this->metadataGenerator = $generator;
        }

        return $this->metadataGenerator;
    }
}
