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

use Opus\Common\DocumentInterface;
use Opus\Common\FileInterface;

/**
 * Interface for generating a PDF file copy which includes an appropriate PDF cover.
 *
 * The PDF cover should be generated via a PDF generator class that implements PdfGeneratorInterface.
 */
interface CoverGeneratorInterface
{
    /**
     * Returns the path to a workspace subdirectory that stores cached document files.
     *
     * @return string
     */
    public function getFilecacheDir();

    /**
     * Sets the path to a workspace subdirectory that stores cached document files.
     *
     * @param string|null $filecacheDir
     */
    public function setFilecacheDir($filecacheDir);

    /**
     * Returns the path to a workspace subdirectory that stores temporary files.
     *
     * @return string
     */
    public function getTempDir();

    /**
     * Sets the path to a workspace subdirectory that stores temporary files.
     *
     * @param string|null $tempDir
     */
    public function setTempDir($tempDir);

    /**
     * Returns the path to a configuration directory that stores template files.
     *
     * @return string
     */
    public function getTemplatesDir();

    /**
     * Sets the path to a configuration directory that stores template files.
     *
     * @param string|null $templatesDir
     */
    public function setTemplatesDir($templatesDir);

    /**
     * Returns the template name (or path relative to the templates directory) that's appropriate
     * for the given document.
     *
     * @param DocumentInterface $document
     * @return string|null Template name or path relative to templates directory.
     */
    public function getTemplateName($document);

    /**
     * Returns the path to a directory that stores licence logo files, or null if no such
     * directory has been defined.
     *
     * @return string|null
     */
    public function getLicenceLogosDir();

    /**
     * Sets the path to a directory that stores licence logo files.
     *
     * @param string|null $licenceLogosDir
     */
    public function setLicenceLogosDir($licenceLogosDir);

    /**
     * Returns the file path to a file copy that includes an appropriate cover page.
     * Returns the file's original path if no cover needs to be generated or if cover generation fails.
     *
     * @param DocumentInterface $document The document for which a PDF cover shall be generated.
     * @param FileInterface     $file The document's file for which a PDF cover shall be generated.
     * @return string file path
     */
    public function processFile($document, $file);
}
