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

use Opus\Document;
use Opus\File;
use Opus\Pdf\Cover\PdfGenerator\PdfGeneratorInterface;

class DefaultCoverGenerator implements CoverGeneratorInterface
{
    /**
     * Returns the file path to a file copy that includes an appropriate cover page.
     * Returns the file's original path if cover generation fails.
     *
     * @param Document $document
     * @param File     $file
     *
     * @return string file path
     */
    public function processFile($document, $file)
    {
        // TODO: if there's an up-to-date merged PDF for this file in workspace/filecache, return its path
        // TODO: use getPdfGenerator() to get an appropriate PdfGenerator instance
        // TODO: use the PdfGenerator instance to create a PDF cover (e.g. from a cover template)
        // TODO: use saveCover() to save the generated PDF cover to workspace/tmp
        // TODO: use mergeFileWithCover() to merge the PDF file with the PDF cover
        $filePath = "";

        return $filePath;
    }

    /**
     * Returns true if there's an up-to-date file with a merged cover for the given file in workspace/filecache,
     * otherwise returns false.
     *
     * @param File $file
     *
     * @return bool
     */
    protected function fileWithCoverExists($file)
    {
        // TODO: check if there's already an up-to-date merged PDF for this file in workspace/filecache

        return false;
    }

    /**
     * Returns a PDF generator instance to create a cover for the given document and file.
     *
     * @param Document $document
     * @param File     $file
     *
     * @return PdfGeneratorInterface|null
     */
    protected function getPdfGenerator($document, $file)
    {
        // TODO: get a PdfGenerator instance that's appropriate for this document/file

        return null;
    }

    /**
     * Saves the generated cover to workspace/tmp.
     *
     * @param string $coverData
     */
    protected function saveCover($coverData)
    {
        // TODO: save the passed PDF cover data to workspace/tmp
    }

    /**
     * Returns the original file data merged with the cover at the given path.
     *
     * @param File   $file
     * @param string $coverPath Path to saved PDF cover file.
     *
     * @return string file data
     */
    protected function mergeFileWithCover($file, $coverPath)
    {
        // TODO: merge the given PDF file with the PDF cover at the given path
        $fileData = "";

        return $fileData;
    }

    /**
     * Saves the file with merged cover to workspace/filecache.
     *
     * @param string $fileData
     */
    protected function saveFileWithCover($fileData)
    {
        // TODO: save the passed PDF data to workspace/filecache
    }
}
