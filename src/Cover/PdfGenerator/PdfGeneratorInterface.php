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

use Opus\Common\Document;

/**
 * Interface for generating a PDF for a document based on a template.
 *
 * Different implementations of this interface may use different tool chains to generate the PDF.
 */
interface PdfGeneratorInterface
{
    // TODO: add constants for other supported template formats & PDF engines
    const TEMPLATE_FORMAT_MARKDOWN = 'markdown';
    const PDF_ENGINE_XELATEX       = 'xelatex';

    /**
     * Returns the path to a directory that stores temporary files.
     *
     * @return string
     */
    public function getTempDir();

    /**
     * Sets the path to a directory that stores temporary files.
     *
     * @param string $tempDir
     */
    public function setTempDir($tempDir);

    /**
     * Returns the path to the template file that's used to generate the PDF.
     *
     * @return string
     */
    public function getTemplatePath();

    /**
     * Sets the path to the template file that's used to generate the PDF.
     *
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath);

    /**
     * Creates a PDF that's appropriate for the given document and returns the generated PDF data.
     * Returns null in case of failure.
     *
     * @param DocumentInterface $document The document for which a PDF shall be generated.
     * @param string            $tempFilename The file name (without its file extension) to be used for any
     *          temporary file(s) that may be generated during PDF generation. May be empty in which case
     *          a default name will be used.
     * @return string|null Generated PDF data.
     */
    public function generate($document, $tempFilename = '');

    /**
     * Creates a PDF that's appropriate for the given document and returns the path to the generated
     * PDF file. Returns null in case of failure.
     *
     * @param DocumentInterface $document The document for which a PDF shall be generated.
     * @param string            $tempFilename The file name (without its file extension) to be used for any
     *          temporary file(s) that may be generated during PDF generation. May be empty in which case
     *          a default name will be used.
     * @return string|null Path to generated PDF file.
     */
    public function generateFile($document, $tempFilename = '');
}
