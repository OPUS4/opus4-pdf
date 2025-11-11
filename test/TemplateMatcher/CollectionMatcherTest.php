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

use Opus\Common\Collection;
use Opus\Common\CollectionRole;
use Opus\Common\Document;
use Opus\Pdf\TemplateMatcher\CollectionMatcher;
use OpusTest\Pdf\TestAsset\TestCase;

/**
 * TODO use fixture class to setup collections that can be reused elsewhere
 */
class CollectionMatcherTest extends TestCase
{
    /** @var CollectionMatcher */
    private $matcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->matcher = new CollectionMatcher();
    }

    public function testGetTemplate()
    {
        $doc = Document::new();

        $this->assertNull($this->matcher->getTemplate($doc));
    }

    public function testGetTemplateForCollection()
    {
        $role = CollectionRole::new();
        $role->setName('test-role');
        $role->setOaiName('test-role');
        $role->store();

        $root = $role->addRootCollection();
        $root->setName('test-root-collection');
        $rootId = $role->store();

        $doc = Document::new();
        $doc->addCollection($root);
        $doc->store();

        // TODO when the root collection is added to the document it gets a new ID - Why?
        // TODO WARNING This only happens when the full test suite is run!
        // $this->assertEquals($rootId, $root->getId());

        $this->adjustConfiguration([
            'collection' => [$root->getId() => ['cover' => 'test-cover.md']],
        ]);

        $this->assertEquals('test-cover.md', $this->matcher->getTemplate($doc));
    }

    /**
     * TODO Conflict between templates of two or more collections assigned to a document is unresolved.
     */
    public function testGetTemplateForTwoCollectionsWithCovers()
    {
        $role = CollectionRole::new();
        $role->setName('test-role');
        $role->setOaiName('test-role');
        $role->store();

        $root = $role->addRootCollection();
        $root->setName('test-root-collection');
        $rootId = $role->store();

        $doc = Document::new();

        $col1 = Collection::new();
        $col1->setName('test-col-1');
        $root->addLastChild($col1);
        $colId1 = $col1->store();
        $doc->addCollection($col1);

        $col2 = Collection::new();
        $col2->setName('test-col-2');
        $root->addLastChild($col2);
        $colId2 = $col2->store();
        $doc->addCollection($col2);

        $doc->store();

        $this->adjustConfiguration([
            'collection' => [
                $colId1 => ['cover' => 'test-cover.md'],
                $colId2 => ['cover' => 'demo-cover.md'],
            ],
        ]);

        // TODO the collection that was added to the document first wins (What should happen?) - Log entry?
        $this->assertEquals('test-cover.md', $this->matcher->getTemplate($doc));
    }
}
