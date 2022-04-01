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
use Opus\Config;
use Opus\Document;
use Opus\File;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorFactory;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorInterface;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Generates a PDF file copy which includes an appropriate PDF cover.
 *
 * The generated file copy containing the PDF cover will be cached in a workspace subdirectory and served
 * from this file cache unless a change of document metadata requires generating a new cover for the file.
 */
class DefaultCoverGenerator implements CoverGeneratorInterface
{
    /** @var string Path to a file cache directory */
    private $filecacheDir = "";

    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

    /** @var string Path to a directory that stores template files */
    private $templatesDir = "";

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
     * @param string $filecacheDir
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
     * @param string $tempDir
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
            $templatesDir = APPLICATION_PATH . '/application/configs/covers';
        }

        if (substr($templatesDir, -1) !== DIRECTORY_SEPARATOR) {
            $templatesDir .= DIRECTORY_SEPARATOR;
        }

        return $templatesDir;
    }

    /**
     * Sets the path to a configuration directory that stores template files.
     *
     * @param string $templatesDir
     */
    public function setTemplatesDir($templatesDir)
    {
        $this->templatesDir = $templatesDir;
    }

    /**
     * Returns the file path to a file copy that includes an appropriate cover page.
     * Returns the file's original path if cover generation fails.
     *
     * @param Document $document
     * @param File     $file
     * @return string file path
     */
    public function processFile($document, $file)
    {
        $filePath       = $file->getPath();
        $cachedFilePath = $this->getCachedFilePath($file);

        if ($this->cachedFileExists($document, $file, $cachedFilePath)) {
            return $cachedFilePath;
        }

        $pdfGenerator = $this->getPdfGenerator($document, $file);
        if ($pdfGenerator === null) {
            return $filePath;
        }

        // DEBUG
        $coverPdfData = file_get_contents($this->getFilecacheDir() . 'testcover.pdf'); // DEBUG

        // TODO: use the PdfGenerator instance to create a PDF cover (e.g. from a cover template)
        // $coverPdfData = $pdfGenerator->generate($document);
        if (empty($coverPdfData)) {
            return $filePath;
        }

        $coverPath         = $this->getTempFilePath($file);
        $savedSuccessfully = $this->saveFileData($coverPdfData, $coverPath);
        if (! $savedSuccessfully) {
            return $filePath;
        }

        $mergedPdfData = $this->mergePdfFiles($coverPath, $filePath);

        $savedSuccessfully = $this->saveFileData($mergedPdfData, $cachedFilePath);
        if (! $savedSuccessfully) {
            return $filePath;
        }

        return $cachedFilePath;
    }

    /**
     * Returns true if there's an up-to-date file with a merged cover for the given file in the filecache directory,
     * otherwise returns false.
     *
     * @param Document $document
     * @param File     $file
     * @param string   $cachedFilePath Path to a cached file representing the given file in the filecache directory.
     * @return bool
     */
    protected function cachedFileExists($document, $file, $cachedFilePath)
    {
        // TODO: check if the cached file is up-to-date by comparing its creation date with Document.ServerDateModified

        if (file_exists($cachedFilePath)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the path of the cached file representing the given file in the filecache directory.
     *
     * @param File $file
     * @return string file path
     */
    protected function getCachedFilePath($file)
    {
        $cachedFilename = $this->getCachedFilename($file);
        return $this->getFilecacheDir() . $cachedFilename;
    }

    /**
     * Returns the path of the temp file representing the given file in the temp directory.
     *
     * @param File $file
     * @return string file path
     */
    protected function getTempFilePath($file)
    {
        $tempFilename = $this->getCachedFilename($file);
        return $this->getTempDir() . $tempFilename;
    }

    /**
     * Returns the name of the cached file representing the given file in the filecache directory.
     *
     * @param File $file
     * @return string file name
     */
    protected function getCachedFilename($file)
    {
        // TODO: need to check for empty file name / parent ID values?
        $filePath = $file->getPathName();
        $docId    = $file->getParentId();

        return $docId . '-' . $filePath;
    }

    /**
     * Returns the template name (or path relative to the templates directory) that's appropriate
     * for the given document.
     *
     * @param Document $document
     * @return string|null template name or path relative to templates directory
     */
    protected function getTemplateName($document)
    {
        // TODO: query Config for any `collection.<COLLECTIONID>.cover = <TEMPLATENAME>` setting(s)
        // TODO: to find the appropriate cover template to be used for a given document / collection ID

        // TODO: walk the collection tree up to its root and use the first template that has a matching config setting

        // TODO: handle document belonging to two collections for which different cover templates have been specified

        // DEBUG
        return 'ifa/iccra-cover-template.md'; // DEBUG
    }

    /**
     * Returns the absolute path to the template file to be used for the given document.
     *
     * @param Document $document
     * @return string|null absolute path to template file
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
            return null;
        }

        return $templatePath;
    }

    /**
     * Returns a PDF generator instance to create a cover for the given document and file.
     *
     * @param Document $document
     * @param File     $file
     * @return PdfGeneratorInterface|null
     */
    protected function getPdfGenerator($document, $file)
    {
        // TODO: support more template format(s) and PDF engine(s) via different PdfGeneratorInterface implementation(s)

        $templatePath = $this->getTemplatePath($document);

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
            return null;
        }

        $generator->setTemplatePath($templatePath);

        return $generator;
    }

    /**
     * Saves the given file at the given path. Returns true if storage was successful,
     * otherwise returns false.
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
        // TODO: check whether another (better maintained, more compatible?) library could be used for PDF merging

        try {
            $merger = new Merger();
            $merger->addFile($firstFilePath);
            $merger->addFile($secondFilePath);
            $pdfData = $merger->merge();
        } catch (Exception $e) {
            return null;
        }

        return $pdfData;
    }
}
