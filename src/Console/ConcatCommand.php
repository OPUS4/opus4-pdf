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

namespace Opus\Pdf\Console;

use Opus\Pdf\Cover\DefaultCoverGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_exists;

/**
 * Console command to generate a PDF cover for a given document ID.
 */
class ConcatCommand extends Command
{
    public const ARGUMENT_COVER = 'cover';

    public const ARGUMENT_DOCUMENT = 'document';

    public const ARGUMENT_MERGED = 'merged';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
Merges two PDF files.
EOT;

        $this->setName('pdf:concat')
            ->setDescription('Joins two PDF files')
            ->setHelp($help)
            ->addArgument(
                self::ARGUMENT_COVER,
                InputArgument::REQUIRED,
                'Cover PDF'
            )->addArgument(
                self::ARGUMENT_DOCUMENT,
                InputArgument::REQUIRED,
                'Cover PDF'
            )->addArgument(
                self::ARGUMENT_MERGED,
                InputArgument::REQUIRED,
                'Cover PDF'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coverPdf = $input->getArgument(self::ARGUMENT_COVER);
        if (! file_exists($coverPdf)) {
            $output->writeln("<error>PDF file not found: {$coverPdf}</error>");
        }

        $documentPdf = $input->getArgument(self::ARGUMENT_DOCUMENT);
        if (! file_exists($documentPdf)) {
            $output->writeln("<error>PDF file not found: {$documentPdf}</error>");
        }

        $outputPdf = $input->getArgument(self::ARGUMENT_MERGED);

        $coverGenerator = new DefaultCoverGenerator();
        $concatenator   = $coverGenerator->getPdfConcatenator();

        if ($concatenator !== null) {
            if ($concatenator->join($coverPdf, $documentPdf, $outputPdf) === null) {
                $output->writeln("<error>Failed to join PDF files</error>");
                return Command::FAILURE;
            }
        } else {
            $output->writeln("<error>PDF concatenator class not configured (pdf.covers.concatClass)</error>");
        }

        return Command::SUCCESS;
    }
}
