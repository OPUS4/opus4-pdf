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

use Opus\Common\Collection;
use Opus\Common\CollectionInterface;
use Opus\Common\DocumentInterface;

class CollectionMatcher extends AbstractTemplateMatcher
{
    /**
     * @param DocumentInterface$document
     * @return string|null
     */
    public function getTemplate($document)
    {
        $docCollections = $document->getCollection();

        foreach ($docCollections as $collection) {
            $templateName = $this->getTemplateNameForCollection($collection);
            if ($templateName !== null) {
                return $templateName;
            }
        }

        return null;
    }

    /**
     * Returns the first matching template name (or path relative to the templates directory) that has been defined
     * for the given collection or any of its parent collections. Returns null if no matching template was found.
     *
     * @param CollectionInterface $collection Document collection for which a matching template shall be found.
     * @return string|null Template name or path relative to templates directory.
     */
    protected function getTemplateNameForCollection($collection)
    {
        $templateId = $this->getTemplateIdForCollectionId($collection->getId());

        // if there's no template for the given collection, check its parent collection
        if ($templateId === null) {
            $parentCollectionId = $collection->getParentNodeId();
            if ($parentCollectionId !== null) {
                $parentCollection = Collection::get($parentCollectionId);
                $templateId       = $this->getTemplateNameForCollection($parentCollection);
            }
        }

        // NOTE: currently, the template ID is identical to the template name
        // TODO in a future implementation, it may be necessary to convert the template ID to a template name

        return $templateId;
    }

    /**
     * Returns the ID of a template that has been defined for the given collection, or null if no template was found.
     *
     * @param int $collectionId ID of a document collection for which a matching template shall be found.
     * @return string|null Template ID.
     */
    protected function getTemplateIdForCollectionId($collectionId)
    {
        // NOTE: The template name/rel.path <-> collection ID mapping is currently defined via a Config setting such as
        //       `collection.<COLLECTION_ID>.cover = '<TEMPLATE_NAME>'`; however, note that this is a temporary measure.
        // NOTE: As a result, the returned template ID is currently identical to the template name and is thus a string
        //       (instead of an int).
        // TODO better implementation of the template name/rel.path <-> collection ID mapping

        $collectionConfig = $this->getConfig()->collection;
        if ($collectionConfig === null) {
            return null;
        }

        $collectionConfigId = $collectionConfig->{$collectionId};
        if ($collectionConfigId === null) {
            return null;
        }

        $templateId = $collectionConfigId->cover;
        if (empty($templateId)) {
            return null;
        }

        return $templateId;
    }
}
