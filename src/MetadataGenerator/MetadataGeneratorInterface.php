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

namespace Opus\Pdf\MetadataGenerator;

use Opus\Date;
use Opus\Document;
use Opus\Person;

/**
 * Interface for generating metadata for a document in a bibliographic metadata format.
 *
 * Different implementations of this interface may use different tool chains to generate the metadata.
 */
interface MetadataGeneratorInterface
{
    // TODO add constants for other supported metadata formats
    const METADATA_FORMAT_CSL_JSON = 'csl_json';

    /**
     * Returns the path to a directory that stores temporary files.
     *
     * @return string
     */
    public function getTempDir();

    /**
     * Sets the path to a directory that stores temporary files.
     *
     * @param string|null $tempDir
     */
    public function setTempDir($tempDir);

    /**
     * Creates metadata that are appropriate for the given document and returns the generated data.
     * Returns null in case of failure.
     *
     * @param Document $document The document for which metadata shall be generated.
     * @return string|null Generated metadata.
     */
    public function generate($document);

    /**
     * Creates metadata that are appropriate for the given document and returns the path to the generated
     * metadata file. Returns null in case of failure.
     *
     * @param Document $document The document for which metadata shall be generated.
     * @param string   $tempFilename The file name (without its file extension) to be used for any
     * temporary file(s) that may be generated during metadata generation. May be empty in which case
     * a default name will be used.
     * @return string|null Path to generated metadata file.
     */
    public function generateFile($document, $tempFilename = '');

    /**
     * Creates and returns a formatted date string from the given Opus\Date object according to level 0
     * of the Extended Date/Time Format (EDTF) specification. Depending on the given date, the generated
     * date string has year, month or day precision. Examples:
     *
     *     2021
     *     2021-12
     *     2021-12-31
     *
     * TODO move this function to a more appropriate place
     *
     * @link https://www.loc.gov/standards/datetime/
     *
     * @param  Date $date The date for which a formatted date string shall be generated.
     * @return string|null Formatted date string or null in case of failure.
     */
    public function extendedDateString($date);

    /**
     * Creates and returns a formatted string of person names for the given array of Opus\Person objects.
     *
     * TODO move this function to a more appropriate place
     *
     * @param  Person[] $persons Array of Person objects for which a formatted string shall be created.
     * @return string|null Formatted string of person names.
     */
    public function personsString($persons);
}
