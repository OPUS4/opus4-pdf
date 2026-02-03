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

use Opus\Common\Config;
use Opus\Common\ConfigTrait;
use Opus\Common\Cover\CoverGeneratorInterface;
use Opus\Common\DocumentInterface;
use Opus\Common\FileInterface;
use Opus\Common\LoggingTrait;
use Opus\Common\Util\ClassLoaderHelper;
use Opus\Pdf\DoNotUseCoverException;
use Opus\Pdf\PdfConcatenatorInterface;
use Opus\Pdf\TemplateMatcher\CollectionMatcher;
use Opus\Pdf\TemplateMatcher\DefaultMatcher;
use Opus\Pdf\TemplateMatcher\DisableIfEnrichmentMatcher;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function file_exists;
use function filemtime;
use function pathinfo;
use function rtrim;
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
    private $filecacheDir;

    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

    /** @var string Path to a directory that stores template files */
    private $templatesDir = "";

    /** @var string Path to a directory that stores licence logo files */
    private $licenceLogosDir = "";

    /** @var PdfConcatenatorInterface */
    private $pdfConcat;

    /** @var OutputInterface */
    private $output;

    /** @var array|null */
    private $templateMatchers;

    /**
     * Returns the path to a workspace subdirectory that stores cached document files.
     *
     * @return string
     */
    public function getFilecacheDir()
    {
        if (null === $this->filecacheDir) {
            // NOTE getWorkspacePath throws exception if not set (so we do not need to check here)
            $path = Path::join(Config::getInstance()->getWorkspacePath(), 'filecache');
            $this->setFilecacheDir($path);
        }

        // TODO IMPORTANT move to setup code so this won't be executed every time (SHOULD NOT BE HERE)
        $filesystem = new Filesystem();
        if (! $filesystem->exists($path)) {
            $filesystem->mkdir($path);
        }

        return $this->filecacheDir;
    }

    /**
     * Sets the path to a workspace subdirectory that stores cached document files.
     *
     * @param string|null $filecacheDir
     */
    public function setFilecacheDir($filecacheDir)
    {
        if ($filecacheDir !== null) {
            $this->filecacheDir = rtrim($filecacheDir, '/') . DIRECTORY_SEPARATOR;
        } else {
            $this->filecacheDir = null;
        }
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

        $this->getOutput()->writeln(
            'Cover template: ' . $pdfGenerator->getTemplatePath(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $tempFilename = $document->getId(); // TODO better temp filename that is not just a number
        $coverPath    = $pdfGenerator->generateFile($document, $tempFilename);
        if ($coverPath === null) {
            $this->getOutput()->writeln('<error>Could not generate cover</error>');
            $this->getLogger()->err('Couldn\'t generate cover: expected cover path but got null');
            return null;
        }

        $this->getOutput()->writeln(
            'Generated cover file: ' . $coverPath,
            OutputInterface::VERBOSITY_VERBOSE
        );

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
            $this->getLogger()->err('Failed generating PDF cover');
            return $filePath;
        }

        $concatenator = $this->getPdfConcatenator();
        if ($concatenator === null) {
            return $filePath;
        }

        $mergedFilePath = $concatenator->join($coverPath, $filePath, $cachedFilePath);
        if ($mergedFilePath === null) {
            return $filePath;
        }

        return $mergedFilePath; // usually same as $cachedFilePath TODO API changes?
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
        $matchers = $this->getTemplateMatchers();

        $templateName = null;

        try {
            foreach ($matchers as $matcher) {
                $templateName = $matcher->getTemplate($document);
                if ($templateName !== null) {
                    break;
                }
            }
        } catch (DoNotUseCoverException $ex) {
            return null;
        }

        return $templateName;
    }

    /**
     * @return array
     *
     * TODO get list of matchers and their options from configuration
     */
    public function getTemplateMatchers()
    {
        if ($this->templateMatchers === null) {
            $this->templateMatchers = [
                new DisableIfEnrichmentMatcher(),
                new CollectionMatcher(),
                new DefaultMatcher(),
            ];
        }

        return $this->templateMatchers;
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
            $this->getOutput()->writeln('<error>No cover template found</error>');
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
            $this->getOutput()->writeln('<error>Could not create PDF generator</error>');
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
     * @return PdfConcatenatorInterface
     */
    public function getPdfConcatenator()
    {
        if ($this->pdfConcat !== null) {
            return $this->pdfConcat;
        }

        $config = $this->getConfig();

        if (isset($config->pdf->covers->concatClass)) {
            $concatClass = $config->pdf->covers->concatClass;
            if (ClassLoaderHelper::classExists($concatClass)) {
                $this->pdfConcat = new $concatClass();
            } else {
                $this->getLogger()->err("Configured PDF concatenator class does not exist: {$concatClass}");
            }
        } else {
            $this->getLogger()->err("PDF concatenator not configured (pdf.covers.concatClass)");
        }

        return $this->pdfConcat;
    }

    /**
     * @param PdfConcatenatorInterface $concatenator
     * @return $this
     */
    public function setPdfConcatenator($concatenator)
    {
        $this->pdfConcat = $concatenator;
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        if ($this->output === null) {
            $this->output = new NullOutput();
        }

        return $this->output;
    }
}
