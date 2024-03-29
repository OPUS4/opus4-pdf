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
use iio\libmergepdf\Merger;
use Opus\Common\Collection;
use Opus\Common\Config;
use Opus\Common\ConfigTrait;
use Opus\Common\Cover\CoverGeneratorInterface;
use Opus\Common\DocumentInterface;
use Opus\Common\FileInterface;
use Opus\Common\LoggingTrait;

use function file_exists;
use function file_put_contents;
use function filemtime;
use function pathinfo;
use function substr;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_FILENAME;

/**
 * Generates a PDF file copy which includes an appropriate PDF cover.
 *
 * The generated file copy containing the PDF cover will be cached in a workspace subdirectory and served
 * from this file cache unless a change of document metadata requires generating a new cover for the file.
 */
class DefaultCoverGenerator implements CoverGeneratorInterface
{
    use ConfigTrait;
    use LoggingTrait;

    /** @var string Path to a file cache directory */
    private $filecacheDir = "";

    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

    /** @var string Path to a directory that stores template files */
    private $templatesDir = "";

    /** @var string Path to a directory that stores licence logo files */
    private $licenceLogosDir = "";

    /**
     * Returns the path to a workspace subdirectory that stores cached document files.
     *
     * @return string
     */
    public function getFilecacheDir()
    {
        $filecacheDir = $this->filecacheDir;

        if (empty($filecacheDir)) {
            $filecacheDir = Config::getInstance()->getWorkspacePath() . 'filecache';
        }

        if (substr($filecacheDir, -1) !== DIRECTORY_SEPARATOR) {
            $filecacheDir .= DIRECTORY_SEPARATOR;
        }

        return $filecacheDir;
    }

    /**
     * Sets the path to a workspace subdirectory that stores cached document files.
     *
     * @param string|null $filecacheDir
     */
    public function setFilecacheDir($filecacheDir)
    {
        $this->filecacheDir = $filecacheDir;
    }

    /**
     * Returns the path to a workspace subdirectory that stores temporary files.
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
     * Sets the path to a workspace subdirectory that stores temporary files.
     *
     * @param string|null $tempDir
     */
    public function setTempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Returns the path to a configuration directory that stores template files.
     *
     * @return string
     */
    public function getTemplatesDir()
    {
        $templatesDir = $this->templatesDir;

        if (empty($templatesDir)) {
            $config = $this->getConfig();

            if (isset($config->pdf->covers->path)) {
                $templatesDir = $config->pdf->covers->path;
            }

            if (empty($templatesDir)) {
                $templatesDir = APPLICATION_PATH . '/application/configs/covers';
            }
        }

        if (substr($templatesDir, -1) !== DIRECTORY_SEPARATOR) {
            $templatesDir .= DIRECTORY_SEPARATOR;
        }

        return $templatesDir;
    }

    /**
     * Sets the path to a configuration directory that stores template files.
     *
     * @param string|null $templatesDir
     */
    public function setTemplatesDir($templatesDir)
    {
        $this->templatesDir = $templatesDir;
    }

    /**
     * Returns the path to a directory that stores licence logo files, or null if no such
     * directory has been defined.
     *
     * @return string|null
     */
    public function getLicenceLogosDir()
    {
        $licenceLogosDir = $this->licenceLogosDir;

        if (empty($licenceLogosDir)) {
            $config = $this->getConfig();

            if (isset($config->licences->logos->path)) {
                $licenceLogosDir = $config->licences->logos->path;
            }

            if (empty($licenceLogosDir)) {
                $this->getLogger()->err('Couldn\'t get licence logos directory');
                return null;
            }
        }

        if (substr($licenceLogosDir, -1) !== DIRECTORY_SEPARATOR) {
            $licenceLogosDir .= DIRECTORY_SEPARATOR;
        }

        return $licenceLogosDir;
    }

    /**
     * Sets the path to a directory that stores licence logo files.
     *
     * @param string|null $licenceLogosDir
     */
    public function setLicenceLogosDir($licenceLogosDir)
    {
        $this->licenceLogosDir = $licenceLogosDir;
    }

    /**
     * Returns the file path to a cover file that's generated for the specified document. Returns null if cover
     * generation fails.
     *
     * @param DocumentInterface $document The document for which a cover shall be generated.
     * @param string|null       $templatePath (Optional) The absolute path (or path relative to the templates directory)
     * of the template file to be used. If not given or the path doesn't exist, the default template that's appropriate
     * for the given document will be used.
     * @return string|null File path.
     */
    public function processDocument($document, $templatePath = null)
    {
        $pdfGenerator = $this->getPdfGenerator($document, $templatePath);
        if ($pdfGenerator === null) {
            return null;
        }

        $tempFilename = $document->getId();
        $coverPath    = $pdfGenerator->generateFile($document, $tempFilename);
        if ($coverPath === null) {
            $this->getLogger()->err('Couldn\'t generate cover: expected cover path but got null');
            return null;
        }

        return $coverPath;
    }

