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

namespace OpusTest\Pdf\TemplateMatcher;

use Opus\Common\Document;
use Opus\Common\Enrichment;
use Opus\Pdf\DoNotUseCoverException;
use Opus\Pdf\TemplateMatcher\DisableIfEnrichmentMatcher;
use OpusTest\Pdf\TestAsset\TestCase;

class DisableIfEnrichmentMatcherTest extends TestCase
{
    /** @var DisableIfEnrichmentMatcher */
    private $matcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->matcher = new DisableIfEnrichmentMatcher();
    }

    public function tearDown(): void
    {
        // TODO configuration should be loaded/automatically reset for every test
        //      (currently PHPUnit Bootstrap only executed once)
        $this->adjustConfiguration([
            'pdf' => ['covers' => ['disableIfEnrichment' => null]],
        ]);

        parent::tearDown();
    }

    public function testGetEnrichmentKey()
    {
        $this->assertEquals(DisableIfEnrichmentMatcher::DEFAULT_ENRICHMENT_KEY, $this->matcher->getEnrichmentKey());
    }

    public function testGetEnrichmentKeyConfigured()
    {
        $this->adjustConfiguration([
            'pdf' => ['covers' => ['disableIfEnrichment' => 'customEnrichment']],
        ]);

        $this->assertEquals('customEnrichment', $this->matcher->getEnrichmentKey());
    }

    public function testGetEnrichmentKeyConfiguredEmptyValue()
    {
        $this->adjustConfiguration([
            'pdf' => ['covers' => ['disableIfEnrichment' => '']],
        ]);

        $this->assertEquals(DisableIfEnrichmentMatcher::DEFAULT_ENRICHMENT_KEY, $this->matcher->getEnrichmentKey());
    }

    public function testGetTemplate()
    {
        $matcher = $this->matcher;

        $doc = Document::new();

        $this->assertNull($matcher->getTemplate($doc));

        $enrichment = Enrichment::new();
        $enrichment->setKeyName(DisableIfEnrichmentMatcher::DEFAULT_ENRICHMENT_KEY);
        $enrichment->setValue(0);
        $doc->setEnrichment($enrichment);

        $this->assertNull($matcher->getTemplate($doc));
    }

    public function testGetTemplateThrowException()
    {
        $doc = Document::new();

        $enrichment = Enrichment::new();
        $enrichment->setKeyName(DisableIfEnrichmentMatcher::DEFAULT_ENRICHMENT_KEY);
        $enrichment->setValue(1);
        $doc->setEnrichment($enrichment);

        $this->expectException(DoNotUseCoverException::class);

        $this->matcher->getTemplate($doc);
    }

    public function testGetTemplateCustomEnrichment()
    {
        $this->adjustConfiguration([
            'pdf' => ['covers' => ['disableIfEnrichment' => 'customEnrichment']],
        ]);

        $doc = Document::new();

        $this->assertNull($this->matcher->getTemplate($doc));

        $enrichment = Enrichment::new();
        $enrichment->setKeyName('customEnrichment');
        $enrichment->setValue(1);
        $doc->setEnrichment($enrichment);

        $this->expectException(DoNotUseCoverException::class);

        $this->matcher->getTemplate($doc);
    }
}
