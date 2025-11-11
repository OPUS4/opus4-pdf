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

namespace Opus\Pdf\TemplateMatcher;

use Opus\Common\DocumentInterface;
use Opus\Pdf\DoNotUseCoverException;

use function filter_var;
use function is_array;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Prevents cover generation for document if configured enrichment evaluates to true.
 *
 * TODO support just checking for the presence of a value/enrichment (not necessarily "true")
 */
class DisableIfEnrichmentMatcher extends AbstractTemplateMatcher
{
    const DEFAULT_ENRICHMENT_KEY = 'opus_pdf_CoverDisabled';

    /** @var string|null */
    private $enrichmentKey;

    /**
     * @param DocumentInterface $document
     * @return null|string Returns null if enrichment is not set
     * @throws DoNotUseCoverException Thrown when enrichment value evaluates to true (1, "true", "on", ...).
     */
    public function getTemplate($document)
    {
        $enrichmentKey = $this->getEnrichmentKey();

        if ($enrichmentKey === null) {
            return null;
        }

        $enrichments = $document->getEnrichment($enrichmentKey);

        if ($enrichments === null) {
            return null;
        }

        if (! is_array($enrichments)) {
            $enrichments = [$enrichments];
        }

        // Enrichments can have multiple values, the first that evaluates to true disables cover generation
        foreach ($enrichments as $enrichment) {
            if (filter_var($enrichment->getValue(), FILTER_VALIDATE_BOOLEAN)) {
                throw new DoNotUseCoverException();
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getEnrichmentKey()
    {
        $config = $this->getConfig();

        if ($this->enrichmentKey === null) {
            if (isset($config->pdf->covers->disableIfEnrichment)) {
                $value = $config->pdf->covers->disableIfEnrichment;
                if (! empty($value)) {
                    return $value;
                }
            }

            return 'opus_pdf_CoverDisabled';
        }

        return $this->enrichmentKey;
    }

    /**
     * @param string|null $enrichmentKey
     * @return $this
     */
    public function setEnrichmentKey($enrichmentKey)
    {
        $this->enrichmentKey = $enrichmentKey;
        return $this;
    }
}