    /**
     * Returns the file path to a file copy that includes an appropriate cover page.
     * Returns the file's original path if cover generation fails.
     *
     * @param DocumentInterface $document
     * @param FileInterface     $file
     * @return string File path.
     */
    public function processFile($document, $file)
    {
        $filePath       = $file->getPath();
        $cachedFilePath = $this->getCachedFilePath($file);

        if ($this->cachedFileExists($document, $file, $cachedFilePath)) {
            return $cachedFilePath;
        }

        $pdfGenerator = $this->getPdfGenerator($document);
        if ($pdfGenerator === null) {
            return $filePath;
        }

        $tempFilename = pathinfo($this->getCachedFilename($file), PATHINFO_FILENAME);

        $coverPath = $pdfGenerator->generateFile($document, $tempFilename);

        if ($coverPath === null) {
            $this->getLogger()->err('Couldn\'t generate cover: expected cover path but got null');
            return $filePath;
        }

        $mergedPdfData = $this->mergePdfFiles($coverPath, $filePath);

        $savedSuccessfully = $this->saveFileData($mergedPdfData, $cachedFilePath);
        if (! $savedSuccessfully) {
            $this->getLogger()->err("Couldn't save merged PDF data to cached file $cachedFilePath");
            return $filePath;
        }

        return $cachedFilePath;
    }

    /**
     * Returns true if there's an up-to-date file with a merged cover for the given document & file in the filecache
     * directory, otherwise returns false.
     *
     * @param DocumentInterface $document
     * @param FileInterface     $file
     * @param string            $cachedFilePath Path to a cached file representing the given file in the filecache
     * directory.
     * @return bool
     */
    protected function cachedFileExists($document, $file, $cachedFilePath)
    {
        if (! file_exists($cachedFilePath)) {
            return false;
        }

        $documentModificationDate   = $document->getServerDateModified()->getUnixTimestamp();
        $cachedFileModificationDate = filemtime($cachedFilePath);

        // ignore the cached file if it's not up-to-date
        if ($documentModificationDate > $cachedFileModificationDate) {
            return false;
        }

        return true;
    }

    /**
     * Returns the path of the cached file representing the given file in the filecache directory.
     *
     * @param FileInterface $file
     * @return string File path.
     */
    protected function getCachedFilePath($file)
    {
        $cachedFilename = $this->getCachedFilename($file);
        return $this->getFilecacheDir() . $cachedFilename;
    }

    /**
     * Returns the path of the temp file representing the given file in the temp directory.
     *
     * @param FileInterface $file
     * @return string File path.
     */
    protected function getTempFilePath($file)
    {
        $tempFilename = $this->getCachedFilename($file);
        return $this->getTempDir() . $tempFilename;
    }

    /**
     * Returns the name of the cached file representing the given file in the filecache directory.
     *
     * @param FileInterface $file
     * @return string file name
     */
    protected function getCachedFilename($file)
    {
        // TODO need to check for empty file name / parent ID values?
        $filePath = $file->getPathName();
        $docId    = $file->getParentId();

        return $docId . '-' . $filePath;
    }

    /**
     * Returns the template name (or path relative to the templates directory) that's appropriate
     * for the given document.
     *
     * @param DocumentInterface $document
     * @return string|null Template name or path relative to templates directory.
     */
    public function getTemplateName($document)
    {
        // TODO handle documents belonging to two collections for which different cover templates have been specified

        $docCollections = $document->getCollection();

        foreach ($docCollections as $collection) {
            $templateName = $this->getTemplateNameForCollection($collection);
            if ($templateName !== null) {
                return $templateName;
            }
        }

        $templateName = $this->getDefaultTemplateName();
        if ($templateName !== null) {
            return $templateName;
        }

        return null;
    }

    /**
     * Returns the default template name (or path relative to the templates directory) that has been defined
     * via the `pdf.covers.default` configuration setting. Returns null if no default template was defined.
     *
     * @return string|null Template name or path relative to templates directory.
     */
    protected function getDefaultTemplateName()
    {
        $config       = $this->getConfig();
        $templateName = null;

        if (isset($config->pdf->covers->default)) {
            $templateName = $config->pdf->covers->default;
        }

        return ! empty($templateName) ? $templateName : null;
    }

    /**
     * Returns the first matching template name (or path relative to the templates directory) that has been defined
     * for the given collection or any of its parent collections. Returns null if no matching template was found.
     *
     * @param CollectionInterface $collection Document collection for which a matching template shall be found.
     * @return string|null Template name or path relative to templates directory.
     */
    protected function getTemplateNameForCollection($collection)
    {
        $templateId = $this->getTemplateIdForCollectionId($collection->getId());

        // if there's no template for the given collection, check its parent collection
        if ($templateId === null) {
            $parentCollectionId = $collection->getParentNodeId();
            if ($parentCollectionId !== null) {
                $parentCollection = Collection::get($parentCollectionId);
                $templateId       = $this->getTemplateNameForCollection($parentCollection);
            }
        }

        // NOTE: currently, the template ID is identical to the template name
        // TODO in a future implementation, it may be necessary to convert the template ID to a template name

        return $templateId;
    }

