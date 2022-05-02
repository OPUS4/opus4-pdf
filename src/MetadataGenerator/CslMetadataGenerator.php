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

use Opus\Common\Config;
use Opus\Date;
use Opus\Document;
use Opus\Person;
use Seboettg\CiteData\Csl\Date as CslDate;
use Seboettg\CiteData\Csl\Name as CslName;
use Seboettg\CiteData\Csl\Record as CslRecord;

use function file_put_contents;
use function implode;
use function in_array;
use function is_writable;
use function json_encode;
use function preg_replace;
use function strval;
use function substr;
use function uniqid;

use const DIRECTORY_SEPARATOR;

/**
 * Generates metadata for a document in CSL JSON format.
 *
 * @link https://github.com/citation-style-language/schema#csl-json-schema
 * @link https://citeproc-js.readthedocs.io/en/latest/csl-json/markup.html
 */
class CslMetadataGenerator implements MetadataGeneratorInterface
{
    /** @var string Path to a directory that stores temporary files */
    private $tempDir = "";

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
    }

    /**
     * Creates metadata that are appropriate for the given document and returns the generated data.
     * Returns null in case of failure.
     *
     * @param Document $document The document for which metadata shall be generated.
     * @return string|null Generated metadata.
     */
    public function generate($document)
    {
        // TODO: add support for more CSL properties?
        //     - general: `id`, `contributor` (both not supported by CslRecord?)
        //     - chapter in a book: `container-author` (for the book author)
        //     - chapter in a book in a series: `collection-title` (for the series title)
        //     - book in a series: `collection-number`, `collection-editor` (series info)

        // TODO: add support for more OPUS\Document properties?
        //     - thesis: ThesisGrantor, ThesisDateAccepted

        // generate metadata in CSL JSON format
        $cslRecord = new CslRecord();

        $documentType = $document->getType();
        $cslRecord->setType($this->cslType($documentType));

        $cslRecord->setLanguage($document->getLanguage());

        $mainTitle = $document->getMainTitle();
        if (! empty($mainTitle)) {
            $cslRecord->setTitle($mainTitle->getValue());
        }

        $mainAbstract = $document->getMainAbstract();
        if (! empty($mainAbstract)) {
            $cslRecord->setAbstract($mainAbstract->getValue());
        }

        $cslRecord->setAuthor($this->cslNames($document->getPersonAuthor()));
        $cslRecord->setEditor($this->cslNames($document->getPersonEditor()));
        //$cslRecord->setContributor($this->cslNames($document->getPersonContributor())); // not supported?

        $publishedDateString = null;
        $publishedDate       = $document->getPublishedDate();
        $publishedYear       = $document->getPublishedYear();
        if ($publishedDate !== null) {
            $publishedDateString = $this->extendedDateString($publishedDate);
        } elseif (! empty($publishedYear)) {
            $publishedDateString = strval($publishedYear);
        }
        if ($publishedDateString !== null) {
            $issuedDate = new CslDate();
            $issuedDate->setRaw($publishedDateString);
            $cslRecord->setIssued($issuedDate);
        }

        $cslRecord->setStatus($document->getPublicationState());

        $publisherName  = $document->getPublisherName();
        $publisherPlace = $document->getPublisherPlace();
        if (empty($publisherName)) {
            $institutes = $document->getThesisPublisher();
            if (! empty($institutes)) {
                $publisherName  = $institutes[0]->getName();
                $publisherPlace = $institutes[0]->getCity();
            }
        }
        $cslRecord->setPublisher($publisherName);
        $cslRecord->setPublisherPlace($publisherPlace);

        $parentTitles = $document->getTitleParent();
        if (! empty($parentTitles)) {
            if ($this->documentTypeHasContainer($documentType)) {
                $cslRecord->setContainerTitle($parentTitles[0]->getValue());
            } else {
                $cslRecord->setCollectionTitle($parentTitles[0]->getValue());
            }
        }

        $cslRecord->setEdition($document->getEdition());
        $cslRecord->setVolume($document->getVolume());
        $cslRecord->setIssue($document->getIssue());

        $firstPage = $document->getPageFirst();
        if (! empty($firstPage)) {
            $pageParts = [$firstPage];
            $lastPage  = $document->getPageLast();
            if (! empty($lastPage)) {
                $pageParts[] = $lastPage;
            }

            $cslRecord->setPage(implode('-', $pageParts));
            $cslRecord->setPageFirst($firstPage);
        }

        $cslRecord->setNumberOfPages($document->getPageNumber());

        $dois = $document->getIdentifierDoi();
        if (! empty($dois)) {
            $cslRecord->setDOI($dois[0]->getValue());
        }

        $urls = $document->getIdentifierUrl();
        if (! empty($urls)) {
            $cslRecord->setURL($urls[0]->getValue());
        }

        $isbns = $document->getIdentifierIsbn();
        if (! empty($isbns)) {
            $cslRecord->setISBN($isbns[0]->getValue());
        }

        $issns = $document->getIdentifierIssn();
        if (! empty($issns)) {
            $cslRecord->setISSN($issns[0]->getValue());
        }

        return json_encode([$cslRecord]);
    }

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
    public function generateFile($document, $tempFilename = '')
    {
        $tempDir = $this->getTempDir();
        if (! is_writable($tempDir)) {
            return null;
        }

        if (empty($tempFilename)) {
            $tempFilename = $document->getId() . '-' . uniqid();
        }

        $cslFilePath = $tempDir . $tempFilename . '-csl.json';

        // generate metadata in CSL JSON format
        $cslMetadata = $this->generate($document);
        if ($cslMetadata === null) {
            return null;
        }

        $result = file_put_contents($cslFilePath, $cslMetadata);
        if ($result === false) {
            return null;
        }

        return $cslFilePath;
    }

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
     * @link   https://www.loc.gov/standards/datetime/
     *
     * @param  Date $date The date for which a formatted date string shall be generated.
     * @return string|null Formatted date string or null in case of failure.
     */
    public function extendedDateString($date)
    {
        if ($date === null) {
            return null;
        }

        $dateParts = [];

        $year = $date->getYear();
        if (! empty($year)) {
            $dateParts[] = $year;
        }

        $month = $date->getMonth();
        if (! empty($month)) {
            $dateParts[] = $month;
        }

        $day = $date->getDay();
        if (! empty($day)) {
            $dateParts[] = $day;
        }

        if (empty($dateParts)) {
            return null;
        }

        return implode('-', $dateParts);
    }

    /**
     * Creates and returns a formatted string of person names for the given array of Opus\Person objects.
     *
     * TODO move this function to a more appropriate place
     *
     * @param  Person[] $persons Array of Person objects for which a formatted string shall be created.
     * @param  bool     $shortenFirstNames Specifies whether first name(s) shall be reduced to initials.
     * By default, first names are used without modification.
     * @return string|null Formatted string of person names.
     */
    public function personsString($persons, $shortenFirstNames = false)
    {
        $personStrings = [];

        foreach ($persons as $person) {
            $firstNamesString = $person->getFirstName();
            if ($shortenFirstNames === true) {
                // reduce first name(s) to initial(s) and append period(s) if necessary
                $firstNamesString = preg_replace('/(\w)\w+/', '$1', $firstNamesString);
                $firstNamesString = preg_replace('/(\w)(?!\.)/', '$1.', $firstNamesString);
            }

            $personStrings[] = $firstNamesString . ' ' . $person->getLastName();
        }

        return implode(', ', $personStrings);
    }

    /**
     * Returns true if the given OPUS type describes a part within a container, false if not.
     *
     * @param  string $type The Opus\Document type to be checked.
     * @return bool Whether the type describes a part within a container.
     */
    protected function documentTypeHasContainer($type)
    {
        $docTypesWithContainer = [
            'article',
            'bookpart',
            'conferenceobject',
            'contributiontoperiodical',
            'review',
            'workingpaper',
        ];

        return in_array($type, $docTypesWithContainer);
    }

    /**
     * Returns the CSL type representing the given Opus\Document type.
     *
     * @param  string $type The Opus\Document type which shall be mapped to a CSL type.
     * @return string|null CSL type or null in case no matching type was found.
     */
    protected function cslType($type)
    {
        if (empty($type)) {
            return null;
        }

        // NOTE: some Document type -> CSL type mappings are only approximate
        $cslTypesByDocType = [
            'article'                  => 'article-journal',
            'bachelorthesis'           => 'thesis',
            'book'                     => 'book',
            'bookpart'                 => 'chapter',
            'conferenceobject'         => 'paper-conference',
            'contributiontoperiodical' => 'article',
            'coursematerial'           => 'document', // approximate
            'diplom'                   => 'thesis',
            'doctoralthesis'           => 'thesis',
            'examen'                   => 'thesis',
            'habilitation'             => 'thesis',
            'image'                    => 'graphic', // approximate
            'lecture'                  => 'speech', // approximate
            'magister'                 => 'thesis',
            'masterthesis'             => 'thesis',
            'movingimage'              => 'motion_picture',
            'other'                    => 'document', // approximate
            'periodical'               => 'periodical',
            'periodicalpart'           => 'collection', // approximate
            'preprint'                 => 'manuscript', // approximate
            'report'                   => 'report',
            'review'                   => 'review',
            'sound'                    => 'song', // approximate
            'studythesis'              => 'thesis',
            'workingpaper'             => 'article', // approximate
        ];

        return $cslTypesByDocType[$type] ?? null;
    }

    /**
     * Creates and returns an array of CSL name objects for the given array of Opus\Person objects.
     *
     * @link   https://citeproc-js.readthedocs.io/en/latest/csl-json/markup.html#name-fields
     *
     * @param  Person[] $persons Array of Person objects for which CSL name objects shall be created.
     * @return CslName[]|null Array of CSL name objects or null in case of failure.
     */
    protected function cslNames($persons)
    {
        $cslNames = [];

        foreach ($persons as $person) {
            $cslName = new CslName();
            $cslName->setGiven($person->getFirstName());
            $cslName->setFamily($person->getLastName());

            // NOTE: we omit the academic title since this is usually not wanted as part of a formatted citation
            //$cslName->setSuffix($person->getAcademicTitle());

            $cslNames[] = $cslName;
        }

        if (empty($cslNames)) {
            return null;
        }

        return $cslNames;
    }
}
