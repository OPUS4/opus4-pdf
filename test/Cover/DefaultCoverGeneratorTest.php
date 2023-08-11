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

namespace OpusTest\Pdf\Cover;

use Opus\Common\CollectionInterface;
use Opus\Common\CollectionRole;
use Opus\Common\CollectionRoleInterface;
use Opus\Common\Cover\CoverGeneratorFactory;
use Opus\Common\Cover\CoverGeneratorInterface;
use Opus\Common\Document;
use Opus\Pdf\Cover\DefaultCoverGenerator;
use OpusTest\Pdf\TestAsset\SimpleTestCase;

use function is_object;

class DefaultCoverGeneratorTest extends SimpleTestCase
{
    /** @var CollectionRoleInterface */
    protected $roleFixture;

    /** @var CollectionInterface */
    protected $collectionFixture;

    public function setUp(): void
    {
        parent::setUp();

        $this->roleFixture = CollectionRole::new();
        $this->roleFixture->setName('dummy-role');
        $this->roleFixture->setOaiName('dummy-oai');
        $this->roleFixture->store();

        $this->collectionFixture = $this->roleFixture->addRootCollection();
        $this->collectionFixture->setName('dummy-collection');
        $this->roleFixture->store();
    }

    protected function tearDown(): void
    {
        if (is_object($this->roleFixture)) {
            $this->roleFixture->delete();
        }

        parent::tearDown();
    }

    public function testCreate()
    {
        $overlayProperties = [
            'pdf' => [
                'covers' => [
                    'generatorClass' => DefaultCoverGenerator::class,
                ],
            ],
        ];

        $this->adjustConfiguration($overlayProperties);

        $generator = CoverGeneratorFactory::getInstance()->create();

        $this->assertNotNull($generator);
        $this->assertInstanceOf(CoverGeneratorInterface::class, $generator);
    }

    public function testGetCachedFilename()
    {
        $this->markTestIncomplete('not implemented yet');

        // TODO create File with pathName and parentId & call DefaultCoverGenerator->getCachedFilename($file)
    }

    public function testGetTemplateNameDefault()
    {
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Test document');
        $title->setLanguage('eng');
        $doc->store();

        $overlayProperties = [
            'pdf' => [
                'covers' => [
                    'default'        => 'demo-cover.md',
                    'generatorClass' => DefaultCoverGenerator::class,
                ],
            ],
        ];

        $this->adjustConfiguration($overlayProperties);

        $generator = CoverGeneratorFactory::getInstance()->create();

        $this->assertNotNull($generator);

        $templateName = $generator->getTemplateName($doc);

        $this->assertEquals('demo-cover.md', $templateName);
    }

    public function testGetTemplateNameForCollection()
    {
        /** @var CollectionInterface $subcollection */
        $subcollection = $this->collectionFixture->addFirstChild();
        $subcollection->setName('dummy-subcollection');
        $this->roleFixture->store();

        $doc = Document::new();
        $doc->store();

        $title = $doc->addTitleMain();
        $title->setValue('Test document belonging to a dummy collection');
        $title->setLanguage('eng');

        $doc->addCollection($subcollection);
        $doc->store();

        $subcollectionId = $subcollection->getId();

        $overlayProperties = [
            'collection' => [
                $subcollectionId => [
                    'cover' => 'demo-cover.md',
                ],
            ],
            'pdf'        => [
                'covers' => [
                    'generatorClass' => DefaultCoverGenerator::class,
                ],
            ],
        ];

        $this->adjustConfiguration($overlayProperties);

        $generator = CoverGeneratorFactory::getInstance()->create();

        $this->assertNotNull($generator);

        $templateName = $generator->getTemplateName($doc);

        $this->assertEquals('demo-cover.md', $templateName);
    }
}