    /**
     * Returns the ID of a template that has been defined for the given collection, or null if no template was found.
     *
     * @param int $collectionId ID of a document collection for which a matching template shall be found.
     * @return string|null Template ID.
     */
    protected function getTemplateIdForCollectionId($collectionId)
    {
        // NOTE: The template name/rel.path <-> collection ID mapping is currently defined via a Config setting such as
        //       `collection.<COLLECTION_ID>.cover = '<TEMPLATE_NAME>'`; however, note that this is a temporary measure.
        // NOTE: As a result, the returned template ID is currently identical to the template name and is thus a string
        //       (instead of an int).
        // TODO better implementation of the template name/rel.path <-> collection ID mapping

        $collectionConfig = $this->getConfig()->collection;
        if ($collectionConfig === null) {
            return null;
        }

        $collectionConfigId = $collectionConfig->{$collectionId};
        if ($collectionConfigId === null) {
            return null;
        }

        $templateId = $collectionConfigId->cover;
        if (empty($templateId)) {
            return null;
        }

        return $templateId;
    }

    /**
     * Returns the absolute path to the template file to be used for the given document.
     *
     * @param DocumentInterface $document
     * @return string|null Absolute path to template file.
     */
    protected function getTemplatePath($document)
    {
        $templatesDir = $this->getTemplatesDir();
        $templateName = $this->getTemplateName($document);

        if ($templateName === null) {
            return null;
        }

        $templatePath = $templatesDir . $templateName;

        if (! file_exists($templatePath)) {
            $this->getLogger()->err("Template file doesn't exist: $templatePath");
            return null;
        }

        return $templatePath;
    }

    /**
     * Returns a PDF generator instance to create a cover for the given document.
     *
     * @param DocumentInterface $document The document for which a cover shall be created.
     * @param string|null       $templatePath (Optional) The absolute path (or path relative to the templates directory)
     * of the template file to be used. If not given or the path doesn't exist, the default template that's appropriate
     * for the given document will be used.
     * @return PdfGeneratorInterface|null
     */
    protected function getPdfGenerator($document, $templatePath = null)
    {
        // TODO support more template format(s) and PDF engine(s) via different PdfGeneratorInterface implementation(s)

        if ($templatePath !== null && ! file_exists($templatePath)) {
            // look for given template file in default template directory
            $templatePath = $this->getTemplatesDir() . $templatePath;
        }
        if ($templatePath === null || ! file_exists($templatePath)) {
            // use the document's default template
            $templatePath = $this->getTemplatePath($document);
        }
        if ($templatePath === null) {
            return null;
        }

        // choose an appropriate PDF generator based on the used template
        $markdownFileExtension = '.md';
        $templateFormat        = null;
        $pdfEngine             = null;

        if (substr($templatePath, -3) === $markdownFileExtension) {
            $templateFormat = PdfGeneratorInterface::TEMPLATE_FORMAT_MARKDOWN;
            $pdfEngine      = PdfGeneratorInterface::PDF_ENGINE_XELATEX;
        }

        $generator = PdfGeneratorFactory::create($templateFormat, $pdfEngine);

        if ($generator === null) {
            $this->getLogger()->err("Couldn't create PDF generator for '$templateFormat' and '$pdfEngine'");
            return null;
        }

        $licenceLogosDir = $this->getLicenceLogosDir();
        if ($licenceLogosDir !== null) {
            $generator->setLicenceLogosDir($licenceLogosDir);
        }

        $generator->setTemplatePath($templatePath);
        $generator->setTempDir($this->getTempDir());

        return $generator;
    }

    /**
     * Saves the given file at the given path. Returns true if storage was successful, otherwise returns false.
     *
     * @param string $fileData File data to be stored at the given path.
     * @param string $filePath Path at which the given file data shall be stored.
     * @return bool
     */
    protected function saveFileData($fileData, $filePath)
    {
        $result = file_put_contents($filePath, $fileData);

        return ! ($result === false);
    }

    /**
     * Merges the PDFs at the given file paths and returns the merged PDF data, or null in case of failure.
     *
     * @param string $firstFilePath  Path to PDF file that shall be included first in the merged PDF.
     * @param string $secondFilePath Path to PDF file that shall be appended to the PDF file at $firstFilePath.
     * @return string|null Merged PDF data.
     */
    protected function mergePdfFiles($firstFilePath, $secondFilePath)
    {
        // TODO check whether another (better maintained, more compatible?) library could be used for PDF merging

        try {
            $merger = new Merger();
            $merger->addFile($firstFilePath);
            $merger->addFile($secondFilePath);
            $pdfData = $merger->merge();
        } catch (Exception $e) {
            $this->getLogger()->err("Couldn't merge PDFs: '$e'");
            return null;
        }

        return $pdfData;
    }
}
