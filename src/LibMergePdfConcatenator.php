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
 * @copyright   Copyright (c) 2025, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Pdf;

use Exception;
use iio\libmergepdf\Merger;
use Opus\Common\LoggingTrait;

use function file_put_contents;

class LibMergePdfConcatenator implements PdfConcatenatorInterface
{
    use LoggingTrait;

    /**
     * @param string $coverPath
     * @param string $documentPath
     * @param string $outputPath
     * @return string|null
     */
    public function join($coverPath, $documentPath, $outputPath)
    {
        try {
            $merger = new Merger();
            $merger->addFile($coverPath);
            $merger->addFile($documentPath);
            $pdfData = $merger->merge();
        } catch (Exception $e) {
            $this->getLogger()->err("Failed merging PDF files: '$e'");
            return null;
        }

        $savedSuccessfully = $this->saveFileData($pdfData, $outputPath);

        if (! $savedSuccessfully) {
            $this->getLogger()->err("Failed saving merged PDF data to cached file $outputPath");
            return null;
        }

        return $outputPath;
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
}
